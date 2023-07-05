<?php

namespace format_ocmooc\forms;

require_once("$CFG->libdir/formslib.php");

class socialform extends \moodleform {

    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('text', 'title', get_string('title', 'format_ocmooc'));
        $mform->setType('title', PARAM_TEXT);

        $mform->addElement('text', 'url', get_string('url', 'format_ocmooc'));
        $mform->setType('url', PARAM_URL);

        $options = [
                'youtube' => get_string('youtube', 'format_ocmooc'),
                'twitter' => get_string('twitter', 'format_ocmooc'),
                'facebook' => get_string('facebook', 'format_ocmooc'),
                'mastodon' => get_string('mastodon', 'format_ocmooc'),
                'linkedin' => get_string('linkedin', 'format_ocmooc'),
                'xing' => get_string('xing', 'format_ocmooc'),
                'other' => get_string('other', 'format_ocmooc'),
        ];
        $mform->addElement('select', 'type', get_string('type', 'format_ocmooc'), $options);

        $this->add_action_buttons();
    }
}