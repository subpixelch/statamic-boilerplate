module.exports = {

    mixins: [Dossier],

    props: ['get', 'delete', 'keyword', 'reorder', 'canDelete', 'sort', 'sortOrder', 'reorderable'],

    data: function() {
        return {
            ajax: {
                get: this.get,
                delete: this.delete,
                reorder: this.reorder
            },
            tableOptions: {
                sort: this.sort,
                sortOrder: this.sortOrder,
                reorderable: this.reorderable,
                partials: {}
            }
        }
    },

    ready: function () {
        this.addActionPartial();
    },

    methods: {
        addActionPartial: function () {
            var str = `<li><a :href="item.edit_url">{{ translate('cp.edit') }}</a></li>`;

            if (this.canDelete) {
                str += `
                    <li class="warning">
                        <a href="#" @click.prevent="call('deleteItem', item.id)">{{ translate('cp.delete') }}</a>
                    </li>`;
            }

            this.tableOptions.partials.actions = str;
        }
    }

};
