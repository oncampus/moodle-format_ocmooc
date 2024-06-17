import Section from 'format_ocmooc/local/content/section';

export default class extends Section {

    create(descriptor) {
        this.type = 'chapter';
        this.types = [
            'chapter',
            'lection',
        ];
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

    drop(dropdata) {
        if (dropdata.type == 'chapter') {
            this.reactive.dispatch('chapterMove', [dropdata.id], this.id);
        }
        if (dropdata.type == 'lection') {
            this.reactive.dispatch('lectionMove2Chapter', [dropdata.id], this.id);
        }
        super.drop(dropdata);
    }

    getWatchers() {
        return [
            {watch: `section[${this.id}]:updated`, handler: this._refreshChapter},
        ];
    }

    _refreshChapter(element) {
        super._refreshSection(element);
    }
}