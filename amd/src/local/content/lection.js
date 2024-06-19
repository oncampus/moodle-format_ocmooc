import Section from 'format_ocmooc/local/content/section';

export default class extends Section {
    create(descriptor) {
        this.type = 'lection';
        this.types = [
            'lection',
        ];
        this.cm = true;
        super.create(descriptor);
    }

    /**
     * Register state values and the drag and drop subcomponent.
     *
     * @param {BaseComponent} lectionitem section item component
     */
    configDragDrop(lectionitem) {
        super.configDragDrop(lectionitem);
    }

    showDropZone(dropdata) {
        if (dropdata.type == 'cm' && this.cm) {
            this.getLastCm()?.classList.add(this.classes.DROPDOWN);
        }


        if (dropdata?.type == 'lection') {
            // The relative move of section depends on the section number.
            if (this.section.parent != dropdata.parent) {
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

    drop(dropdata) {
        if (dropdata.type == 'lection') {
            this.reactive.dispatch('lectionMove', [dropdata.id], this.id);
        }
        super.drop(dropdata);
    }
}