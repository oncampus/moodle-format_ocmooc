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

/**
 * Course index section title component for OC MOOC.
 *
 * This component enables drag-and-drop functionality for sections
 * (chapters and lections) in the course index navigation.
 *
 * @module     format_ocmooc/local/courseindex/sectiontitle
 * @class      format_ocmooc/local/courseindex/sectiontitle
 * @extends    core_courseformat/local/courseindex/sectiontitle
 * @copyright  2025 Oncampus GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import SectionTitle from 'core_courseformat/local/courseindex/sectiontitle';
import Exporter from 'format_ocmooc/local/courseeditor/exporter';

/**
 * Extended section title class for OC MOOC course format.
 */
export default class extends SectionTitle {

    /**
     * Component creation hook.
     *
     * @param {object} descriptor Initial configuration for the component
     */
    create(descriptor) {
        this.type = descriptor.type;
        this.cm = descriptor.cm;

        super.create(descriptor);

        // Provide drag-and-drop metadata via overridden method.
        this.getDraggableData = this._getDraggableData;
    }

    /**
     * Prepare draggable data for this section.
     *
     * @returns {object|null} The draggable metadata for this section
     */
    _getDraggableData() {
        const exporter = new Exporter();

        if (this.type === 'chapter') {
            return exporter.chapterDraggableData(this.reactive.state, this.id);
        } else {
            return exporter.lectionDraggableData(this.reactive.state, this.id);
        }
    }

    /**
     * Validate incoming draggable data to determine if it's accepted.
     *
     * @param {object} dropdata The dragged item metadata
     * @returns {boolean} True if valid drop, otherwise false
     */
    validateDropData(dropdata) {
        if (dropdata.type === 'cm') {
            return this.cm ?? false;
        }

        // Fallback to default validation from superclass.
        return super.validateDropData(dropdata);
    }
}
