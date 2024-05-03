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
 *  Display the whole course.
 *
 * @package     format_ocmooc
 * @copyright   2022 oncampus GmbH <support@oncampus.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $PAGE, $CFG;
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/completionlib.php');

// Retrieve course format option fields and add them to the $course object.
$format = course_get_format($course);
$course = $format->get_course();
$context = context_course::instance($course->id);

// Add any extra logic here.

// Make sure section 0 is created.
course_create_sections_if_missing($course, 0);

$renderer = $format->get_renderer($PAGE);

// Setup the format base instance.
if (!empty($displaysection)) {
    $format->set_section_number($displaysection);
}
$isediting = $PAGE->user_is_editing();
if ($isediting) {
    $templateable = new \format_ocmooc\output\courseformat\fullcontent($format);
    $data = $templateable->export_for_template($renderer);
    echo $renderer->render_from_template('format_ocmooc/local/content/content', $data);
    $PAGE->requires->js_call_amd('format_ocmooc/jumpto_section', 'init');
} else {
    // Output course content.
    $outputclass = $format->get_output_classname('content');
    $widget = new $outputclass($format);
    echo $renderer->render($widget);
}
// Include any format js module here using $PAGE->requires->js.
$PAGE->requires->js('/course/format/ocmooc/lockEditbutton.js');
$PAGE->requires->js('/course/format/ocmooc/chapterslider.js');
$PAGE->requires->js('/course/format/ocmooc/courseEditorToggler.js');
$PAGE->requires->js('/course/format/ocmooc/amd/build/hvp_resizer_parent.min.js');