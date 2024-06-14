import Section from 'format_ocmooc/local/content/section';

export default class extends Section {

    create(descriptor) {
        this.type = 'chapter';
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
        super.drop(dropdata);
    }

    getWatchers() {
        return [
            {watch: `chapter[${this.id}]:updated`, handler: this._refreshChapter},
        ];
    }

    _refreshChapter(element) {
        console.log("chapter update");
    }
}