<?php

namespace format_ocmooc;

class moocnav {

    public static function get_moocnav_entries() {
        global $COURSE;

        // static entries.
        $moocnav = [
                [
                        'name' => get_string('course'),
                        'url' => new \moodle_url('/course/view.php', ['id' => $COURSE->id])
                ],
        ];

        if (self::is_social_area()) {
            $moocnav[] = [
                    'name' => get_string('social', 'format_ocmooc'),
                    'url' => new \moodle_url('/course/format/ocmooc/views/social.php', ['courseid' => $COURSE->id]),
            ];
        }

        $moocnav = array_merge_recursive($moocnav, self::get_html_pages());

        return $moocnav;
    }

    private static function is_social_area() {
        global $COURSE, $DB;

        return $DB->record_exists('format_ocmooc_social', ['courseid' => $COURSE->id]);
    }

    private static function get_html_pages() {
        global $COURSE, $DB;

        $moocnav = array();

        $pages = $DB->get_records('format_ocmooc_htmlsite', ['courseid' => $COURSE->id]);
        foreach ($pages as $page) {
            $moocnav[] = [
                    'name' => $pages->title,
                    'url' => new \moodle_url('/course/format/ocmooc/views/page.php', ['courseid' => $COURSE->id, 'id' => $page->id])
            ];
        }

        return $moocnav;
    }

}