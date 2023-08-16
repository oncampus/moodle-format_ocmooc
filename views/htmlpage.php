<?php

require_once(__DIR__ . "/../../../../config.php");

$courseid = required_param('courseid', PARAM_INT);
$id = required_param('id', PARAM_INT);
$coursecontext = context_course::instance($courseid);
$course = get_course($courseid);

require_login($courseid);

if ($course->format != 'ocmooc') {
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
}

$url = new moodle_url('/course/format/ocmooc/views/htmlpage.php', ['courseid' => $courseid, 'id' => $id]);
$htmlpage = new \format_ocmooc\htmlpage($courseid, $url, null, $id);

$htmlpage->setup_page();
echo $OUTPUT->header();

$htmlpage->render_view();

echo $OUTPUT->footer();