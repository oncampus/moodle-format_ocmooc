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

use renderer_base;
use stdClass;

/**
 * Activity chooser button for OC MOOC format.
 *
 * Removes the "subsection" option from the add-content dropdown because
 * this format uses its own chapter/lection hierarchy and does not support
 * Moodle's native subsection module.
 *
 * @package     format_ocmooc
 * @copyright   2025 oncampus GmbH <support@oncampus.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activitychooserbutton extends \core_courseformat\output\local\content\activitychooserbutton {
    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return stdClass data context for a mustache template
     */
    public function export_for_template(renderer_base $output): stdClass {
        $data = parent::export_for_template($output);

        if (!empty($data->actionlinks)) {
            $data->actionlinks = array_values(array_filter(
                $data->actionlinks,
                function ($link) {
                    foreach (($link->attributes ?? []) as $attr) {
                        if (($attr['name'] ?? '') === 'data-modname' && ($attr['value'] ?? '') === 'subsection') {
                            return false;
                        }
                    }
                    return true;
                }
            ));
            $data->hasactionlinks = !empty($data->actionlinks);
        }

        return $data;
    }
}
