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
 * Forum list view for the OCMOOC course format.
 *
 * This file displays the forum list view for the OCMOOC course format.
 * It includes the necessary code to render the forum list view and handle
 * the course context.
 *
 * @package format_ocmooc
 * @copyright 2025 oncampus GmbH
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace format_ocmooc\views;

use core\context\course as context_course;
use moodle_url;

require_once(__DIR__ . "/../../../../config.php");

$courseid = required_param('courseid', PARAM_INT);
$coursecontext = context_course::instance($courseid);
$course = get_course($courseid);

require_login($courseid);

if ($course->format != 'ocmooc') {
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
}

$url = new moodle_url('/course/format/ocmooc/views/forumlist.php', ['courseid' => $courseid]);
$forumlist = new \format_ocmooc\forumlist($courseid, $url);

$forumlist->handle_form();

$forumlist->setup_page();
echo $OUTPUT->header();

$forumlist->render_view();

$forumlist->render_editor(false);

echo $OUTPUT->footer();
