<?php

use format_ocmooc\forum;

require_once(__DIR__ . "/../../../../config.php");

$courseid = required_param('courseid', PARAM_INT);
$type = required_param('type', PARAM_TEXT);
$coursecontext = context_course::instance($courseid);
$course = get_course($courseid);

require_login($courseid);

if ($course->format != 'ocmooc') {
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
}

$url = (new moodle_url('/course/format/ocmooc/views/forum.php', ['courseid' => $courseid, 'type' => $type]))->out(false);
$forum = new forum($courseid, $url);


$forum->setup_page();
echo $OUTPUT->header();

$forum->render_view();

echo $OUTPUT->footer();