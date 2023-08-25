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
        $chapterno = $sections->add_chapter();
        $redirecturl->param('chapter', $chapterno);
        break;
    case 'addlection':
        $chapterid = required_param('chapterid', PARAM_INT);
        list($chapterno, $lectiono) = $sections->add_lection($chapterid);
        $redirecturl->param('chapter', $chapterno);
        $redirecturl->param('lection', $lectiono);
        break;
    case 'confirm-delete':
        $id = required_param('id', PARAM_INT);
        $sections->deleteconfirmation($id,$course,$redirecturl);
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