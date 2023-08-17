<?php

require_once(__DIR__ . '/../../../config.php');

$action = required_param('action', PARAM_TEXT);
$sesskey = required_param('sesskey', PARAM_ALPHANUM);
$courseid = required_param('courseid', PARAM_INT);
$position = required_param('position', PARAM_INT);

$context = context_course::instance($courseid);
$course = get_course($courseid);

require_login($courseid);
require_sesskey();

$redirecturl = new moodle_url('/course/view.php', ['id' => $courseid]);

if (!has_capability('format/ocmooc:edit', $context) || $course->format != 'ocmooc') {
    redirect($redirecturl);
}

$format = course_get_format($courseid);

switch ($action) {
    case 'addchapter':
        $section = course_create_section($courseid);
        break;
    case 'addlection':
        $chapterid = required_param('chapterid', PARAM_INT);
        $section = course_create_section($courseid);
        $section->parent = $chapterid;
        $format->update_section_format_options($section);
        break;
    case 'delete':
        $id = required_param('id', PARAM_INT);
        $section = $format->get_section($id);

        $records = $DB->get_records('course_format_options',
                ['courseid' => $courseid, 'format' => 'ocmooc', 'name' => 'parent', 'value' => $id]);
        if ($records) {
            foreach ($records as $record) {
                $child = $format->get_section($record->sectionid);
                if ($format->can_delete_section($child)) {
                    $format->delete_section($child);
                } else {
                    $child->parent = 0;
                    $format->update_section_format_options($child);
                }
            }
        }

        if ($format->can_delete_section($id)) {
            $format->delete_section($id);
        }
        break;
}

redirect($redirecturl);