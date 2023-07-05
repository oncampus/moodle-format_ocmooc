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

$PAGE->set_url($url);
$PAGE->set_title(get_string('social', 'format_ocmooc'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($coursecontext);

echo $OUTPUT->header();

$moocnav = \format_ocmooc\moocnav::get_moocnav_entries();
echo $OUTPUT->render_from_template('format_ocmooc/local/moocnav/headernav', ['moocnav' => $moocnav]);

$social->render_view();
echo $OUTPUT->footer();