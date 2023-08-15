<?php

require_once(__DIR__ . "/../../../../config.php");

$courseid = required_param('courseid', PARAM_INT);
$coursecontext = context_course::instance($courseid);
$course = get_course($courseid);

require_login($courseid);

if ($course->format != 'ocmooc') {
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
}

$url = new moodle_url('/course/format/ocmooc/views/badges.php', ['courseid' => $courseid]);
$badges = new \format_ocmooc\badges($courseid, $url);

$badges->handle_form();

$PAGE->set_url($url);
$PAGE->set_title(get_string('badges', 'format_ocmooc'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($coursecontext);

echo $OUTPUT->header();

$badges->render_view();

$badges->render_editor();

echo $OUTPUT->footer();