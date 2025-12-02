// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

import CmItem from 'core_courseformat/local/content/section/cmitem';

/**
 * Custom course module (CM) component.
 *
 * Extends the core CM item to handle drag-and-drop behavior
 * for positioning a CM after the current one.
 *
 * @module     format_ocmooc/local/content/section/cmitem
 * @class      format_ocmooc/local/content/section/cmitem
 * @extends    core_courseformat/local/content/section/cmitem
 * @copyright  2025 Oncampus GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
export default class extends CmItem {

    /**
     * Validate whether the current item can accept the dropped item.
     *
     * Only allows course modules (CMs) to be dropped after this item.
     *
     * @param {Object} dropdata The object describing the dragged item
     * @returns {boolean} Whether the drop is valid
     */
    validateDropData(dropdata) {
        return dropdata?.type === 'cm';
    }

    /**
     * Handle the drop operation.
     *
     * Moves the dropped CM behind the current CM item in the same section.
     *
     * @param {Object} dropdata The drop data containing the source CM ID
     */
    drop(dropdata) {
        if (!dropdata?.id || !this.reactive) {
            return;
        }

        this.reactive.dispatch('cmMove', [dropdata.id], this.section.id, this.id);
    }
}