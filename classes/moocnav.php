<?php

namespace format_ocmooc;

class moocnav {

    public static function get_moocnav_entries() {
        global $COURSE, $DB;

        // static entries.
        $moocnav = [
                [
                        'name' => get_string('coursetab', 'format_ocmooc'),
                        'url' => new \moodle_url('/course/view.php', ['id' => $COURSE->id]),
                        'class' => 'coursetab'
                ],
        ];

        // news forum
        $type = 'news';
        if (self::is_forum($type)) {
            $forum = $DB->get_record('forum', ['course' => $COURSE->id, 'type' => $type]);
            $moocnav[] = [
                'name' => get_string('newsforum', 'format_ocmooc'),
                'url' => (new \moodle_url('/mod/forum/view.php', ['courseid' => $COURSE->id, 'f' => $forum->id]))->out(false),
                'class' => 'forumtab'
            ];
        }

        $coursecontext = \context_course::instance($COURSE->id);
        if (is_guest($coursecontext)) {
            $allowed = get_config('format_ocmooc', 'participants_guest');
            if ($allowed) {
                $moocnav[] = [
                        'name' => get_string('participants', 'format_ocmooc'),
                        'url' => new \moodle_url('/course/format/ocmooc/views/participants.php', ['courseid' => $COURSE->id]),
                        'class' => 'participantstab'
                ];
            }
        } else {
            $moocnav[] = [
                    'name' => get_string('participants', 'format_ocmooc'),
                    'url' => new \moodle_url('/course/format/ocmooc/views/participants.php', ['courseid' => $COURSE->id]),
                    'class' => 'participantstab'
            ];
        }

        //discussion forum
        $type = 'social';
        if (self::is_forum($type)){
            $social = $DB->get_record('forum', ['course' => $COURSE->id, 'type'=> $type],'*',IGNORE_MULTIPLE );
            $moocnav[]= [
                'name' => get_string('discussionforum', 'format_ocmooc'),
                'url' => (new \moodle_url('/mod/forum/view.php', ['courseid' => $COURSE->id,'f' => $social->id]))->out(false),
                'class' => 'discussiontab'
            ];
        }

        if (self::is_social_area()) {
            $moocnav[] = [
                    'name' => get_string('social', 'format_ocmooc'),
                    'url' => new \moodle_url('/course/format/ocmooc/views/social.php', ['courseid' => $COURSE->id]),
                    'class' => 'socialtab'
            ];
        }

        $moocnav[] = [
                'name' => get_string('badges_nav', 'format_ocmooc'),
                'url' => new \moodle_url('/course/format/ocmooc/views/badges.php', ['courseid' => $COURSE->id]),
                'class' => 'badgestab'
        ];

        $moocnav[] = [
            'name' => get_string('forumlist', 'format_ocmooc'),
            'url' => new \moodle_url('/course/format/ocmooc/views/forumlist.php', ['courseid' => $COURSE->id]),
            'class' => 'forumlisttab'
        ];

        return array_merge_recursive($moocnav, self::get_html_pages());
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
                'name' => $page->title,
                'url' => (new \moodle_url('/course/format/ocmooc/views/page.php', ['courseid' => $COURSE->id, 'id' => $page->id]))->out(false)
            ];
        }
        return $moocnav;
    }
    private static function is_forum($type) {
        global $COURSE, $DB;

        return $DB->record_exists('forum', ['course' => $COURSE->id, 'type'=> $type]);
    }

}