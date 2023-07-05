<?php

namespace format_ocmooc\forms;

require_once("$CFG->libdir/formlibs.php");

class htmlpageform extends \moodleform {

    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('text', 'title', get_string('title', 'format_ocmooc'));
        $mform->setType('title', PARAM_TEXT);

        $mform->addElement('editor', 'content', get_string('content', 'format_ocmooc'));
        $mform->setType('content', PARAM_RAW);
    }
}