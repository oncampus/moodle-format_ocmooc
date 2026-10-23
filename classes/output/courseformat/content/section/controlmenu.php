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

use core\output\action_menu\link;
use core\output\action_menu\link_secondary;
use core\output\pix_icon;
use core\url;
use core_courseformat\output\local\content\section\controlmenu as core_controlmenu;

/**
 * Section control menu override for format_ocmooc.
 *
 * Removes the duplicate action for chapters and shows sections in the learner view.
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
        if (!empty($controls['delete']) && is_array($controls['delete'])) {
            $controls['delete']['url'] = new \moodle_url(
                '/course/format/ocmooc/sectionhandler.php',
                [
                    'action' => 'confirm-delete',
                    'courseid' => $this->format->get_course()->id,
                    'sectionnum' => $section->section,
                    'sesskey' => sesskey(),
                ]
            );
            $controls['delete']['attr'] = array_merge(
                $controls['delete']['attr'] ?? [],
                ['class' => 'icon editing_delete text-danger']
            );
        }
        return $controls;
    }

    /**
     * Retrieves the view item. It leaves the edit mode and opens the section in the learner view.
     *
     * @return link|null The menu item if applicable, otherwise null.
     */
    protected function get_section_view_item(): ?link {
        if ($this->format->get_sectionid() == $this->section->id) {
            return null;
        }
        return new link_secondary(
            url: new url(
                '/course/format/ocmooc/sectionhandler.php',
                [
                    'action' => 'view',
                    'courseid' => $this->format->get_course()->id,
                    'sectionnum' => $this->section->section,
                    'sesskey' => sesskey(),
                ]
            ),
            icon: new pix_icon('i/viewsection', ''),
            text: get_string('view'),
            attributes: ['class' => 'view'],
        );
    }
}
