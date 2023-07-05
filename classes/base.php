<?php

namespace format_ocmooc;

abstract class base {

    protected $courseid;

    protected $url;

    protected $mform;

    /**
     * @param $courseid
     */
    public function __construct($courseid, $url) {
        $this->courseid = $courseid;
        $this->url = $url;
    }

    public function render_view() {
        global $DB;

        $this->render_view_custom();

        $records = $DB->get_records('format_ocmooc_social', ['courseid' => $this->courseid]);
        print_object($records);
    }

    protected abstract function render_view_custom();

    public function render_editor() {
        echo \html_writer::tag('h2', get_string('editor', 'format_ocmooc'));

        $this->render_editor_custom();
        $this->show_form();
    }

    protected abstract function render_editor_custom();

    public abstract function render_overview();

    public function handle_form() {
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

    private function show_form() {
        $this->mform->display();
    }

}