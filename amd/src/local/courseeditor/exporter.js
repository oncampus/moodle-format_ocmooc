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

import Exporter from 'core_courseformat/local/courseeditor/exporter';

/**
 * Custom exporter extending the default core course format exporter.
 *
 * Adds support for hierarchical section exporting and customized draggable data
 * for chapter and lection sections, as well as for course modules.
 *
 * @module     format_ocmooc/local/courseeditor/exporter
 * @class      format_ocmooc/local/courseeditor/exporter
 * @extends    core_courseformat/local/courseeditor/exporter
 * @copyright  2025 Oncampus GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
export default class extends Exporter {

    /**
     * Export a section including its child sections.
     *
     * @param {Object} state The full reactive state
     * @param {Object} sectioninfo The section data from state
     * @returns {Object} Renderable data object for the section
     */
    section(state, sectioninfo) {
        const children = sectioninfo.children;
        const section = super.section(state, sectioninfo);

        section.children = [];
        if (children && children.length) {
            for (const element of children) {
                section.children.push(this.section(state, element));
            }
        }

        return section;
    }

    /**
     * Export a course module with additional draggable metadata.
     *
     * @param {Object} state The full reactive state
     * @param {Object} cminfo The course module data from state
     * @returns {Object} Renderable data object for the course module
     */
    cm(state, cminfo) {
        const cm = super.cm(state, cminfo);
        cm.draggabledata = {
            type: 'cm',
            id: cminfo.id,
            sectionid: cminfo.sectionid,
            name: cminfo.name,
        };
        return cm;
    }

    /**
     * Generate draggable data for a chapter section.
     *
     * @param {Object} state The full reactive state
     * @param {number} sectionid The ID of the section
     * @returns {Object|null} Draggable data object
     */
    chapterDraggableData(state, sectionid) {
        return this.createDraggableData(state, sectionid, 'chapter');
    }

    /**
     * Generate draggable data for a lection section.
     *
     * @param {Object} state The full reactive state
     * @param {number} sectionid The ID of the section
     * @returns {Object|null} Draggable data object
     */
    lectionDraggableData(state, sectionid) {
        return this.createDraggableData(state, sectionid, 'lection');
    }

    /**
     * Generic method to build draggable data for any custom section type.
     *
     * @param {Object} state The full reactive state
     * @param {number} sectionid The section ID
     * @param {string} type The section type ('chapter' or 'lection')
     * @returns {Object|null} Draggable data object or null if section not found
     */
    createDraggableData(state, sectionid, type) {
        const sectioninfo = state.section.get(sectionid);
        if (!sectioninfo) {
            return null;
        }

        return {
            type: type,
            id: sectioninfo.id,
            name: sectioninfo.name,
            number: sectioninfo.number,
            parent: sectioninfo.parent,
        };
    }
}
