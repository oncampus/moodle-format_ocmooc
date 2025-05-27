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

import {getCurrentCourseEditor} from 'core_courseformat/courseeditor';
import BaseCourseindex from 'core_courseformat/local/courseindex/courseindex';
import Exporter from "format_ocmooc/local/courseeditor/exporter";

/**
 * Course index main component.
 *
 * @module     format_ocmooc/local/courseindex/courseindex
 * @copyright  2022 Marina Glancu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
export default class Component extends BaseCourseindex {

    /**
     * Static method to create a component instance from the mustache template.
     *
     * @param {string} target the DOM main element ID
     * @param {object} selectors optional CSS selector overrides
     * @return {Component}
     */
    static init(target, selectors) {
        const courseEditor = getCurrentCourseEditor();
        courseEditor.getExporter = () => new Exporter(courseEditor);

        return new Component({
            element: document.getElementById(target),
            reactive: courseEditor,
            selectors,
        });
    }

    /**
     * Component constructor.
     *
     * @param {object} descriptor
     */
    create(descriptor) {
        super.create(descriptor);
        this.name = 'course_format_ocmooc_courseindex';
        this.selectors.COURSE_SUBSECTIONLIST = `[data-for='subsectionlist']`;
        this.selectors.CHAPTER = `[data-for='chapter']`;
        this.selectors.LECTION = `[data-for='lection']`;
        this.sections = {};
    }

    /**
     * Called when state is fully available.
     *
     * @param {object} state
     */
    stateReady(state) {
        const exporter = this.reactive.getExporter();

        for (const id of state.course.sectionlist ?? []) {
            const section = state.section.get(id);
            const data = exporter.section(state, section);

            const fakeelement = document.createElement('div');
            fakeelement.classList.add('bg-pulse-grey', 'w-100');
            fakeelement.innerHTML = '&nbsp;';
            this.sections[id] = fakeelement;
            this.element.appendChild(fakeelement);

            this.renderComponent(fakeelement, 'format_ocmooc/local/courseindex/section', data)
                .then(component => {
                    const newelement = component.getElement();
                    this.sections[id] = newelement;
                    fakeelement.replaceWith(newelement);
                });
        }
    }

    getWatchers() {
        return [
            {watch: `section.indexcollapsed:updated`, handler: this._refreshSectionCollapsed},
            {watch: `cm:created`, handler: this._createCm},
            {watch: `cm:deleted`, handler: this._deleteCm},
            {watch: `section:deleted`, handler: this._deleteSection},
            {watch: `course.pageItem:created`, handler: this._refreshPageItem},
            {watch: `course.pageItem:updated`, handler: this._refreshPageItem},
            {watch: `course.sectionlist:updated`, handler: this._refreshCourseSectionlist},
            {watch: `section.cmlist:updated`, handler: this._refreshSectionCmlist},
            {watch: `course.hierarchy:updated`, handler: this._refreshCourseSectionlist},
        ];
    }

    /**
     * Refresh the courseindex when drag and drop or deleting a cm item.
     *
     * @param {Event} event the triggered event
     */
    _refreshSectionCmlist({element}) {
        const cmlist = element.cmlist ?? [];
        const listparent = this.getElement(this.selectors.SECTION_CMLIST, element.id);
        if (!listparent) {
            return;
        }
        element.cmlist?.forEach(cmid => {
            const cmElement = this.getElement(this.selectors.CM, cmid);
            if (cmElement) {
                this.cms[cmid] = cmElement;
            }
        });

        this._fixOrderTiles(listparent, cmlist, this.cms);
    }


    /**
     * Setup sections toggler.
     *
     * Toggler click is delegated to the main course index element because new sections can
     * appear at any moment and this way we prevent accidental double bindings.
     *
     * @param {Event} event the triggered event
     */
    _sectionTogglers(event) {
        const sectionlink = event.target.closest(this.selectors.TOGGLER);
        const closestCollapse = event.target.closest(this.selectors.COLLAPSE);
        const isChevron = closestCollapse?.closest(this.selectors.SECTION_ITEM);

        if (sectionlink || isChevron) {
            const lection = event.target.closest(this.selectors.LECTION);
            const chapter = event.target.closest(this.selectors.CHAPTER);
            const toggler = lection !== null
                ? lection.querySelector(this.selectors.COLLAPSE)
                : chapter.querySelector(this.selectors.COLLAPSE);
            const isCollapsed = toggler?.classList.contains(this.classes.COLLAPSED) ?? false;

            const sectionId = lection !== null
                ? lection.getAttribute('data-id')
                : chapter.getAttribute('data-id');
            this.reactive.dispatch('sectionContentCollapsed', [sectionId], !isCollapsed);
        }
    }

    /**
     * Refresh the section list.
     *
     * @param {object} param
     * @param {Object} param.element
     */
    _refreshCourseSectionlist({element}) {
        const hierarchy = element.hierarchy ?? [];
        let dettachedSections = [];
        for (let i = 0; i < hierarchy.length; i++) {
            const listparent = this.getElement(this.selectors.COURSE_SUBSECTIONLIST + `[data-parent='${hierarchy[i].id}']`);
            this._fixOrderTiles(listparent, hierarchy[i].children, dettachedSections);
        }
        this._fixOrderTiles(this.element, element.sectionlist ?? [], dettachedSections);
    }

    /**
     * Fix/reorder the section or cms order.
     *
     * @param {Element} container the HTML element to reorder.
     * @param {Array} neworder an array with the ids order
     * @param {Object} dettachedelements a list of dettached elements
     */
    async _fixOrderTiles(container, neworder, dettachedelements) {
        if (container === undefined || !container) {
            return;
        }

        // Grant the list is visible (in case it was empty).
        container.classList.remove('hidden');

        // Move the elements in order at the beginning of the list.
        neworder.forEach((itemid, index) => {
            let item = this.getElement(this.selectors.SECTION, itemid) ??
                this.getElement(this.selectors.CHAPTER, itemid) ??
                this.getElement(this.selectors.LECTION, itemid) ??
                dettachedelements[itemid];
            if (item === undefined) {
                // Missing elements cannot be sorted.
                return;
            }
            // Get the current element at that position.
            const currentitem = container.children[index];
            if (currentitem === undefined) {
                container.append(item);
                return;
            }
            if (currentitem !== item) {
                container.insertBefore(item, currentitem);
            }
        });

        // Remove the remaining elements.
        while (container.children.length > neworder.length) {
            const lastchild = container.lastChild;
            dettachedelements[lastchild?.dataset?.id ?? 0] = lastchild;
            container.removeChild(lastchild);
        }

        // Empty lists should not be visible.
        if (!neworder.length) {
            container.classList.add('hidden');
        }
    }

    /**
     * Create a new section instance.
     *
     * @param {Object} details the update details.
     * @param {Object} details.state the state data.
     * @param {Object} details.element the element data.
     */
    async _createSection({state, element}) {
        const sectionItem = this.getElement('section', element.id) ?? this._createFakeSection(this.element, element.id);
        if (0) { // eslint-disable-line no-constant-condition
            // TODO. Commented out part of parent function code. Is it needed for something?
            this.sections[element.id] = sectionItem;
            // Place the fake node on the correct position.
            this._refreshCourseSectionlist({
                state,
                element: state.course,
            });
        }
        // Collect render data.
        const exporter = this.reactive.getExporter();
        const data = exporter.section(state, element);

        // Create the new content.
        //const newcomponent = await this.renderComponent(sectionItem, 'format_ocmooc/local/courseindex/section', data);
        const newcomponent = await this.renderComponent(sectionItem, 'core_courseformat/local/content/section', data);
        // Replace the fake node with the real content.
        const newelement = newcomponent.getElement();
        this.sections[element.id] = newelement;
        sectionItem.parentNode.replaceChild(newelement, sectionItem);
    }

    /**
     * Create a placeholder for a section
     *
     * @param {Element} container
     * @param {Number} sectionid
     * @returns {Element}
     */
    _createFakeSection(container, sectionid) {
        const fakeelement = document.createElement('div');
        container.appendChild(fakeelement);
        fakeelement.classList.add('bg-pulse-grey', 'w-100');
        fakeelement.dataset.for = 'section';
        fakeelement.dataset.id = sectionid;
        fakeelement.innerHTML = '&nbsp;';
        return fakeelement;
    }
}
