<?php

namespace format_ocmooc\forms;

require_once("$CFG->libdir/formslib.php");

class participantsform extends \moodleform {

    protected function definition() {
        $form = $this->_form;

        $form->addElement('checkbox', 'profilepicture', get_string('userpic'));
        $form->setDefault('profilepicture', true);

        $options = [
                0 => get_string('fullname'),
                1 => get_string('username'),
                2 => get_string('anonymized', 'format_ocmooc'),
        ];
        $form->addElement('select', 'namedisplay', get_string('namedisplay', 'format_ocmooc'), $options);
        $form->setDefault('namedisplay', 2);

        $form->addElement('checkbox', 'email', get_string('email'));
        $form->setDefault('email', false);

        $form->addElement('checkbox', 'town', get_string('city'));
        $form->setDefault('town', true);

        $form->addElement('checkbox', 'country', get_string('country'));
        $form->setDefault('country', true);

        $form->addElement('checkbox', 'badges', get_string('badges'));
        $form->setDefault('badges', true);

        $form->addElement('checkbox', 'roles', get_string('roles'));
        $form->setDefault('roles', false);

        $form->addElement('checkbox', 'groups', get_string('groups'));
        $form->setDefault('groups', false);

        $form->addElement('checkbox', 'lastaccess', get_string('lastaccess'));
        $form->setDefault('lastaccess', false);

        $this->add_action_buttons();
    }
}