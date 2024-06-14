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
 * Basic overriding Section class for chapter and lection.
 * Preparing all sharing modifications here!
 */
export default class extends Section {

    stateReady(state) {
        this.configState(state);
        // Drag and drop is only available for components compatible course formats.
        if (this.reactive.isEditing && this.reactive.supportComponents) {
            // Section zero and other formats sections may not have a title to drag.
            const sectionItem = this.getElement(this.selectors.SECTION_ITEM);
            if (sectionItem) {
                // Init the inner dragable element.
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

    validateDropData(dropdata) {
        if (dropdata?.type === this.type && this.reactive.sectionReturn != 0) {
            return false;
        }

        // We accept any course module.
        if (dropdata?.type === 'cm') {
            return true;
        }

        // We accept any section but the section 0 or ourself
        if (dropdata?.type === this.type) {
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
        if (dropdata.type == 'cm') {
            this.getLastCm()?.classList.add(this.classes.DROPDOWN);
        }
        if (dropdata.type == this.type) {
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

}