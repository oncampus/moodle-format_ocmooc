<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace format_ocmooc;

/**
 * Class for handling badges in the OCMOOC format
 *
 * @package    format_ocmooc
 * @copyright  2025 oncampus GmbH <support@oncampus.de>
 * @author     oncampus GmbH <support@oncampus.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class badges extends base {
    /**
     * The URL for the badge.
     *
     * @var \moodle_url
     */
    protected $badgeurl;

    /**
     * Constructor for the badges class
     *
     * @param int $courseid The course ID
     * @param string $url The URL
     */
    public function __construct($courseid, $url) {
        $this->title = get_string('badges', 'format_ocmooc');
        parent::__construct($courseid, $url);
    }

    /**
     * Renders the custom view for badges
     */
    protected function render_view_custom() {
        global $DB, $OUTPUT, $USER;

        $data = [];
        $data['profileurl'] = new \moodle_url('/user/profile.php', ['id' => $USER->id]);
        $data['mybackpack'] = new \moodle_url('/badges/mybackpack.php');

        $this->show_certificates($this->courseid);

        // Get user badges and prepare them for display.
        $userbadges = badges_get_user_badges($USER->id, $this->courseid);
        $preparedbadges = [];
        foreach ($userbadges as $badge) {
            $badgedata = new \stdClass();
            $badgedata->id = $badge->id;
            $badgedata->name = $badge->name;
            $badgedata->description = $badge->description;
            $badgedata->badgeurl = new \moodle_url('/badges/overview.php', ['id' => $badge->id]);
            $badgedata->owned = true;
            $badgedata->imageurl = \moodle_url::make_pluginfile_url(
                \context_course::instance($this->courseid)->id,
                'badges',
                'badgeimage',
                $badge->id,
                '/',
                'f1',
                false
            );
            $preparedbadges[] = $badgedata;
        }
        $data['badges'] = array_values($preparedbadges);

        // Get all course badges and prepare them for display.
        $allbadges = badges_get_badges(2, $this->courseid);
        $preparedallbadges = [];
        foreach ($allbadges as $badge) {
            $badgedata = new \stdClass();
            $badgedata->id = $badge->id;
            $badgedata->name = $badge->name;
            $badgedata->description = $badge->description;
            $badgedata->badgeurl = new \moodle_url('/badges/overview.php', ['id' => $badge->id]);
            $badgedata->owned = $this->check_is_badge_owned($badge, $userbadges);
            $badgedata->imageurl = \moodle_url::make_pluginfile_url(
                \context_course::instance($this->courseid)->id,
                'badges',
                'badgeimage',
                $badge->id,
                '/',
                'f1',
                false
            );
            $preparedallbadges[] = $badgedata;
        }
        $data['allbadges'] = array_values($preparedallbadges);

        echo $OUTPUT->render_from_template('format_ocmooc/badges/badges', $data);
    }

    /**
     * Shows certificates for the course
     *
     * @param int $courseid The course ID
     */
    private function show_certificates($courseid) {
        global $DB, $OUTPUT, $USER, $CFG;

        $context = \context_course::instance($courseid);

        $minprozent   = 0;
        $record = $DB->get_record('course_format_options', ['courseid' => $courseid, 'name' => 'certpercentage']);
        if ($record) {
            $minprozent   = $record->value;
        }

        $simplecertm  = $DB->get_record('modules', ['name' => 'simplecertificate']);
        $ildcertm     = $DB->get_record('modules', ['name' => 'ildcertificate']);
        $certificatem = $DB->get_record('modules', ['name' => 'coursecertificate']);
        $issuecount   = 0;

        echo \html_writer::start_div('ocmooc-certificates');
        if ($ildcertm) {
            if (
                $ildcertcm = $DB->get_record(
                    'course_modules',
                    ['module' => $ildcertm->id, 'course' => $courseid, 'visible' => 1]
                )
            ) {
                if (has_capability('mod/ildcertificate:addinstance', $context)) {
                    $ildcert    = $DB->get_record('ildcertificate', ['course' => $courseid, 'id' => $ildcertcm->instance]);
                    $issuecount += count($DB->get_records('ildcertificate_issues', ['certificateid' => $ildcert->id]));
                }
            }
        }
        if ($simplecertm) {
            if (
                $minprozent > 0 && $simplecertcm = $DB->get_record(
                    'course_modules',
                    ['module' => $simplecertm->id, 'course' => $courseid, 'visible' => 1]
                )
            ) {
                if (has_capability('mod/simplecertificate:addinstance', $context)) {
                    $simplecert = $DB->get_record('simplecertificate', ['course' => $courseid, 'id' => $simplecertcm->instance]);
                    $issuecount += count($DB->get_records('simplecertificate_issues', ['certificateid' => $simplecert->id]));
                }
            }
        }
        if ($issuecount > 0) {
            echo 'Anzahl ausgestellter Zertifikate (' . get_string('only_for_trainers', 'format_ocmooc') . '): ' . $issuecount;
        }

        echo \html_writer::tag(
            'h2',
            \html_writer::tag('div', get_string('certificate_and_badges', 'format_ocmooc'), ['class' => 'oc_badges_text'])
        );
        echo \html_writer::tag(
            'h2',
            \html_writer::tag('div', get_string('certificate', 'format_ocmooc'), ['class' => 'oc_badges_text'])
        );

        // Check whether certificates can be obtained and output a string about the current certificate situation.
        if ($simplecertm || $ildcertm || $certificatem) {
            if (
                $DB->get_record('course_modules', ['module' => $ildcertm->id, 'course' => $courseid, 'visible' => 1])
                    || $DB->get_record('course_modules', ['module' => $simplecertm->id, 'course' => $courseid, 'visible' => 1])
                    || $DB->get_record('course_modules', ['module' => $certificatem->id, 'course' => $courseid, 'visible' => 1])
            ) {
                // There is at least one certificate included in the course.
                echo \html_writer::tag('div', \html_writer::tag(
                    'div',
                    get_string('cert_available', 'format_ocmooc'),
                    ['class' => 'oc_badges_text']
                ));
            } else {
                // There is no certificate in the course.
                echo \html_writer::tag(
                    'div',
                    \html_writer::tag('div', get_string('cert_nocert', 'format_ocmooc'), ['class' => 'oc_badges_text'])
                );
            }

            if (
                $certificatem && ($coursecertcm = $DB->get_record(
                    'course_modules',
                    ['module' => $certificatem->id, 'course' => $courseid, 'visible' => 1]
                ))
            ) {
                $coursecert = $DB->get_records('tool_certificate_issues', ['courseid' => $courseid, 'userid' => $USER->id]);
                if ($coursecert) {
                    echo \html_writer::tag('div', get_string('cert_descr_general', 'format_ocmooc'));
                    foreach ($coursecert as $cert) {
                        $link = new \moodle_url(
                            '/admin/tool/certificate/view.php',
                            ['code' => $cert->code, 'action' => 'get']
                        );
                        $button = new \single_button($link, get_string('certificate', 'format_ocmooc'));
                        $button->add_action(
                            new \popup_action(
                                'click',
                                $link,
                                'view' . $coursecertcm->id,
                                ['height' => 600, 'width' => 800]
                            )
                        );
                        echo \html_writer::tag('div', $OUTPUT->render($button), ['style' => 'text-align:left']);
                    }
                }
            }
            if (
                $ildcertm && ($ildcertcm = $DB->get_record(
                    'course_modules',
                    ['module' => $ildcertm->id, 'course' => $courseid, 'visible' => 1]
                ))
            ) {
                // Zertifikat anzeigen.
                $modulecontext = \context_module::instance($ildcertcm->id);
                if (
                    has_capability('mod/ildcertificate:view', $modulecontext)
                        && (has_capability('mod/ildcertificate:manage', $modulecontext)
                                || $DB->record_exists(
                                    'ildcertificate_issues',
                                    ['certificateid' => $ildcertcm->instance, 'userid' => $USER->id]
                                ))
                ) {
                    $link = new \moodle_url('/mod/ildcertificate/view.php', ['id' => $ildcertcm->id, 'action' => 'get']);
                    $button = new \single_button($link, get_string('certificate', 'format_ocmooc'));
                    $button->add_action(
                        new \popup_action(
                            'click',
                            $link,
                            'view' . $ildcertcm->id,
                            ['height' => 600, 'width' => 800]
                        )
                    );
                    echo \html_writer::tag('div', get_string('cert_descr_general', 'format_ocmooc'));
                    echo \html_writer::tag('div', $OUTPUT->render($button), ['style' => 'text-align:left']);
                }
            }
            if ($simplecertm) {
                if (
                    $minprozent > 0 && $simplecertcm = $DB->get_record(
                        'course_modules',
                        ['module' => $simplecertm->id, 'course' => $courseid, 'visible' => 1]
                    )
                ) {
                    echo \html_writer::tag('div', \html_writer::tag(
                        'div',
                        get_string('cert_addtext', 'format_ocmooc'),
                        ['class' => 'oc_badges_text']
                    ));
                    $percentage = 0;
                    $modcount = 0;

                    /* hvp start */
                    require_once($CFG->libdir . '/gradelib.php');
                    $hvppercentage = 0;
                    $hvpmodule = $DB->get_record('modules', ['name' => 'hvp']);
                    $cm = $DB->get_records(
                        'course_modules',
                        ['course' => $courseid, 'module' => $hvpmodule->id, 'completion' => 2, 'visible' => 1]
                    );
                    $hvpcount = count($cm);

                    if ($hvpcount != 0) {
                        foreach ($cm as $module) {
                            $gradinginfo = grade_get_grades($module->course, 'mod', 'hvp', $module->instance, $USER->id);
                            $usergrade = $gradinginfo->items[0]->grades[$USER->id]->grade;

                            $hvppercentage += $usergrade / $hvpcount;
                        }

                        $percentage = $hvppercentage;
                        $modcount++;
                    }
                    /* hvp end */

                    $percentage = (int) ($percentage / $modcount);

                    if ($percentage >= $minprozent) {
                        // Zertifikat anzeigen.
                        $modulecontext = \context_module::instance($simplecertcm->id);
                        require_capability('mod/simplecertificate:view', $modulecontext);

                        $link = new \moodle_url(
                            '/mod/simplecertificate/view.php',
                            ['id' => $simplecertcm->id, 'action' => 'get']
                        );
                        $button = new \single_button($link, get_string('certificate', 'format_ocmooc'));
                        $button->add_action(
                            new \popup_action(
                                'click',
                                $link,
                                'view' . $simplecertcm->id,
                                ['height' => 600, 'width' => 800]
                            )
                        );
                        echo \html_writer::tag('div', get_string('cert_descr', 'format_ocmooc', $minprozent));
                        echo \html_writer::tag('div', $OUTPUT->render($button), ['style' => 'text-align:left']);
                    } else {
                        echo \html_writer::tag('div', \html_writer::tag('div', get_string(
                            'cert_need',
                            'format_ocmooc',
                            ['min_per' => $minprozent, 'current' => $percentage]
                        )));
                    }
                }
            }
            echo \html_writer::end_div();
        }
    }

    /**
     * Checks if a badge is owned by the user
     *
     * @param object $badge The badge object
     * @param array $ownbadges Array of badge objects
     * @return bool True if the badge is owned, false otherwise
     */
    private function check_is_badge_owned($badge, $ownbadges) {
        global $DB;

        $hashes = array_keys($ownbadges);
        if (empty($hashes)) {
            return false;
        }

        [$insql, $insparams] = $DB->get_in_or_equal($hashes);

        $sql = "SELECT id FROM {badge_issued} WHERE badgeid = ? AND uniquehash $insql";
        $params = array_merge([$badge->id], $insparams);

        return $DB->record_exists_sql($sql, $params);
    }

    /**
     * Renders the custom editor for badges
     */
    protected function render_editor_custom() {
    }

    /**
     * Renders the overview for badges
     */
    public function render_overview() {
    }

    /**
     * Handles the data for badges
     *
     * @param array $data The data to handle
     */
    protected function handle_data($data) {
    }
}
