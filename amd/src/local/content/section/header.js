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

import Header from 'core_courseformat/local/content/section/header';
import Exporter from 'format_ocmooc/local/courseeditor/exporter';

/**
 * Custom section header component for format_ocmooc.
 *
 * This class handles drag-and-drop logic for chapter and lection section headers,
 * allowing for reordering and restructuring sections within the course.
 *
 * @module     format_ocmooc/local/content/section/header
 * @class      format_ocmooc/local/content/section/header
 * @extends    core_courseformat/local/content/section/header
 * @copyright  2025 Oncampus GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
export default class extends Header {

    /**
     * Component initialization hook.
     *
     * @param {Object} descriptor Component descriptor with state and metadata
     */
    create(descriptor) {
        this.type = descriptor.type;
        this.cm = descriptor.cm;
        super.create(descriptor);
    }

    /**
     * Called when the reactive state is fully ready.
     *
     * @param {Object} state The current course state
     */
    stateReady(state) {
        this.configDragDrop(this.id, state, this.fullregion);

        // Only allow drag for non-zero sections.
        if (this.section?.number !== 0) {
            this.getDraggableData = this._getDraggableData;
        }
    }

    /**
     * Validate whether the incoming drop data is allowed on this header.
     *
     * @param {Object} dropdata Drag and drop metadata
     * @returns {boolean} True if drop is allowed, otherwise false
     */
    validateDropData(dropdata) {
        if (this.section?.number === 0) {
            return false;
        }

        if (this.element.dataset.number != 0 && !this.cm && dropdata.type === 'cm') {
            return false;
        }

        // Prevent dragging the first CM to itself.
        if (dropdata?.type === 'cm') {
            const firstcmid = this.section?.cmlist[0];
            return dropdata.id !== firstcmid;
        }

        // Chapters can accept chapters and lections.
        if (this.type === 'chapter') {
            return dropdata.type === 'chapter' || dropdata.type === 'lection';
        }

        // Lections can accept only lections.
        if (this.type === 'lection') {
            return dropdata.type === 'lection';
        }

        return false;
    }

    /**
     * Handle drop operation onto this header component.
     *
     * @param {Object} dropdata The dropped item metadata
     * @param {Event} event Optional drop event
     */
    drop(dropdata, event) {
        if (!dropdata?.type) {
            return;
        }

        if (dropdata.type === 'chapter' && this.type === 'chapter') {
            this.reactive.dispatch('chapterMove', [dropdata.id], this.id);
        } else if (dropdata.type === 'lection' && this.type === 'chapter') {
            this.reactive.dispatch('lectionMove2Chapter', [dropdata.id], this.id);
        } else if (dropdata.type === 'lection' && this.type === 'lection') {
            this.reactive.dispatch('lectionMove', [dropdata.id], this.id);
        } else if (dropdata.type === 'cm' && this.cm) {
            const firstcmid = this.section?.cmlist?.[0] ?? 0;
            this.reactive.dispatch('cmMove', [dropdata.id], this.id, firstcmid);
        }

        // Call parent handler if defined
        super.drop?.(dropdata, event);
    }

    /**
     * Provide draggable metadata to the reactive drag system.
     *
     * @returns {Object} Draggable metadata describing the section
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
     * Display visual indication for drop target.
     *
     * @param {Object} dropdata The incoming draggable metadata
     */
    showDropZone(dropdata) {
        if (['chapter', 'lection'].includes(dropdata?.type)) {
            this.element.classList.add(this.classes.DROPZONE);
        }
    }

    /**
     * Remove visual dropzone indication.
     */
    hideDropZone() {
        this.element.classList.remove(this.classes.DROPZONE);
    }
}
