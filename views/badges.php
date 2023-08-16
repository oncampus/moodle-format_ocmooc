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

$badges->setup_page();
echo $OUTPUT->header();

$badges->render_view();

$badges->render_editor(false);

echo $OUTPUT->footer();