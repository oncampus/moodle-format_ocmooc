<?php

require_once(__DIR__ . "/../../../../config.php");

$courseid = required_param('courseid', PARAM_INT);
$coursecontext = context_course::instance($courseid);
$course = get_course($courseid);

require_login($courseid);

if (!has_capability('format/ocmooc:edit', $coursecontext)) {
    redirect(
            '/index.php',
            get_string('error:nocapability', 'format_ocmooc'),
            null,
            \core\output\notification::NOTIFY_ERROR
    );
}

if ($course->format != 'ocmooc') {
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
}

$url = new moodle_url('/course/format/ocmooc/edit/social.php', ['courseid' => $courseid]);
$social = new \format_ocmooc\social($courseid, $url);
$social->handle_form();

$PAGE->set_url($url);
$PAGE->set_title(get_string('social', 'format_ocmooc'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($coursecontext);

echo $OUTPUT->header();
$social->render_editor();

$social->render_overview();
echo $OUTPUT->footer();