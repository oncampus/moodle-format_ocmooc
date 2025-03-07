<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Event observers used in ocmooc format
 *
 * @package    format_ocmooc
 * @copyright  2023 OnCampus GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Event observer for mod_forum.
 */
class format_ocmooc_observer {
    /**
     * Observer for \core\event\course_created event.
     *
     * @param \core\event\course_created $event
     * @return void
     */
    public static function course_created(\core\event\course_created $event) {
        global $CFG, $DB;

        $course = $event->get_record_snapshot('course', $event->objectid);
        $format = course_get_format($course);
        $socialforum = $DB->get_record('forum', ['course' => $course->id, 'type' => 'social']);
        if (!$socialforum && ($format->get_format() === 'ocmooc')) {
            require_once($CFG->dirroot . '/mod/forum/lib.php');
            // Auto create the Social forum.
            forum_get_course_forum($event->objectid, 'social');
        }
    }

    /**
     * Observer for \core\event\course_category_updated
     *
     * @param \core\event\course_updated $event
     * @return void
     */
    public static function course_updated(\core\event\course_updated $event) {
        global $CFG, $DB;

        $course = $event->get_record_snapshot('course', $event->objectid);
        $format = course_get_format($course);
        $socialforum = $DB->get_record('forum', ['course' => $course->id, 'type' => 'social']);
        // Check if the social forum already exists.
        if (!$socialforum && ($format->get_format() === 'ocmooc')) {
            require_once($CFG->dirroot . '/mod/forum/lib.php');
            // Auto create the Social forum.
            forum_get_course_forum($event->objectid, 'social');
        }
    }
}
