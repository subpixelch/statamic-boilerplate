<template>
    <div class="card flat-bottom sticky">
        <div class="head">
            <h1>
                {{ container }}
                <strong v-if="path !== '/'"><small>{{ path }}</small></strong>
            </h1>
            <div class="btn-group" v-if="path === '/'">
                <button class="btn btn-secondary" @click="sync" :disabled="syncing">
                    <template v-if="!syncing">{{ translate('cp.sync_assets') }}</template>
                    <template v-if="syncing">{{ translate('cp.syncing_assets') }}</template>
                </button>
            </div>
            <div class="btn-group">
                <button class="btn btn-primary" @click="openFinder">{{ translate('cp.upload_asset') }}</button>
            </div>
        </div>
    </div>
    <div class="card flat-top">

        <div v-if="loading" class="loading">
            <span class="icon icon-circular-graph animation-spin"></span> {{ translate('cp.loading') }}
        </div>

        <asset-listing v-if="!loading"
                       name="browse"
                       :assets="assets"
                       :folders="folders"
                       :folder="folder"
                       :container="uuid"
                       :path="path">
        </asset-listing>
    </div>

</template>

<script>
module.exports = {

    props: {
        container: String,
        uuid: String,
        path: String
    },

    data: function() {
        return {
            assets: [],
            folders: [],
            folder: {},
            loading: true,
            syncing: false,
        }
    },

    methods: {

        loadAssets: function() {
            this.$http.post(cp_url('assets/browse'), {
                container: this.uuid,
                folder: this.path
            }).success(function(data) {
                this.assets = data.assets;
                this.folder = data.folder;
                this.folders = data.folders;
                this.loading = false;
            });
        },

        openFinder: function() {
            $('.system-file-upload').click();
        },

        sync: function () {
            this.syncing = true;
            this.$http.get(cp_url('assets/sync/' + this.uuid)).success(function (response) {
                this.loadAssets();
                this.syncing = false;
            })
        },

        updatePath: function(path) {
            this.loading = true;
            this.path = path;
            this.loadAssets();
        },

        bindBrowserNavigation: function() {
            var self = this;

            // Set the initial path in the history state for back
            window.history.replaceState({ path: this.path }, '');

            // When the browser back/forward buttons are clicked
            window.onpopstate = function(e) {
                self.updatePath(e.state.path);
            };
        },

        pushState: function() {
            var path = (this.path === '/') ? '' : '/'+this.path;
            window.history.pushState({ path: this.path }, '', cp_url('assets/browse/' + this.uuid + path));
        }

    },

    ready: function() {
        // Initially get the assets for this folder
        this.loadAssets();

        // Support back/forward buttons
        this.bindBrowserNavigation();

        // When the path is updated in the listing (ie. user wants to navigate to a new folder)
        this.$on('path.updated', function(newPath) {
            this.updatePath(newPath);
            this.pushState();
        });

        // When an asset is uploaded, we want it to be shown in the listing.
        this.$on('asset.uploaded', function(uploaded) {
            this.assets.unshift(uploaded);
        });
    }

};
</script>
