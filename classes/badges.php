<?php

namespace format_ocmooc;

class badges extends base {

    public function __construct($courseid, $url) {
        $this->title = get_string('badges', 'format_ocmooc');
        parent::__construct($courseid, $url);
    }

    protected function render_view_custom() {
        global $OUTPUT, $USER;

        $data = array();
        $data['profileurl'] = new \moodle_url('/user/profile.php', ['id' => $USER->id]);
        $data['mybackpack'] = new \moodle_url('/badges/mybackpack.php');

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