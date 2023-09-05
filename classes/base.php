<?php

namespace format_ocmooc;

abstract class base {

    protected $courseid;

    protected $url;

    protected $mform;

    protected $setdatadb;

    protected $setdataparams = null;

    protected $title;

    protected $heading;

    protected $context;

    /**
     * @param $courseid
     */
    public function __construct($courseid, $url) {
        $this->courseid = $courseid;
        $this->context = \context_course::instance($courseid);
        $this->url = $url;
    }

    public function setup_page() {
        global $PAGE, $DB;

        if (!isset($this->title)) {
            $this->title = get_string('default_title', 'format_ocmooc');
        }

        if (!isset($this->heading)) {
            $coursename = $DB->get_field('course', 'fullname', ['id' => $this->courseid]);
            $this->heading = format_string($coursename);
        }

        $PAGE->set_url($this->url);
        $PAGE->set_title($this->title);
        $PAGE->set_heading($this->heading);
        $PAGE->set_context($this->context);
    }

    public function render_view() {
        global $OUTPUT;

        $moocnav = \format_ocmooc\moocnav::get_moocnav_entries();
        echo $OUTPUT->render_from_template('format_ocmooc/local/moocnav/headernav', ['moocnav' => $moocnav]);

        $this->render_view_custom();
    }

    protected abstract function render_view_custom();

    public function render_editor($showheader = true) {
        $this->set_data();

        if ($showheader) {
            echo \html_writer::tag('h2', get_string('editor', 'format_ocmooc'));
        }

        $this->render_editor_custom();
        $this->show_form();
    }

    protected abstract function render_editor_custom();

    public abstract function render_overview();

    public function handle_form($reseturl = false) {
        if (!isset($this->mform)) {
            return;
        }

        $backurl = optional_param('backurl', null, PARAM_URL);
        if (isset($backurl)) {
            $url = new \moodle_url($backurl);
        } else if ($reseturl) {
            $url = $this->url;
            $url->remove_all_params();
            $url->params(['courseid' => $this->courseid]);
        } else{
            $url = new \moodle_url('/course/view.php',['id'=>$this->courseid]);
        }

        if ($this->mform->is_cancelled()) {
            redirect($url);
        } else if ($fromform = $this->mform->get_data()) {
            $handled = $this->handle_data($fromform);
            if ($handled) {
                redirect(
                        $url,
                        get_string('success', 'format_ocmooc'),
                        null,
                        \core\output\notification::NOTIFY_SUCCESS
                );
            } else {
                redirect(
                        $url,
                        get_string('failed', 'format_ocmooc'),
                        null,
                        \core\output\notification::NOTIFY_ERROR
                );
            }
        }
    }

    protected abstract function handle_data($data);

    protected function set_data() {
        global $DB;

        if (!isset($this->setdatadb) || !isset($this->mform)) {
            return;
        }

        $params = ['courseid' => $this->courseid];
        if (isset($this->setdataparams)) {
            $params = array_merge($params, $this->setdataparams);
        }

        $data = $DB->get_record($this->setdatadb, $params);
        $this->mform->set_data($data);
    }

    private function show_form() {
        if (!isset($this->mform)) {
            return;
        }

        $this->mform->display();
    }

}