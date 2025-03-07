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

/**
 * Social edit page for the OCMOOC course format.
 *
 * This file displays the social edit page for the OCMOOC course format.
 * It includes the necessary code to render the social edit page and handle
 * the course context.
 *
 * @package format_ocmooc
 * @copyright 2025 oncampus GmbH
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . "/../../../../config.php");

$courseid = required_param('courseid', PARAM_INT);
$coursecontext = context_course::instance($courseid);
$course = get_course($courseid);

require_login($courseid);

if (!has_capability('format/ocmooc:edit', $coursecontext)) {
    redirect(
        '/index.php',
        get_string('error:nocapability', 'format_ocmooc'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

if ($course->format != 'ocmooc') {
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
}

$url = new moodle_url('/course/format/ocmooc/edit/social.php', ['courseid' => $courseid]);
$social = new \format_ocmooc\social($courseid, $url);
$social->handle_form();

$PAGE->set_url($url);
$PAGE->set_title(get_string('social', 'format_ocmooc'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($coursecontext);

echo $OUTPUT->header();
$social->render_editor();

$social->render_overview();
echo $OUTPUT->footer();
