<template>
    <div class="asset-folder-fieldtype-wrapper">
        <small class="help-block" v-if="!container">{{ translate('cp.select_asset_container') }}</small>
        <div v-if="container && loading" class="loading loading-basic">
            <span class="icon icon-circular-graph animation-spin"></span> {{ translate('cp.loading') }}
        </div>
        <select-fieldtype v-if="container && !loading" :name="name" :data.sync="data" :config="selectConfig"></select-fieldtype>
    </div>
</template>

<script>
module.exports = {

    props: ['data', 'config', 'name'],

    data: function() {
        return {
            loading: true,
            options: {},
            container: null
        }
    },

    computed: {
        selectConfig: function() {
            return {
                options: this.options
            };
        },

        allowBlank: function() {
            return false;
        },

        containerField: function() {
            return this.config.container || 'container';
        }
    },

    methods: {
        getFolders: function() {
            this.$http.get(cp_url('assets/containers/' + this.container + '/folders'), function(data) {
                var options = (this.allowBlank) ? [{ value: null, text: '', }] : [];

                _.each(data, function (title, folder) {
                    var text = (title) ? title + ' (' + folder + ')' : folder;

                    options.push({
                        value: folder,
                        text: text
                    });
                });

                this.options = options;
                this.loading = false;

                if (!this.data) {
                    this.data = options[0].value;
                }
            });
        }
    },

    ready: function() {
        var self = this;

        // When the asset container is modified, we want to either get the appropriate folders or reset the folders.
        this.$parent.$watch('field', function (field) {
            // Other changes in the field will trigger this. We want to
            // ignore everything except a modifier asset container value
            if (field[self.containerField] === self.container) {
                return false;
            }

            if (field[self.containerField]) {
                self.loading = true;
                self.container = field[self.containerField];
                self.getFolders();
            } else {
                self.container = null;
                self.data = null;
            }
        }, { deep: true });

        if (this.$parent.field[this.containerField]) {
            this.container = this.$parent.field[this.containerField];
            this.getFolders();
        }
    }

};
</script>
