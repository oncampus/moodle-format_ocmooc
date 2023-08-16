<?php

require_once(__DIR__ . "/../../../../config.php");

$courseid = required_param('courseid', PARAM_INT);
$coursecontext = context_course::instance($courseid);
$course = get_course($courseid);

require_login($courseid);

if ($course->format != 'ocmooc') {
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
}

$url = new moodle_url('/course/format/ocmooc/views/social.php', ['courseid' => $courseid]);
$social = new \format_ocmooc\social($courseid, $url);

$social->setup_page();
echo $OUTPUT->header();

$social->render_view();
echo $OUTPUT->footer();