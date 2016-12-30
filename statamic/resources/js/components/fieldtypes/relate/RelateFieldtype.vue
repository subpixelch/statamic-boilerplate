<template>
    <div class="relate-fieldtype">

        <div v-if="loading" class="loading loading-basic">
            <span class="icon icon-circular-graph animation-spin"></span> {{ translate('cp.loading') }}
        </div>

        <relate-tags
            v-if="!loading && (tags || single)"
            :data.sync="data"
            :suggestions="suggestions"
            :max-items="maxItems"
            :create="canCreate"
            :name="name">
        </relate-tags>

        <relate-panes
            v-if="!loading && panes && !single"
            :data.sync="data"
            :suggestions="suggestions"
            :max-items="maxItems"
            :name="name">
        </relate-panes>

    </div>
</template>

<script>
import RelatePanes from './RelatePanesFieldtype.vue'
import RelateTags from './RelateTagsFieldtype.vue'

module.exports = {

    components: {
        'relate-panes': RelatePanes,
        'relate-tags': RelateTags
    },

    props: ['data', 'config', 'name', 'suggestionsProp'],

    data: function() {
        return {
            loading: true,
            suggestions: []
        }
    },

    computed: {

        single: function () {
            return this.maxItems && this.maxItems === 1;
        },

        maxItems: function() {
            return this.config.max_items;
        },

        mode() {
            return this.config.mode || 'tags';
        },

        panes() {
            return this.mode === 'panes';
        },

        tags() {
            return this.mode === 'tags';
        },

        canCreate() {
            return this.config.create;
        }
    },

    methods: {

        getSuggestions: function() {
            if (this.suggestionsProp) {
                this.suggestions = this.suggestionsProp;
                this.removeInvalidData();
                this.loading = false;
            } else {
                this.$http.post(cp_url('addons/suggest/suggestions'), this.config, function(data) {
                    this.suggestions = data;
                    this.removeInvalidData();
                    this.loading = false;
                });
            }
        },

        /**
         * Remove data that doesn't exist in the suggestions.
         *
         * These may be entries that have been deleted, for example.
         */
        removeInvalidData: function () {
            var self = this;

            if (self.single) {
                if (! _.findWhere(self.suggestions, { value: self.data })) {
                    self.data = null;
                }
            } else {
                self.data = _.filter(self.data, function (item) {
                    return _.findWhere(self.suggestions, { value: item });
                });
            }
        }

    },

    ready: function() {
        if (!this.data) {
            this.data = [];
        }

        this.getSuggestions();

        this.$watch('suggestionsProp', function(suggestions) {
            this.suggestions = suggestions;
        });
    }
};
</script>
