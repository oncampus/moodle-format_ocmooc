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
 * HTML page edit page for the OCMOOC course format.
 *
 * This file displays the HTML page edit page for the OCMOOC course format.
 * It includes the necessary code to render the HTML page edit page and handle
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
$action = optional_param('action', null, PARAM_TEXT);
$id = optional_param('id', null, PARAM_INT);
$confirm = optional_param('confirm', null, PARAM_ALPHANUM);
$backurl = optional_param('backurl', null, PARAM_URL);

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

$urlparams = ['courseid' => $courseid];

if (isset($action)) {
    $urlparams['action'] = $action;
}

if (isset($id)) {
    $urlparams['id'] = $id;
}

if (isset($backurl)) {
    $urlparams['backurl'] = $backurl;
}

$url = new moodle_url('/course/format/ocmooc/edit/htmlpage.php', $urlparams);
$htmlpage = new \format_ocmooc\htmlpage($courseid, $url, $action, $id);

if (isset($action)) {
    if ($action == 'create' || $action == 'edit') {
        $htmlpage->handle_form(true);
    } else if ($action == 'delete' && $confirm == md5($id . $courseid)) {
        $htmlpage->delete();
    }
}

$PAGE->set_url($url);
$PAGE->set_title(get_string('htmlpage', 'format_ocmooc'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($coursecontext);

echo $OUTPUT->header();

if (isset($action)) {
    if ($action != 'delete') {
        $htmlpage->render_editor();
    }
} else {
    $htmlpage->render_overview();
}

echo $OUTPUT->footer();
