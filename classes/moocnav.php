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
 * Class for handling MOOC navigation elements.
 *
 * @copyright 2023 Open LMS (https://www.openlms.net/)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package format_ocmooc
 */
class moocnav {
    /**
     * Get all navigation entries for the MOOC format.
     *
     * @return array Navigation entries with name, URL, and class.
     */
    public static function get_moocnav_entries() {
        global $COURSE, $DB;

        // Static entries.
        $moocnav = [
                [
                        'name' => get_string('coursetab', 'format_ocmooc'),
                        'url' => new \moodle_url('/course/view.php', ['id' => $COURSE->id]),
                        'class' => 'coursetab',
                ],
        ];

        // News forum.
        $type = 'news';
        if (self::is_forum($type)) {
            $forum = $DB->get_record('forum', ['course' => $COURSE->id, 'type' => $type]);
            $moocnav[] = [
                    'name' => get_string('newsforum', 'format_ocmooc'),
                    'url' => (new \moodle_url('/mod/forum/view.php', ['courseid' => $COURSE->id, 'f' => $forum->id]))->out(false),
                    'class' => 'forumtab',
            ];
        }

        $coursecontext = \context_course::instance($COURSE->id);
        if (is_guest($coursecontext)) {
            $allowed = get_config('format_ocmooc', 'participants_guest');
            if ($allowed) {
                $moocnav[] = [
                        'name' => get_string('participants', 'format_ocmooc'),
                        'url' => new \moodle_url('/course/format/ocmooc/views/participants.php', ['courseid' => $COURSE->id]),
                        'class' => 'participantstab',
                ];
            }
        } else {
            $moocnav[] = [
                    'name' => get_string('participants', 'format_ocmooc'),
                    'url' => new \moodle_url('/course/format/ocmooc/views/participants.php', ['courseid' => $COURSE->id]),
                    'class' => 'participantstab',
            ];
        }

        // Discussion forum.
        $type = 'social';
        if (self::is_forum($type)) {
            $social = $DB->get_record('forum', ['course' => $COURSE->id, 'type' => $type], '*', IGNORE_MULTIPLE);
            $moocnav[] = [
                    'name' => get_string('discussionforum', 'format_ocmooc'),
                    'url' => (new \moodle_url('/mod/forum/view.php', ['courseid' => $COURSE->id, 'f' => $social->id]))->out(false),
                    'class' => 'discussiontab',
            ];
        }

        if (self::is_social_area()) {
            $moocnav[] = [
                    'name' => get_string('social', 'format_ocmooc'),
                    'url' => new \moodle_url('/course/format/ocmooc/views/social.php', ['courseid' => $COURSE->id]),
                    'class' => 'socialtab',
            ];
        }

        $moocnav[] = [
                'name' => get_string('badges_nav', 'format_ocmooc'),
                'url' => new \moodle_url('/course/format/ocmooc/views/badges.php', ['courseid' => $COURSE->id]),
                'class' => 'badgestab',
        ];

        $moocnav[] = [
                'name' => get_string('forumlist', 'format_ocmooc'),
                'url' => new \moodle_url('/course/format/ocmooc/views/forumlist.php', ['courseid' => $COURSE->id]),
                'class' => 'forumlisttab',
        ];

        return array_merge_recursive($moocnav, self::get_html_pages());
    }

    /**
     * Get dropdown menu items for the MOOC navigation.
     *
     * @return array Dropdown items with URL and name.
     */
    public static function get_drowdown_items() {
        global $COURSE;

        $moocnavdropdown = [];

        $participants = new participants($COURSE->id, qualified_me());
        if ($params = $participants->get_unenrol_url()) {
            [$url, $string] = $params;
            $moocnavdropdown[] = [
                    'url' => $url,
                    'name' => $string,
            ];
        }

        return $moocnavdropdown;
    }

    /**
     * Check if the course has a social area.
     *
     * @return bool True if social area exists, false otherwise.
     */
    private static function is_social_area() {
        global $COURSE, $DB;

        return $DB->record_exists('format_ocmooc_social', ['courseid' => $COURSE->id]);
    }

    /**
     * Get HTML pages for the MOOC navigation.
     *
     * @return array HTML pages with name and URL.
     */
    private static function get_html_pages() {
        global $COURSE, $DB;

        $moocnav = [];

        $pages = $DB->get_records('format_ocmooc_htmlsite', ['courseid' => $COURSE->id]);
        foreach ($pages as $page) {
            $moocnav[] = [
                    'name' => $page->title,
                    'url' => (new \moodle_url(
                        '/course/format/ocmooc/views/page.php',
                        ['courseid' => $COURSE->id, 'id' => $page->id]
                    ))->out(false),
            ];
        }
        return $moocnav;
    }

    /**
     * Check if a forum of the specified type exists in the course.
     *
     * @param string $type The type of forum to check for.
     * @return bool True if forum exists, false otherwise.
     */
    private static function is_forum($type) {
        global $COURSE, $DB;

        return $DB->record_exists('forum', ['course' => $COURSE->id, 'type' => $type]);
    }
}
