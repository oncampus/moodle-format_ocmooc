<?php

namespace format_ocmooc\forms;

require_once("$CFG->libdir/formslib.php");

class searchform extends \moodleform {

    protected function definition() {
        $form = $this->_form;

        $form->addElement('text', 'searchtext', null);
        $form->setType('searchtext', PARAM_TEXT);

        $this->add_action_buttons(false, get_string('search'));
    }
}