<?php

require_once(__DIR__ . "/../../../../config.php");

$courseid = required_param('courseid', PARAM_INT);
$coursecontext = context_course::instance($courseid);
$course = get_course($courseid);
require_login($courseid);
$PAGE->set_pagelayout('report');

if ($course->format != 'ocmooc') {
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
}

$url = new moodle_url('/course/format/ocmooc/views/participants.php', ['courseid' => $courseid]);
$participants = new \format_ocmooc\participants($courseid, $url);

$participants->check_guest_access();
$participants->update_missing_locations($courseid);
$participant_locations = $participants->get_course_participant_locations($courseid);
$templatecontext = [
    'markers' => $participant_locations
];

$participants->setup_page();
echo $OUTPUT->header();
$participants->render_view();
echo $OUTPUT->render_from_template('format_ocmooc/map/map', $templatecontext);
echo $OUTPUT->footer();