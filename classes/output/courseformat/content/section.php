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

namespace format_ocmooc\output\courseformat\content;

use core_courseformat\output\local\content\section as section_base;
use renderer_base;
use stdClass;

/**
 * Content output class for the OCMOOC course format section.
 *
 * @package    format_ocmooc
 * @copyright  2025 oncampus GmbH <support@oncampus.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section extends section_base {
    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output Renderer base.
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        global $PAGE;
        $data = parent::export_for_template($output);
        if ($PAGE->user_is_editing()) {
            $data->insertafter = false;
        }
        if ($this->section->parent == 0) {
            if ($this->section->section > 0) {
                $data->insertafter = true;
            }
            $data->ischapter = true;
        }
        return $data;
    }

    /**
     * Returns the template name for rendering.
     *
     * @param \renderer_base $renderer The renderer instance.
     * @return string The template name.
     */
    public function get_template_name(\renderer_base $renderer): string {
        return "format_ocmooc/local/content/section";
    }
}
