<?php

require_once(__DIR__ . "/../../../../config.php");

$courseid = required_param('courseid', PARAM_INT);
$coursecontext = context_course::instance($courseid);
$course = get_course($courseid);
$action = optional_param('action', null, PARAM_TEXT);
$id = optional_param('id', null, PARAM_INT);
$confirm = optional_param('confirm', null, PARAM_ALPHANUM);
$backurl = optional_param('backurl', null, PARAM_URL);

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

$urlparams = ['courseid' => $courseid];

if (isset($action)) {
    $urlparams['action'] = $action;
}

if (isset($id)) {
    $urlparams['id'] = $id;
}

if (isset($backurl)) {
    $urlparams['backurl'] = $backurl;
}

$url = new moodle_url('/course/format/ocmooc/edit/htmlpage.php', $urlparams);
$htmlpage = new \format_ocmooc\htmlpage($courseid, $url, $action, $id);

if (isset($action)) {
    if ($action == 'create' || $action == 'edit') {
        $htmlpage->handle_form(true);
    } else if ($action == 'delete' && $confirm == md5($id . $courseid)) {
        $htmlpage->delete();
    }
}

$PAGE->set_url($url);
$PAGE->set_title(get_string('htmlpage', 'format_ocmooc'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($coursecontext);

echo $OUTPUT->header();

if (isset($action)) {
    if ($action == 'delete') {

    } else {
        $htmlpage->render_editor();
    }
} else {
    $htmlpage->render_overview();
}

echo $OUTPUT->footer();