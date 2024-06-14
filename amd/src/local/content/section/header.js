import Header from 'core_courseformat/local/content/section/header';
import Exporter from "format_ocmooc/local/courseeditor/exporter";


export default class extends Header {

    create(descriptor) {
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