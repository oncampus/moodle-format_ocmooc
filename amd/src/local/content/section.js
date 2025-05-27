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

import Section from 'core_courseformat/local/content/section';
import Header from 'format_ocmooc/local/content/section/header';

/**
 * Section override class for custom course format.
 *
 * This class extends the core course format section component to support
 * nested "chapter" and "lection" section types and custom drag-and-drop logic.
 *
 * @module     format_ocmooc/local/content/section
 * @class      format_ocmooc/local/content/section
 * @extends    core_courseformat/local/content/section
 * @copyright  2025 Oncampus GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
export default class extends Section {

    /**
     * Triggered when the reactive state is ready.
     *
     * Registers the component state and initializes the drag-and-drop header.
     *
     * @param {Object} state The current reactive state object
     */
    stateReady(state) {
        this.configState(state);

        if (this.reactive.isEditing && this.reactive.supportComponents) {
            const sectionItem = this.getElement(this.selectors.SECTION_ITEM);
            if (sectionItem) {
                const headerComponent = new Header({
                    ...this,
                    element: sectionItem,
                    fullregion: this.element,
                    type: this.type,
                });
                this.configDragDrop(headerComponent);
            }
        }
    }

    /**
     * Define reactive watchers for the section.
     *
     * @returns {Array} List of watchers
     */
    getWatchers() {
        return [
            {watch: `section[${this.id}].title:updated`, handler: this._refreshSectionTitle},
        ];
    }

    /**
     * Handles drop events for course modules and sections.
     *
     * @param {Object} dropdata Data object containing drop context
     */
    drop(dropdata) {
        if (dropdata.type === 'cm' && this.cm) {
            this.reactive.dispatch('cmMove', [dropdata.id], this.section.id, 0);
            return;
        }

        super.drop(dropdata);
    }

    /**
     * Validates whether a given dropdata object is acceptable.
     *
     * @param {Object} dropdata Drop data object
     * @returns {boolean} Whether the drop is valid
     */
    validateDropData(dropdata) {
        if (this.section?.number === 0) {
            return false;
        }

        if (this.types.includes(dropdata?.type) && this.reactive.sectionReturn !== 0) {
            return false;
        }

        if (dropdata?.type === 'cm') {
            return this.cm ?? false;
        }

        if (this.types.includes(dropdata?.type)) {
            const sectionzeroid = this.course.sectionlist[0];
            return dropdata?.id !== this.id &&
                   dropdata?.id !== sectionzeroid &&
                   this.id !== sectionzeroid;
        }

        return false;
    }

    /**
     * Display visual dropzone styling based on the dragged item type.
     *
     * @param {Object} dropdata Drop data object
     */
    showDropZone(dropdata) {
        if (dropdata.type === 'cm' && this.cm) {
            this.getLastCm()?.classList.add(this.classes.DROPDOWN);
        }

        if (this.types.includes(dropdata?.type)) {
            if (this.section.number > dropdata.number) {
                this.element.classList.remove(this.classes.DROPUP);
                this.element.classList.add(this.classes.DROPDOWN);
            } else {
                this.element.classList.add(this.classes.DROPUP);
                this.element.classList.remove(this.classes.DROPDOWN);
            }
        }
    }

    /**
     * Get the DOM element of the last course module in the section.
     *
     * @returns {HTMLElement|null} Last course module element or null
     */
    getLastCm() {
        const cmIds = this.section?.cmlist ?? [];
        if (!cmIds.length) {
            return null;
        }

        const lastId = cmIds[cmIds.length - 1];
        return this.getElement(this.selectors.CM, lastId);
    }

    /**
     * Reset any dropzone UI styles on the section.
     */
    hideDropZone() {
        this.getLastCm()?.classList.remove(this.classes.DROPDOWN);
        this.element.classList.remove(this.classes.DROPUP);
        this.element.classList.remove(this.classes.DROPDOWN);
    }

    /**
     * Refresh the section title in the content area after a title update.
     *
     * Ensures inplace editable structure and icon are preserved.
     *
     * @param {Object} param
     * @param {Object} param.element Updated section state object
     */
    _refreshSectionTitle({element}) {
        const titleContainer = this.getElement('[data-for="section_title"]');
        if (!titleContainer) {
            return;
        }

        const inplaceWrapper = titleContainer.querySelector('.inplaceeditable');
        const quickEditLink = inplaceWrapper?.querySelector('[data-inplaceeditablelink]');
        if (!inplaceWrapper || !quickEditLink) {
            return;
        }

        const title = element.title?.trim() || `Abschnitt ${element.number}`;
        inplaceWrapper.setAttribute('data-value', title);
        quickEditLink.setAttribute('title', 'Abschnittsname bearbeiten');

        const icon = quickEditLink.querySelector('.quickediticon');
        quickEditLink.textContent = '';
        quickEditLink.append(title);
        if (icon) {
            quickEditLink.appendChild(icon);
        }
    }
}