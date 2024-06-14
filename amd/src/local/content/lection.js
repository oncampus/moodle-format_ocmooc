import Section from 'format_ocmooc/local/content/section';

export default class extends Section {
    create(descriptor) {
        this.type = 'lection';
        this.types = [
            'lection',
        ];
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

    drop(dropdata) {
        if (dropdata.type == 'lection') {
            this.reactive.dispatch('lectionMove', [dropdata.id], this.id);
        }
        super.drop(dropdata);
    }

    getWatchers() {
        return [
            {watch: `section[${this.id}]:updated`, handler: this._refreshLection},
        ];
    }

    _refreshLection(element) {
        console.log(element);
    }
}