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

namespace format_ocmooc\output\courseformat\content\section;

use core_courseformat\output\local\content\section\controlmenu as core_controlmenu;

/**
 * Section control menu override for format_ocmooc.
 *
 * Removes the duplicate action for chapters.
 *
 * @package    format_ocmooc
 * @copyright  2025 oncampus GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class controlmenu extends core_controlmenu {
    /**
     * Generate the edit control items of a section.
     *
     * @return array
     */
    public function section_control_items() {
        $controls = parent::section_control_items();
        $section = $this->section;
        if ($section->section && $section->parent == 0) {
            unset($controls['duplicate']);
        }
        if (isset($controls['delete'])) {
            $controls['delete']['url'] = new \moodle_url(
                '/course/format/ocmooc/sectionhandler.php',
                [
                    'action' => 'confirm-delete',
                    'courseid' => $this->format->get_course()->id,
                    'sectionnum' => $section->section,
                    'sesskey' => sesskey(),
                ]
            );
            $controls['delete']['attr'] = [
                'class' => 'icon editing_delete text-danger',
            ];
        }
        return $controls;
    }
}
