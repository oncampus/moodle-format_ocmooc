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
 * View of extra coursepages is defined here
 *
 * @package     format_ocmooc
 * @copyright   2022 oncampus GmbH <support@oncampus.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../config.php');
global $USER, $CFG, $PAGE, $OUTPUT, $DB;

$courseid = \required_param('courseid', PARAM_INT);
$id       = \required_param('id', PARAM_INT);

$course        = get_course($courseid);
$coursecontext = context_course::instance($courseid);

require_login($course);
$htmlpage = $DB->get_record('format_ocmooc_htmlsite', ['courseid' => $courseid, 'id' => $id]);
if (!$htmlpage) {
    redirect(new moodle_url('course/view.php', ['id' => $courseid]));
}
$PAGE->set_url(new moodle_url('/course/format/ocmooc/views/page.php', ['courseid' => $courseid, 'id' => $id]));
$PAGE->set_pagelayout('course');

$format         = course_get_format($courseid);
$course->format = $format->get_format();

$PAGE->set_pagetype('course-view-' . $course->format);
$PAGE->set_title($htmlpage->title);
$PAGE->set_heading($htmlpage->title);
$PAGE->add_body_class('custom-mooc-page');
echo $OUTPUT->header();
echo html_writer::start_div('html-page-wrapper custompage-wrapper');
echo $htmlpage->content;
echo html_writer::end_div();

echo $OUTPUT->footer();
