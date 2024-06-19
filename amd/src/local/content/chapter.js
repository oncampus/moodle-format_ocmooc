import Section from 'format_ocmooc/local/content/section';

export default class extends Section {

    create(descriptor) {
        this.type = 'chapter';
        this.types = [
            'chapter',
            'lection',
        ];
        this.cm = false;
        super.create(descriptor);
    }

    /**
     * Register state values and the drag and drop subcomponent.
     *
     * @param {BaseComponent} chapteritem section item component
     */
    configDragDrop(chapteritem) {
        super.configDragDrop(chapteritem);
    }

    showDropZone(dropdata) {
        if (dropdata?.type == 'chapter') {
            // The relative move of section depends on the section number.
            if (this.section.number > dropdata.number) {
                this.element.classList.remove(this.classes.DROPUP);
                this.element.classList.add(this.classes.DROPDOWN);
            } else {
                this.element.classList.add(this.classes.DROPUP);
                this.element.classList.remove(this.classes.DROPDOWN);
            }
        }

        if (dropdata?.type == 'lection') {
            this.element.classList.remove(this.classes.DROPUP);
            this.element.classList.add(this.classes.DROPDOWN);
        }
    }

    drop(dropdata) {
        if (dropdata.type == 'chapter') {
            this.reactive.dispatch('chapterMove', [dropdata.id], this.id);
        }
        if (dropdata.type == 'lection') {
            this.reactive.dispatch('lectionMove2Chapter', [dropdata.id], this.id);
        }
        if (dropdata.type == 'cm') {
            return;
        }
        super.drop(dropdata);
    }
}