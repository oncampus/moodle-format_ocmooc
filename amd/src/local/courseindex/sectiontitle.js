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
 * Course index section title component.
 *
 * This component is used to control specific course section interactions like drag and drop.
 *
 * @module     core_courseformat/local/courseindex/sectiontitle
 * @class      core_courseformat/local/courseindex/sectiontitle
 * @copyright  2021 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import SectionTitle from 'core_courseformat/local/courseindex/sectiontitle';
import Exporter from "format_ocmooc/local/courseeditor/exporter";


export default class extends SectionTitle {

    create(descriptor){
        this.type = descriptor.type;
        super.create(descriptor);
    }

    _getDraggableData() {
        const exporter = new Exporter();
        if (this.type == 'chapter') {
            return exporter.chapterDraggableData(this.reactive.state, this.id);
        } else {
            return exporter.lectionDraggableData(this.reactive.state, this.id);
        }
    }
}