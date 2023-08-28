<?php

namespace format_ocmooc;

class moocnav {

    public static function get_moocnav_entries() {
        global $COURSE, $DB;

        // static entries.
        $moocnav = [
                [
                        'name' => get_string('course'),
                        'url' => new \moodle_url('/course/view.php', ['id' => $COURSE->id])
                ],
        ];

        // news forum
        $type = 'news';
        if (self::is_forum($type)) {
            $forum = $DB->get_record('forum', ['course' => $COURSE->id, 'type' => $type]);
            $moocnav[] = [
                'name' => get_string('newsforum', 'format_ocmooc'),
                'url' => (new \moodle_url('/mod/forum/view.php', ['courseid' => $COURSE->id, 'f' => $forum->id]))->out(false)
            ];
        }
        $moocnav[] = [
                'name' => get_string('participants', 'format_ocmooc'),
                'url' => new \moodle_url('/course/format/ocmooc/views/participants.php', ['courseid' => $COURSE->id]),
        ];
        //discussion forum
        $type = 'general';
        if (self::is_forum($type)){
            $social = $DB->get_record('forum', ['course' => $COURSE->id, 'type'=> $type]);
            $moocnav[]= [
                'name' => get_string('discussionforum', 'format_ocmooc'),
                'url' => (new \moodle_url('/mod/forum/view.php', ['courseid' => $COURSE->id,'f' => $social->id]))->out(false),
            ];
        }

        $htmlpages = $DB->get_records('format_ocmooc_htmlsite', ['courseid' => $COURSE->id]);
        if ($htmlpages) {
            foreach ($htmlpages as $htmlpage) {
                $moocnav[] = [
                        'name' => $htmlpage->title,
                        'url' => (new \moodle_url('/course/format/ocmooc/views/htmlpage.php',
                                ['id' => $htmlpage->id, 'courseid' => $COURSE->id]))->out(false),
                ];
            }
        }

        if (self::is_social_area()) {
            $moocnav[] = [
                    'name' => get_string('social', 'format_ocmooc'),
                    'url' => new \moodle_url('/course/format/ocmooc/views/social.php', ['courseid' => $COURSE->id]),
            ];
        }

        $moocnav[] = [
                'name' => get_string('badges', 'format_ocmooc'),
                'url' => new \moodle_url('/course/format/ocmooc/views/badges.php', ['courseid' => $COURSE->id]),
        ];

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
    private static function is_forum($type) {
        global $COURSE, $DB;

        return $DB->record_exists('forum', ['course' => $COURSE->id, 'type'=> $type]);
    }

}