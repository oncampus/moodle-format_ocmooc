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

$url = new moodle_url('/course/format/ocmooc/edit/participants.php', ['courseid' => $courseid]);
$participants = new \format_ocmooc\participants($courseid, $url);
$participants->handle_form();

$PAGE->set_url($url);
$PAGE->set_title(get_string('participants', 'format_ocmooc'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($coursecontext);

echo $OUTPUT->header();

$participants->render_editor();

echo $OUTPUT->footer();