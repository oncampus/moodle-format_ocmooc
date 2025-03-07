<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace format_ocmooc\forms;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

/**
 * Form for configuring participants display options
 *
 * @package    format_ocmooc
 * @copyright  2025 oncampus GmbH <info@oncampus.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class participantsform extends \moodleform {
    /**
     * Define the form elements
     */
    protected function definition() {
        $form = $this->_form;

        $form->addElement('checkbox', 'profilepicture', get_string('userpic'));
        $form->setDefault('profilepicture', true);

        $options = [
                0 => get_string('fullname'),
                1 => get_string('username'),
                2 => get_string('anonymized', 'format_ocmooc'),
                3 => get_string('shortened_surname', 'format_ocmooc'),
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
