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

import BaseSection from 'core_courseformat/local/courseindex/section';
import SectionTitle from 'format_ocmooc/local/courseindex/sectiontitle';

/**
 * Custom course index section component for the OC MOOC format.
 *
 * Provides extended drag-and-drop behavior for hierarchical sections (chapters and lections).
 *
 * @module     format_ocmooc/local/courseindex/section
 * @class      format_ocmooc/local/courseindex/section
 * @extends    core_courseformat/local/courseindex/section
 * @copyright  2025 Oncampus GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
export default class Component extends BaseSection {

    /**
     * Static initializer to create a section component instance.
     *
     * @param {string} target DOM ID of the section root element
     * @param {object} selectors Optional CSS selector overrides
     * @return {Component}
     */
    static init(target, selectors) {
        return new Component({
            element: document.getElementById(target),
            selectors,
        });
    }

    /**
     * Component creation hook.
     */
    create() {
        super.create();
    }

    /**
     * Set the type of this section (chapter or lection).
     *
     * @param {string} type Section type: 'chapter' or 'lection'
     */
    setType(type) {
        if (type === 'chapter') {
            this.type = type;
            this.types = ['chapter', 'lection'];
            this.cm = false;
        } else {
            this.type = type;
            this.types = [type];
            this.cm = true;
        }
    }

    /**
     * Called when the state is ready.
     * Initializes drag-and-drop if supported and sets the current page section.
     *
     * @param {object} state The reactive state
     */
    stateReady(state) {
        this.configState(state);

        const sectionItem = this.getElement(this.selectors.SECTION_ITEM);
        if (this.reactive.isEditing && this.reactive.supportComponents) {
            const titleitem = new SectionTitle({
                ...this,
                element: sectionItem,
                fullregion: this.element,
                type: this.type,
                cm: this.cm,
            });
            this.configDragDrop(titleitem);
        }

        const section = state.section.get(this.id);
        if (window.location.href === section.sectionurl.replace(/&amp;/g, '&')) {
            this.reactive.dispatch('setPageItem', 'section', this.id);
            sectionItem.scrollIntoView();
        }
    }

    /**
     * Validate if the provided item can be dropped on this section.
     *
     * @param {object} dropdata The draggable data object
     * @returns {boolean}
     */
    validateDropData(dropdata) {
        if (this.types.includes(dropdata?.type) && this.reactive.sectionReturn !== 0) {
            return false;
        }

        if (dropdata?.type === 'cm') {
            return this.cm ?? false;
        }

        const sectionzeroid = this.course.sectionlist[0];
        if (this.types.includes(dropdata?.type)) {
            return dropdata?.id !== this.id &&
                dropdata?.id !== sectionzeroid &&
                this.id !== sectionzeroid;
        }

        return false;
    }

    /**
     * Display visual indication for an available drop zone.
     *
     * @param {object} dropdata The draggable item data
     */
    showDropZone(dropdata) {
        if (dropdata?.type === 'cm' && this.cm) {
            const lastCm = this.getLastCm();
            if (lastCm) {
                lastCm.classList.add(this.classes.DROPDOWN);
            }
        }

        if (this.type === 'chapter') {
            if (dropdata?.type === 'chapter') {
                if (this.section.number > dropdata.number) {
                    this.element.classList.remove(this.classes.DROPUP);
                    this.element.classList.add(this.classes.DROPDOWN);
                } else {
                    this.element.classList.add(this.classes.DROPUP);
                    this.element.classList.remove(this.classes.DROPDOWN);
                }
            }

            if (dropdata?.type === 'lection') {
                this.element.classList.add(this.classes.DROPZONE);
            }
        } else {
            if (this.section.parent !== dropdata.parent) {
                this.element.classList.remove(this.classes.DROPUP);
                this.element.classList.add(this.classes.DROPDOWN);
            } else if (this.section.number > dropdata.number) {
                this.element.classList.remove(this.classes.DROPUP);
                this.element.classList.add(this.classes.DROPDOWN);
            } else {
                this.element.classList.add(this.classes.DROPUP);
                this.element.classList.remove(this.classes.DROPDOWN);
            }
        }
    }

    /**
     * Retrieve the DOM element of the last CM in this section.
     *
     * @returns {HTMLElement|null}
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
     * Remove all drop zone indicators.
     */
    hideDropZone() {
        this.getLastCm()?.classList.remove(this.classes.DROPDOWN);
        this.element.classList.remove(this.classes.DROPUP);
        this.element.classList.remove(this.classes.DROPDOWN);
        this.element.classList.remove(this.classes.DROPZONE);
    }

    /**
     * Register the drag-and-drop handler for the given section component.
     *
     * @param {BaseComponent} sectionitem The draggable section title component
     */
    configDragDrop(sectionitem) {
        super.configDragDrop(sectionitem);
    }

    /**
     * Dispatch the correct action for the dropped item.
     *
     * @param {object} dropdata The draggable item
     */
    drop(dropdata) {
        if (this.type === 'chapter') {
            if (dropdata.type === 'chapter') {
                this.reactive.dispatch('chapterMove', [dropdata.id], this.id);
            }
            if (dropdata.type === 'lection') {
                this.reactive.dispatch('lectionMove2Chapter', [dropdata.id], this.id);
            }
        } else {
            if (dropdata.type === 'lection') {
                this.reactive.dispatch('lectionMove', [dropdata.id], this.id);
            }
        }

        if (dropdata.type === 'cm' && !this.cm) {
            this.reactive.dispatch('cmMove', [dropdata.id], this.id, 0);
            return;
        }

        super.drop(dropdata);
    }
}
