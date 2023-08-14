<?php

namespace format_ocmooc;

abstract class base {

    protected $courseid;

    protected $url;

    protected $mform;

    protected $setdatadb;

    /**
     * @param $courseid
     */
    public function __construct($courseid, $url) {
        $this->courseid = $courseid;
        $this->url = $url;
    }

    public function render_view() {
        global $OUTPUT;

        $moocnav = \format_ocmooc\moocnav::get_moocnav_entries();
        echo $OUTPUT->render_from_template('format_ocmooc/local/moocnav/headernav', ['moocnav' => $moocnav]);

        $this->render_view_custom();
    }

    protected abstract function render_view_custom();

    public function render_editor() {
        $this->set_data();

        echo \html_writer::tag('h2', get_string('editor', 'format_ocmooc'));

        $this->render_editor_custom();
        $this->show_form();
    }

    protected abstract function render_editor_custom();

    public abstract function render_overview();

    public function handle_form() {
        if (!isset($this->mform))
            return;

        if ($this->mform->is_cancelled()) {
            redirect($this->url);
        } else if ($fromform = $this->mform->get_data()) {
            $handled = $this->handle_data($fromform);
            if ($handled) {
                redirect(
                        $this->url,
                        get_string('success', 'format_ocmooc'),
                        null,
                        \core\output\notification::NOTIFY_SUCCESS
                );
            } else {
                redirect(
                        $this->url,
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

        $data = $DB->get_record($this->setdatadb, ['courseid' => $this->courseid]);
        $this->mform->set_data($data);
    }

    private function show_form() {
        if (!isset($this->mform))
            return;

        $this->mform->display();
    }

}