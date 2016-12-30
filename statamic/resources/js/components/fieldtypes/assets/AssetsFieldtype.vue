<template>
    <div class="assets-fieldtype-wrapper"
        :class="{ 'being-dragging': draggingFile && !selector }"
        @dragover="draggingFile = true"
        @dragleave="draggingFile = false"
        @drop="draggingFile = false">

        <div v-if="loading" class="loading loading-basic">
            <span class="icon icon-circular-graph animation-spin"></span> {{ translate('cp.loading') }}
        </div>

        <div class="drag-notification" v-if="draggingFile && !selector">
            <i class="icon icon-download"></i>
            <h3>{{ translate('cp.drop_to_upload') }}</h3>
        </div>

        <div class="asset-uploader-container" :class="{ 'max-files-reached': maxFilesReached }" v-if="!loading">
            <div class="manage-assets" v-else v-if="!maxFilesReached">
                <button type="button" class="btn btn-with-icon" @click="selectAsset" @keyup.space.enter="selectAsset" tabindex="0">
                    <span class="icon icon-folder-images"></span>
                    {{ translate('cp.add_asset') }}
                </button>
                <p>or <a href='' @click.prevent="openFinder">upload</a> new file</p>
            </div>
            <div class="asset-uploader" v-if="expanded">
                <div class="asset-listing grid">
                    <asset v-for="asset in assetQueue" :asset="asset"></asset>
                </div>
            </div>
            <input type="file" multiple="multiple" class="system-file-upload hide">
        </div>

        <selector :container="container"
            :folder="folder"
            :selected="clone(assets)"
            :show.sync="selector"
            :view-mode="selectorViewMode"
            :max-files="maxFiles">
        </selector>
    </div>
</template>

<script>
import AssetFieldtypeSelector from './AssetFieldtypeSelector.vue'

module.exports = {

    components: {
        asset: require('../../assets/asset/asset'),
        selector: AssetFieldtypeSelector
    },

    props: ['data', 'config', 'name'],

    data: function() {
        return {
            assets: [],      // The asset data
            assetQueue: [],  // The assets being displayed in the ui
            loading: true,   // Fieldtype loading state
            expanded: false, // Fieldtype visual state
            plugin: null,    // Uploader plugin instance
            selector: false,  // Is the asset selector opened?
            selectorViewMode: null,
            draggingFile: false,
        };
    },

    computed: {

        hasAssets: function() {
            return Boolean(this.assets.length);
        },

        container: function() {
            return this.config.container;
        },

        folder: function() {
            return this.config.folder || '/';
        },

        maxFiles: function() {
            if (! this.config.max_files) {
                return 0;
            }

            return this.config.max_files;
        },

        maxFilesReached: function() {
            if (this.maxFiles === 0) {
                return false;
            }

            return this.assets.length >= this.maxFiles;
        }

    },

    methods: {

        clone: function(val) {
            return JSON.parse(JSON.stringify(val));
        },

        // Get assets from the server, or, if there's no data in the
        // field, just set the fieldtype to a ready state.
        getAssets: function() {
            if (this.data && this.data.length) {
                this.$http.post(cp_url('assets/get'), {uuids: this.data}, function (data) {
                    this.assets = data;
                    this.loading = false;
                    this.$nextTick(function() {
                        this.bindUploader();

                        var self = this;
                        _.each(this.assets, function(asset, i) {
                            self.assetQueue.push(asset);
                        });
                    });
                });
            } else {
                this.loading = false;
                this.$nextTick(function() {
                    this.bindUploader();
                });
            }
        },

        // The droparea or manual upload button is clicked.
        openFinder: function() {
            $(this.$el).find('input.system-file-upload').click();
        },

        // Asset+ button is clicked. Show the asset listing modal.
        selectAsset: function() {
            this.selector = true;
        },

        bindUploader: function() {
            var self = this;

            var $uploader = $(this.$el).find('.asset-uploader-container');

            $uploader.dmUploader({
                url: cp_url('assets'),
                extraData: {
                    container: self.container,
                    folder: self.folder,
                    _token: document.querySelector('#csrf-token').getAttribute('value')
                },

                // maxFiles: 0,  - we implement our own max file checks through vue

                onNewFile: function(id, file) {
                    self.assetQueue.push({
                        queueId: id,
                        basename: file.name,
                        extension: file.name.split('.').pop(),
                        uploadPercent: 0
                    });
                },

                onBeforeUpload: function(id) {
                    // Don't allow uploading files when the selector is open.
                    // The user should drag files in there instead.
                    if (self.selector) {
                        return false;
                    }

                    // Check that the max files setting hasn't been reached.
                    if (self.maxFilesReached) {
                        // If it has, tug that file back out from the queue.
                        var item = _.findWhere(self.assetQueue, { queueId: id });
                        var itemIndex = _.indexOf(self.assetQueue, item);
                        self.assetQueue.splice(itemIndex, 1);

                        // Return false so the file doesn't get uploaded
                        return false;
                    }
                },

                onUploadProgress: function(id, percent) {
                    var asset = _.findWhere(self.assetQueue, { queueId: id });
                    asset.uploadPercent = percent;
                },

                onUploadSuccess: function(id, data){
                    var asset = _.findWhere(self.assetQueue, { queueId: id });
                    Vue.set(asset, 'status', 'success');

                    // Now that the asset exists in Statamic, we'll update with the ID.
                    Vue.set(asset, 'id', data.asset.id);

                    // And show the thumbnail
                    Vue.set(asset, 'thumbnail', data.asset.thumbnail);

                    // And a toenail, obviously
                    Vue.set(asset, 'toenail', data.asset.toenail);

                    // If a duplicate file is uploaded, a timestamp will be appended.
                    // We need to reflect the updated filename in the UI.
                    Vue.set(asset, 'basename', data.asset.basename);

                    Vue.set(asset, 'extension', data.asset.extension);

                    self.assets.push(data.asset);
                },

                onUploadError: function(id, message) {
                    console.log(id, message);
                    // var asset = _.findWhere(self.assetQueue, { queueId: id });
                    // Vue.set(asset, 'status', 'error');
                    // Vue.set(asset, 'errorMessage', message);
                }
            });

            self.plugin = $uploader.data('dmUploader');
        }
    },

    events: {
        // The 'x' button on an asset has been clicked.
        'asset.remove': function(removed) {
            // Remove from queue
            var item = _.findWhere(this.assetQueue, { id: removed.id });
            var itemIndex = _.indexOf(this.assetQueue, item);
            this.assetQueue.splice(itemIndex, 1);

            // Remove from assets array
            var asset = _.findWhere(this.assets, { id: removed.id });
            var index = _.indexOf(this.assets, asset);
            this.assets.splice(index, 1);
        },

        // The 'select' button was clicked in the asset selection modal
        'assets.selected': function(assets) {
            var self = this;

            var ids = _.pluck(assets, 'id');
            var newIds = _.difference(ids, this.data);
            var removedIds = _.difference(this.data, ids);

            // Add the newly selected assets to the queue
            _.each(newIds, function(id) {
                // If the limit has been reached, just ignore.
                if (self.maxFilesReached) {
                    return;
                }

                var asset = _.findWhere(assets, { id: id });
                self.assets.push(asset);
                self.assetQueue.push({
                    id: id,
                    basename: asset.basename,
                    thumbnail: asset.thumbnail,
                    toenail: asset.toenail
                });
            });

            // Remove any removed assets
            _.each(removedIds, function(id) {
                var asset = _.findWhere(self.assets, { id: id });
                var assetIndex = _.indexOf(self.assets, asset);
                self.assets.splice(assetIndex, 1);

                var queued = _.findWhere(self.assetQueue, { id: id });
                var queuedIndex = _.indexOf(self.assetQueue, queued);
                self.assetQueue.splice(queuedIndex, 1);
            });
        }
    },

    ready: function() {
        this.selectorViewMode = Cookies.get('statamic.assets.listing_view_mode') || 'grid';

        // We only have IDs in the field data, so we'll need to request the asset data from the server.
        this.getAssets();

        // When there are assets in the queue, we want to make sure the fieldtype
        // is in expanded mode. Otherwise, we just want the "Asset +" button.
        this.$watch('assetQueue', function(queue) {
            this.expanded = Boolean(queue.length);
        });

        // When the assets array is changed (when uploading a new asset or selecting an
        // existing one), we want to update our data to only show the respective IDs.
        this.$watch('assets', function(val) {
            // Sometimes nulls gets added (eg. while uploads are in progress) so we'll just filter those out.
            this.data = _.reject(_.pluck(this.assets, 'id'), function(val) {
                return !val;
            });
        }, { deep: true });
    }
};
</script>
