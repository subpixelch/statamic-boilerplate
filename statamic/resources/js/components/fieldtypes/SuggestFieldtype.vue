<template>
    <div class="suggest-fieldtype-wrapper">
        <div v-if="loading" class="loading loading-basic">
            <span class="icon icon-circular-graph animation-spin"></span> {{ translate('cp.loading') }}
        </div>

        <select v-if="!loading"
                :name="name"
                :placeholder="translate('please_select')"
                :multiple="true">
        </select>
    </div>
</template>

<script>
module.exports = {

    props: ['data', 'name', 'config', 'suggestionsProp'],

    data: function() {
        return {
            loading: true,
            suggestions: []
        }
    },

    methods: {

        getSuggestions: function() {
            if (this.suggestionsProp) {
                this.suggestions = this.suggestionsProp;
                this.loading = false;
                this.$nextTick(function() {
                    this.initSelectize();
                });
            } else {
                this.$http.post(cp_url('addons/suggest/suggestions'), this.config, function(data) {
                    this.suggestions = data;
                    this.loading = false;

                    this.$nextTick(function() {
                        this.initSelectize();
                    });
                });
            }
        },

        initSelectize: function() {
            var self = this;

            $(this.$el).find('select').selectize({
                options: this.suggestions,
                items: this.data,
                create: this.config.create || false,
                maxItems: this.config.max_items,
                placeholder: this.config.placeholder,
                plugins: ['drag_drop', 'remove_button'],
                onChange: function(value) {
                    self.data = value;
                }
            });
        }

    },

    ready: function() {
        this.getSuggestions();
    }
};
</script>
