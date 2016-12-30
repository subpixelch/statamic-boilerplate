<template>
    <div class="asset-fieldtype-selector-wrapper">
        <modal :show.sync="show" :full="true">
            <template slot="header">Asset Manager</template>
            <template slot="body">
                <asset-listing v-if="showListing"
                    :container="container"
                    :path="folder"
                    :selected-assets.sync="selected"
                    :max-files="maxFiles"
                    :mode="viewMode">
                </asset-listing>
            </template>
            <template slot="footer">
                <button type="button" class="btn" @click="close">{{ translate('cp.close') }}</button>
                <button type="button" class="btn btn-primary btn-small" @click="select">{{ translate('cp.select') }}</button>
            </template>
        </modal>
    </div>
</template>

<script>
module.exports = {
    props: {
        show: {
            type: Boolean,
            default: false,
            twoWay: true
        },
        container: String,
        folder: String,
        selected: Array,
        maxFiles: {
            type: Number,
            required: false
        },
        viewMode: String
    },

    data: function() {
        return {
            loading: true,
            showListing: true
        }
    },

    methods: {

        select: function() {
            this.$dispatch('assets.selected', this.selected);
            this.close();
        },

        close: function() {
            this.show = false;
        }

    },

    ready: function() {
        this.$on('asset-listing.loading-complete', function() {
            this.loading = false;
        });
    },

    events: {
        // A folder was selected to navigate to in the listing.
        'path.updated': function (path) {
            // We'll stop showing the listing, update the path, then re-show the listing.
            // This will force the listing component to refresh with the new path.
            // It's the simplest solution for now to allow folder navigation.
            this.showListing = false;
            this.folder = path;
            this.$nextTick(function () {
                this.showListing = true;
            })
        }
    }

};
</script>
