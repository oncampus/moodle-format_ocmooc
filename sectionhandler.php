<?php

require_once(__DIR__ . '/../../../config.php');

$action = required_param('action', PARAM_TEXT);
$sesskey = required_param('sesskey', PARAM_ALPHANUM);
$courseid = required_param('courseid', PARAM_INT);

$context = context_course::instance($courseid);
$course = get_course($courseid);

require_login($courseid);
require_sesskey();

$redirecturl = new moodle_url('/course/view.php', ['id' => $courseid]);

if (!has_capability('format/ocmooc:edit', $context) || $course->format != 'ocmooc') {
    redirect($redirecturl);
}

$sections = new format_ocmooc\sections($courseid);

switch ($action) {
    case 'addchapter':
        $sections->add_chapter();
        break;
    case 'addlection':
        $chapterid = required_param('chapterid', PARAM_INT);
        $sections->add_lection($chapterid);
        break;
    case 'delete':
        $id = required_param('id', PARAM_INT);
        $sections->delete_section($id);
        break;
    case 'reorder':
        $sections->reorder_sections();
        break;
}

redirect($redirecturl);