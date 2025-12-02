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
 * Search form for the OCMOOC format
 *
 * @package   format_ocmooc
 * @copyright 2025 oncampus GmbH <info@oncampus.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class searchform extends \moodleform {
    /**
     * Define the form elements
     */
    protected function definition() {
        $form = $this->_form;

        $form->addElement('text', 'searchtext', null);
        $form->setType('searchtext', PARAM_TEXT);

        $this->add_action_buttons(false, get_string('search'));
    }
}
