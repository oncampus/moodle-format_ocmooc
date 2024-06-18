import Header from 'core_courseformat/local/content/section/header';
import Exporter from "format_ocmooc/local/courseeditor/exporter";


export default class extends Header {

    create(descriptor) {
        this.type = descriptor.type;
        this.cm = descriptor.cm;
        super.create(descriptor);
    }

    validateDropData(dropdata) {
        if (this.element.dataset.number != 0 && !this.cm && dropdata.type == 'cm') {
            return false;
        }
        // Course module validation.
        if (dropdata?.type === 'cm') {
            // The first section element is already there so we can ignore it.
            const firstcmid = this.section?.cmlist[0];
            return dropdata.id !== firstcmid;
        }
        return false;
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