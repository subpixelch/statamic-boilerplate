<script>
import DossierTable from './DossierTable.vue'

module.exports = {

    data: function () {
        return {
            loading: true,
            items: [],
            columns: [],
            search: null,
            reordering: false
        }
    },

    computed: {
        hasItems: function() {
            return !this.loading && this.items && this.items.length;
        },

        noItems: function() {
            return !this.loading && this.items && !this.items.length;
        },

        checkedItems: function() {
            return this.items.filter(function(item) {
                return item.checked;
            }).map(function(item) {
                return item.id;
            });
        },

        allItemsChecked: function() {
            return this.items.length === this.checkedItems.length;
        }
    },

    ready: function () {
        this.getItems();
    },

    components: {
        'dossier-table': DossierTable
    },

    methods: {
        getItems: function () {
            this.$http.get(this.ajax.get, function(data, status, request) {
                this.items = data.items;
                this.columns = data.columns;
                this.loading = false;
            }).error(function() {
                alert('There was a problem retrieving data. Check your logs for more details.');
            });
        },

        removeItemFromList: function(id) {
            var item = _.findWhere(this.items, {id: id});
            var index = _.indexOf(this.items, item);
            this.items.splice(index, 1);
        },

        deleteMultiple: function () {
            var self = this;

            swal({
                type: 'warning',
                title: translate('cp.are_you_sure'),
                text: translate_choice('cp.confirm_delete_items', 2),
                confirmButtonText: translate('cp.yes_im_sure'),
                cancelButtonText: translate('cp.cancel'),
                showCancelButton: true
            }, function() {
                self.$http.delete(self.ajax.delete, {ids: self.checkedItems}, function (data) {
                    _.each(self.checkedItems, function (id) {
                        self.removeItemFromList(id);
                    });
                });
            });
        },


        deleteItem: function (id) {
            var self = this;

            swal({
                type: 'warning',
                title: translate('cp.are_you_sure'),
                text: translate_choice('cp.confirm_delete_items', 1),
                confirmButtonText: translate('cp.yes_im_sure'),
                cancelButtonText: translate('cp.cancel'),
                showCancelButton: true
            }, function() {
                self.$http.delete(self.ajax.delete, {ids: [id]}, function (data) {
                    self.removeItemFromList(id);
                });
            });
        },

        enableReorder: function() {
            this.reordering = true;
            this.$broadcast('reordering.start');
        },

        cancelOrder() {
            this.reordering = false;
            this.$broadcast('reordering.stop');
        },

        saveOrder: function () {
            this.saving = true;

            var order = _.map(this.items, function (item, i) {
                return item.id;
            });

            this.$http.post(this.ajax.reorder, {ids: order}, function () {
                this.saving = false;
                this.$broadcast('reordering.saved');
                this.loading = true;
                this.getItems();
                this.reordering = false;
            });
        },
    }

};
</script>
