<?php

require_once(__DIR__ . "/../../../../config.php");

$courseid = required_param('courseid', PARAM_INT);
$coursecontext = context_course::instance($courseid);
$course = get_course($courseid);

require_login($courseid);

if ($course->format != 'ocmooc') {
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
}

$url = new moodle_url('/course/format/ocmooc/views/forumlist.php', ['courseid' => $courseid]);
$forumlist = new \format_ocmooc\forumlist($courseid, $url);

$forumlist->handle_form();

$forumlist->setup_page();
echo $OUTPUT->header();

$forumlist->render_view();

$forumlist->render_editor(false);

echo $OUTPUT->footer();