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

import BaseSection from "core_courseformat/local/courseindex/section";
import SectionTitle from 'format_ocmooc/local/courseindex/sectiontitle';

/**
 * Course index section component.
 *
 * This component is used to control specific course section interactions like drag and drop.
 *
 * @module     format_ocmooc/local/courseindex/section
 * @copyright  2022 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
export default class Component extends BaseSection {
    // Extends course/format/amd/src/local/courseindex/section.js
    // Extends course/format/amd/src/local/courseeditor/dndsection.js

    /**
     * Static method to create a component instance form the mustahce template.
     *
     * @param {string} target the DOM main element or its ID
     * @param {object} selectors optional css selector overrides
     * @return {Component}
     */
    static init(target, selectors) {
        return new Component({
            element: document.getElementById(target),
            selectors,
        });
    }

    create() {
        super.create();
    }

    setType(type) {
        if (type == 'chapter') {
            this.type = type;
            this.types = [
                'chapter',
                'lection'
            ];
            this.cm = false;
        } else {
            this.type = type;
            this.types = [type];
            this.cm = true;
        }
    }

    stateReady(state) {
        this.configState(state);
        const sectionItem = this.getElement(this.selectors.SECTION_ITEM);
        // Drag and drop is only available for components compatible course formats.
        if (this.reactive.isEditing && this.reactive.supportComponents) {
            // Init the inner dragable element passing the full section as affected region.
            const titleitem = new SectionTitle({
                ...this,
                element: sectionItem,
                fullregion: this.element,
                type: this.type,
                cm: this.cm,
            });
            this.configDragDrop(titleitem);
        }
        // Check if the current url is the section url.
        const section = state.section.get(this.id);
        if (window.location.href == section.sectionurl.replace(/&amp;/g, "&")) {
            this.reactive.dispatch('setPageItem', 'section', this.id);
            sectionItem.scrollIntoView();
        }
    }

    validateDropData(dropdata) {
        if (this.types.includes(dropdata?.type) && this.reactive.sectionReturn != 0) {
            return false;
        }

        // We accept any course module.
        if (dropdata?.type === 'cm') {
            return this.cm ?? false;
        }

        // We accept any section but the section 0 or ourself
        if (this.types.includes(dropdata?.type)) {
            const sectionzeroid = this.course.sectionlist[0];
            return dropdata?.id != this.id && dropdata?.id != sectionzeroid && this.id != sectionzeroid;
        }
        return false;
    }

    /**
     * Display the component dropzone.
     *
     * @param {Object} dropdata the accepted drop data
     */
    showDropZone(dropdata) {
        if (dropdata.type == 'cm' && this.cm) {
            this.getLastCm()?.classList.add(this.classes.DROPDOWN);
        }
        if (this.types.includes(dropdata?.type)) {
            // The relative move of section depends on the section number.
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
     * Hide the component dropzone.
     */
    hideDropZone() {
        this.getLastCm()?.classList.remove(this.classes.DROPDOWN);
        this.element.classList.remove(this.classes.DROPUP);
        this.element.classList.remove(this.classes.DROPDOWN);
    }

    /**
     * Register state values and the drag and drop subcomponent.
     *
     * @param {BaseComponent} sectionitem section item component
     */
    configDragDrop(sectionitem) {
        super.configDragDrop(sectionitem);
    }

    drop(dropdata) {
        if (this.type == 'chapter') {
            if (dropdata.type == 'chapter') {
                this.reactive.dispatch('chapterMove', [dropdata.id], this.id);
            }
            if (dropdata.type == 'lection') {
                this.reactive.dispatch('lectionMove2Chapter', [dropdata.id], this.id);
            }
        } else {
            if (dropdata.type == 'lection') {
                this.reactive.dispatch('lectionMove', [dropdata.id], this.id);
            }
        }

        if (dropdata.type == 'cm' && !this.cm) {
            return;
        }
        super.drop(dropdata);
    }
}
