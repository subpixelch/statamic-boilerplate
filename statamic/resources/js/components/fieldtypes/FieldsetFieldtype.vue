<template>
    <div class="template-fieldtype-wrapper">
        <div v-if="loading" class="loading loading-basic">
            <span class="icon icon-circular-graph animation-spin"></span> {{ translate('cp.loading') }}
        </div>
        <select-fieldtype v-if="!loading" :name="name" :data.sync="data" :config="selectConfig"></select-fieldtype>

    </div>
</template>

<script>

module.exports = {

    props: {
        data: {},
        config: {},
        name: {},
        required: Boolean,
        hidden: { type: Boolean, default: function() { return true; }},
        url: String,
    },

    data: function() {
        return {
            loading: true,
            options: {}
        }
    },

    computed: {
        selectConfig: function() {
            return {
                options: this.options
            };
        }
    },

    ready: function() {
        var url = cp_url('fieldsets/get');
        var params = {};

        if (this.url) {
            // Append the URL if we want to get available fieldsets for a particular page.
            params.url = this.url;
        }

        if (! this.hidden) {
            // By default, we'll get all fieldsets. If we specify that we
            // dont want hidden ones, we'll pass that along.
            params.hidden = false;
        }

        url += '?' + $.param(params);

        this.$http.get(url, function(data) {
            // If a value is required, don't add a blank row.
            var options = (this.required) ? [] : [{ value: null, text: '' }];

            _.each(data.items, function(fieldset) {
                options.push({
                    value: fieldset.uuid,
                    text: fieldset.title
                });
            });
            this.options = options;
            this.loading = false;

            // If a value is required and we don't already have a value, select the first one.
            if (this.required && !this.data) {
                this.data = this.options[0].value;
            }
        });
    }
};
</script>
