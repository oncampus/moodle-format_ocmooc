<?php

namespace format_ocmooc;

class badges extends base {

    public function __construct($courseid, $url) {
        $this->title = get_string('badges', 'format_ocmooc');
        parent::__construct($courseid, $url);
    }

    protected function render_view_custom() {
        global $DB, $OUTPUT, $USER;

        $data = array();
        $data['profileurl'] = new \moodle_url('/user/profile.php', ['id' => $USER->id]);
        $data['mybackpack'] = new \moodle_url('/badges/mybackpack.php');

        //show certificates if they exists via oc_mooc_nav
        if ($DB->record_exists('block_instances', ['blockname' => 'oc_mooc_nav'])){
            $this->show_certificates($this->courseid);
        }

        $badges = badges_get_user_badges($USER->id, $this->courseid);
        $this->prepare_badges($badges);

        $data['badges'] = array_values($badges);

        $allbadges = badges_get_badges(2, $this->courseid);
        $this->prepare_badges($allbadges);
        $this->prepare_all_badges_awarded($allbadges, $badges);
        $data['allbadges'] = array_values($allbadges);

        echo $OUTPUT->render_from_template('format_ocmooc/badges/badges', $data);
    }

    private function prepare_badges(&$badges) {
        foreach ($badges as &$badge) {
            $badge->badgeurl = new \moodle_url('/badges/overview.php', ['id' => $badge->id]);

            $badge->owned = true;

            $badge->imageurl = \moodle_url::make_pluginfile_url(
                    \context_course::instance($this->courseid)->id,
                    'badges',
                    'badgeimage',
                    $badge->id,
                    '/',
                    'f1',
                    false
            );
        }
        return $badges;
    }

    private function show_certificates($courseid){
        global $DB, $OUTPUT, $USER, $CFG;

        $context = \context_course::instance($courseid);

        $blockrecord = $DB->get_record('block_instances', array('blockname' => 'oc_mooc_nav', 'parentcontextid' => $context->id), '*', MUST_EXIST);

        $blockinstance = block_instance('oc_mooc_nav', $blockrecord);
        $total         = $blockinstance->config->capira_questions;//0;
        $min_prozent   = $blockinstance->config->capira_min;

        $min_prozent   = $blockinstance->config->capira_min;
        $simplecert_m  = $DB->get_record('modules', array('name' => 'simplecertificate'));
        $ildcert_m     = $DB->get_record('modules', array('name' => 'ildcertificate'));
        $certificate_m = $DB->get_record('modules', array('name' => 'coursecertificate'));
        $issue_count   = 0;

        echo \html_writer::start_div('ocmooc-certificates');
        if ($ildcert_m) {
            if ($ildcert_cm = $DB->get_record('course_modules', array('module' => $ildcert_m->id, 'course' => $courseid, 'visible' => 1))) {
                if (has_capability('mod/ildcertificate:addinstance', $context)) {
                    $ild_cert    = $DB->get_record('ildcertificate', array('course' => $courseid, 'id' => $ildcert_cm->instance));
                    $issue_count += count($DB->get_records('ildcertificate_issues', array('certificateid' => $ild_cert->id)));
//            echo 'Anzahl ausgestellter Zertifikate (' . get_string('only_for_trainers', 'format_ocmooc') . '): ' . count($cert_issues);
                }
            }
        }
        if ($simplecert_m) {
            if ($min_prozent > 0 && $simplecert_cm = $DB->get_record('course_modules', array('module' => $simplecert_m->id, 'course' => $courseid, 'visible' => 1))) {
                if (has_capability('mod/simplecertificate:addinstance', $context)) {
                    $simple_cert = $DB->get_record('simplecertificate', array('course' => $courseid, 'id' => $simplecert_cm->instance));
                    $issue_count += count($DB->get_records('simplecertificate_issues', array('certificateid' => $simple_cert->id)));
                }
            }
        }
        if ($issue_count > 0) {
            echo 'Anzahl ausgestellter Zertifikate (' . get_string('only_for_trainers', 'format_ocmooc') . '): ' . $issue_count;
        }

        echo \html_writer::tag('h2', \html_writer::tag('div', get_string('certificate', 'format_ocmooc'), array('class' => 'oc_badges_text')));
// echo html_writer::tag('div', html_writer::tag('div', get_string('cert_addtext', 'format_ocmooc'), array('class' => 'oc_badges_text')));
        if ($certificate_m && ($coursecert_cm = $DB->get_record('course_modules', array('module' => $certificate_m->id, 'course' => $courseid, 'visible' => 1)))) {
            $course_cert = $DB->get_records('tool_certificate_issues', array('courseid' => $courseid, 'userid' => $USER->id));
            if ($course_cert) {
                echo \html_writer::tag('div', get_string('cert_descr_general', 'format_ocmooc'));
                foreach ($course_cert as $cert) {
                    $link   = new \moodle_url('/admin/tool/certificate/view.php', array('code' => $cert->code, 'action' => 'get'));
                    $button = new \single_button($link, get_string('certificate', 'format_ocmooc'));
                    $button->add_action(
                        new \popup_action('click', $link, 'view' . $coursecert_cm->id,
                            array('height' => 600, 'width' => 800)));
                    echo \html_writer::tag('div', $OUTPUT->render($button), array('style' => 'text-align:left'));
                }
            }
        }
        if ($ildcert_m && ($ildcert_cm = $DB->get_record('course_modules', array('module' => $ildcert_m->id, 'course' => $courseid, 'visible' => 1)))) {
            // zertifikat anzeigen
            $module_context = \context_module::instance($ildcert_cm->id);
            if (has_capability('mod/ildcertificate:view', $module_context)
                && (has_capability('mod/ildcertificate:manage', $module_context) || $DB->record_exists('ildcertificate_issues', array('certificateid' => $ildcert_cm->instance, 'userid' => $USER->id)))
            ) {

                $url       = new \moodle_url('/mod/ildcertificate/view.php', array(
                    'id' => $ildcert_cm->id,
                    'tab' => 0,
                    'page' => 0,
                    'perpage' => 30,
                ));
                $canmanage = 0;//has_capability('mod/ildcertificate:manage', $module_context);

                $link   = new \moodle_url('/mod/ildcertificate/view.php', array('id' => $ildcert_cm->id, 'action' => 'get'));
                $button = new \single_button($link, get_string('certificate', 'format_ocmooc'));
                $button->add_action(
                    new \popup_action('click', $link, 'view' . $ildcert_cm->id,
                        array('height' => 600, 'width' => 800)));
                #echo html_writer::tag('h2', html_writer::tag('div', get_string('certificate', 'format_ocmooc'), array('class' => 'oc_badges_text')));
                echo \html_writer::tag('div', get_string('cert_descr_general', 'format_ocmooc'));
                echo \html_writer::tag('div', $OUTPUT->render($button), array('style' => 'text-align:left'));
            }
        }
        if ($simplecert_m) {
            if ($min_prozent > 0 && $simplecert_cm = $DB->get_record('course_modules', array('module' => $simplecert_m->id, 'course' => $courseid, 'visible' => 1))) {
                $percentage = 0;
                $mod_count  = 0;

                /* hvp start */
                require_once($CFG->libdir . '/gradelib.php');
                $hvp_percentage = 0;
                $hvp_module     = $DB->get_record('modules', array('name' => 'hvp'));
                $cm             = $DB->get_records('course_modules', array('course' => $courseid, 'module' => $hvp_module->id, 'completion' => 2, 'visible' => 1));
                $hvp_count      = count($cm);

                if ($hvp_count != 0) {
                    foreach ($cm as $module) {
                        $grading_info = grade_get_grades($module->course, 'mod', 'hvp', $module->instance, $USER->id);
                        $user_grade   = $grading_info->items[0]->grades[$USER->id]->grade;

                        $hvp_percentage += $user_grade / $hvp_count;
                    }

                    $percentage = $hvp_percentage;
                    $mod_count++;
                }
                /* hvp end */

                $percentage = $percentage / $mod_count;

                if ($percentage >= $min_prozent) {
                    // zertifikat anzeigen
                    $module_context = \context_module::instance($simplecert_cm->id);
                    require_capability('mod/simplecertificate:view', $module_context);

                    $url       = new \moodle_url('/mod/simplecertificate/view.php', array(
                        'id' => $simplecert_cm->id,
                        'tab' => 0,
                        'page' => 0,
                        'perpage' => 30,
                    ));
                    $canmanage = 0;//has_capability('mod/simplecertificate:manage', $module_context);

                    $link   = new \moodle_url('/mod/simplecertificate/view.php', array('id' => $simplecert_cm->id, 'action' => 'get'));
                    $button = new \single_button($link, get_string('certificate', 'format_ocmooc'));
                    $button->add_action(
                        new \popup_action('click', $link, 'view' . $simplecert_cm->id,
                            array('height' => 600, 'width' => 800)));
                    #echo html_writer::tag('h2', html_writer::tag('div', get_string('certificate', 'format_ocmooc'), array('class' => 'oc_badges_text')));
                    echo \html_writer::tag('div', get_string('cert_descr', 'format_ocmooc', $min_prozent));
                    echo \html_writer::tag('div', $OUTPUT->render($button), array('style' => 'text-align:left'));
                }
            }
        }
        echo \html_writer::end_div();

    }
    private function prepare_all_badges_awarded(&$allbadges, $badges) {
        foreach ($allbadges as &$allbadge) {
            $allbadge->owned = $this->check_is_badge_owned($allbadge, $badges);
        }
        return $allbadges;
    }

    private function display_badges() {
        global $USER;

        $badges = badges_get_user_badges($USER->id, $this->courseid);

        if ($badges) {
            echo \html_writer::start_tag('ul', ['class' => '']);

            foreach ($badges as $badge) {
                $badgeurl = new \moodle_url('/badges/overview.php', ['id' => $badge->id]);
                echo \html_writer::start_tag('li', ['class' => '']);

                $imageurl = \moodle_url::make_pluginfile_url(\context_course::instance($this->courseid)->id, 'badges', 'badgeimage',
                        $badge->id, '/', 'f1', false);

                $linkcontent = \html_writer::img($imageurl, $badge->name);
                $linkcontent .= \html_writer::span($badge->name, '');

                echo \html_writer::link($badgeurl, $linkcontent, ['class' => '']);

                echo \html_writer::end_tag('li');
            }

            echo \html_writer::end_tag('ul');
        } else {
            echo \html_writer::tag('div', get_string('no_badges_awarded', 'format_ocmooc'));
        }
    }

    private function display_all_badges() {
        global $USER;

        // Magic number. Currently I don't know why this is type 2 for normal badges.
        $badges = badges_get_badges(2, $this->courseid);
        $ownbadges = badges_get_user_badges($USER->id, $this->courseid);

        if ($badges) {
            echo \html_writer::start_tag('ul', ['class' => '']);

            foreach ($badges as $badge) {
                $badgeurl = new \moodle_url('/badges/overview.php', ['id' => $badge->id]);
                echo \html_writer::start_tag('li', ['class' => '']);

                $imageurl = \moodle_url::make_pluginfile_url(\context_course::instance($this->courseid)->id, 'badges', 'badgeimage',
                        $badge->id, '/', 'f1', false);

                $opacity = $this->check_is_badge_owned($badge, $ownbadges) ? "1.0" : "0.15";

                $linkcontent = \html_writer::img($imageurl, $badge->name, ['style' => "opacity: $opacity;"]);
                $linkcontent .= \html_writer::span($badge->name, '');

                echo \html_writer::link($badgeurl, $linkcontent, ['class' => '']);

                echo \html_writer::end_tag('li');
            }

            echo \html_writer::end_tag('ul');
        } else {
            echo \html_writer::tag('div', get_string('no_badges_awarded', 'format_ocmooc'));
        }
    }

    private function check_is_badge_owned($badge, $ownbadges) {
        global $DB;

        $hashes = array_keys($ownbadges);
        if (empty($hashes)) {
            return false;
        }

        list($insql, $insparams) = $DB->get_in_or_equal($hashes);

        $sql = "SELECT id FROM {badge_issued} WHERE badgeid = ? AND uniquehash $insql";
        $params = array_merge([$badge->id], $insparams);

        return $DB->record_exists_sql($sql, $params);
    }

    protected function render_editor_custom() {

    }

    public function render_overview() {

    }

    protected function handle_data($data) {

    }
}