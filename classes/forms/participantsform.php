<?php

namespace format_ocmooc\forms;

require_once("$CFG->libdir/formslib.php");

class participantsform extends \moodleform {

    protected function definition() {
        $form = $this->_form;

        $form->addElement('checkbox', 'profilepicture', get_string('profilepicture', 'format_ocmooc'));
        $form->setDefault('profilepicture', true);

        $options = [
                0 => get_string('fullname', 'format_ocmooc'),
                1 => get_string('username', 'format_ocmooc'),
                2 => get_string('anonymized', 'format_ocmooc'),
        ];
        $form->addElement('select', 'namedisplay', get_string('namedisplay', 'format_ocmooc'), $options);
        $form->setDefault('namedisplay', 0);

        $form->addElement('checkbox', 'email', get_string('email', 'format_ocmooc'));
        $form->setDefault('email', true);

        $form->addElement('checkbox', 'town', get_string('town', 'format_ocmooc'));
        $form->setDefault('town', true);

        $form->addElement('checkbox', 'country', get_string('country', 'format_ocmooc'));
        $form->setDefault('country', true);

        $form->addElement('checkbox', 'badges', get_string('badges', 'format_ocmooc'));
        $form->setDefault('badges', true);

        $form->addElement('checkbox', 'roles', get_string('roles', 'format_ocmooc'));
        $form->setDefault('roles', false);

        $form->addElement('checkbox', 'groups', get_string('groups', 'format_ocmooc'));
        $form->setDefault('groups', false);

        $form->addElement('checkbox', 'lastaccess', get_string('lastaccess', 'format_ocmooc'));
        $form->setDefault('lastaccess', false);

        $this->add_action_buttons();
    }
}