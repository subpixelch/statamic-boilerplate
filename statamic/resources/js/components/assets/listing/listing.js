module.exports = {

    template: require('./listing.template.html'),

    components: {
        asset: require('../asset/asset')
    },

    props: {
        name: {
            type: String,
            default: function() {
                return _.random(1000);
            }
        },
        container: String,
        path: String,
        assets: {
            type: Array,
            required: false,
            default: function() {
                return null;
            }
        },
        folders: {
            type: Array,
            required: false,
            default: function() {
                return null;
            }
        },
        folder: {
            type: Object,
            required: false,
            default: function() {
                return null;
            }
        },
        selectedAssets: {
            type: Array,
            required: false,
            default: function() {
                return [];
            }
        },
        maxFiles: {
            type: Number,
            required: false,
            default: function() {
                return 0;
            }
        },
        mode: {
            type: String,
            default: function () {
                return Cookies.get('statamic.assets.listing_view_mode') || 'table';
            }
        }
    },

    data: function() {
        return {
            loading: true,
            editingAsset: false,
            editedAssetUuid: null,
            showFolderEditor: false,
            folderModalPath: '',
            creatingFolder: false,
            search: null,
            sortCol: 'title',
            sortOrders: {title: 1, basename: 1, size_b: 1, last_modified: 1},
            assetQueue: [],
            draggingFile: false,
            plugin: null
        }
    },

    computed: {
        hasParent: function() {
            if (! this.folder) {
                return false;
            }

            return this.folder.parent_path;
        },

        hasItems: function() {
            return this.folders.length || Object.keys(this.assets).length;
        },

        maxFilesReached: function() {
            if (this.maxFiles == 0) {
                return false;
            }

            return this.selectedUuids.length >= this.maxFiles;
        },

        allItemsChecked: function() {
            return this.assets.length > 0 && this.assets.length === this.selectedAssets.length;
        },

        selectedUuids: function() {
            return _.map(this.selectedAssets, function(asset) {
                return asset.uuid;
            });
        }

    },

    methods: {

        goToFolder: function(path) {
            this.$dispatch('path.updated', path);
        },

        // The droparea or manual upload button is clicked.
        openFinder: function() {
            $(this.$el).find('input.system-file-upload').click();
        },

        selectAsset: function(asset) {
            var found = _.findWhere(this.selectedAssets, { uuid: asset.uuid });
            var index = _.indexOf(this.selectedAssets, found);

            if (found) {
                this.selectedAssets.splice(index, 1);
            } else if ( ! this.maxFilesReached) {
                this.selectedAssets.push(asset);
            }
        },

        isSelected: function(asset) {
            var found = _.findWhere(this.selectedAssets, { uuid: asset.uuid });
            return _.indexOf(this.selectedAssets, found) !== -1;
        },

        getOrder: function(asset) {
            var found = _.findWhere(this.selectedAssets, { uuid: asset.uuid });
            return _.indexOf(this.selectedAssets, found) + 1;
        },

        getLabel: function(asset) {
            return asset.title || asset.basename;
        },

        editAsset: function(uuid) {
            this.editingAsset = true;
            this.editedAssetUuid = uuid;
        },

        deleteAsset: function(uuid) {
            var self = this;

            swal({
                type: 'warning',
                title: translate('cp.are_you_sure'),
                text: translate_choice('cp.confirm_delete_items', 1),
                confirmButtonText: translate('cp.yes_im_sure'),
                cancelButtonText: translate('cp.cancel'),
                showCancelButton: true
            }, function() {
                self.$http.delete(cp_url('assets/delete'), {
                    ids: [uuid]
                }, function(data) {
                    var item = _.findWhere(this.assets, {uuid: uuid});
                    var index = _.indexOf(this.assets, item);
                    this.assets.splice(index, 1);
                });
            });
        },

        deleteAssets: function() {
            var self = this;

            swal({
                type: 'warning',
                title: translate('cp.are_you_sure'),
                text: translate_choice('cp.confirm_delete_items', 2),
                confirmButtonText: translate('cp.yes_im_sure'),
                cancelButtonText: translate('cp.cancel'),
                showCancelButton: true
            }, function() {
                self.$http.delete(cp_url('assets/delete'), {ids: self.selectedUuids}, function(data) {
                    _.each(self.selectedAssets, function(asset) {
                        var index = _.indexOf(self.assets, asset);
                        self.assets.splice(index, 1);

                        self.selectedAssets = _.without(self.selectedAssets, asset);
                    });
                });
            });
        },

        createFolder: function() {
            this.showFolderEditor = true;
            this.folderModalPath = this.path;
            this.creatingFolder = true;
        },

        editFolder: function(path) {
            this.showFolderEditor = true;
            this.folderModalPath = path;
            this.creatingFolder = false;
        },

        deleteFolder: function(path) {
            var self = this;

            swal({
                type: 'warning',
                title: translate('cp.are_you_sure'),
                text: translate('cp.confirm_delete_item'),
                confirmButtonText: translate('cp.yes_im_sure'),
                cancelButtonText: translate('cp.cancel'),
                showCancelButton: true
            }, function() {
                self.$http.delete(cp_url('assets/folders/delete'), {
                    container: self.container,
                    folders: [path]
                }, function(data) {
                    var item = _.findWhere(this.folders, {path: path});
                    var index = _.indexOf(this.folders, item);
                    this.folders.splice(index, 1);
                });
            });
        },

        loadingComplete: function() {
            this.loading = false;
            this.$dispatch('asset-listing.loading-complete');
            this.bindUploader();
        },

        sortBy: function(col) {
            this.sortCol = col;
            this.sortOrders[col] = this.sortOrders[col] * -1;
        },

        checkAllItems: function() {
            if (this.allItemsChecked) {
                this.selectedAssets = [];
            } else {
                this.selectedAssets = this.assets;
            }
        },

        bindUploader: function() {
            var self = this;

            var $uploader = $(this.$el);

            $uploader.dmUploader({
                url: cp_url('assets'),
                extraData: {
                    container: self.container,
                    folder: self.path,
                    _token: document.querySelector('#csrf-token').getAttribute('value')
                },

                onNewFile: function(id, file) {
                    self.assetQueue.push({
                        queueId: id,
                        basename: file.name,
                        extension: file.name.split('.').pop(),
                        uploadPercent: 0
                    });
                },

                onUploadProgress: function(id, percent) {
                    var asset = _.findWhere(self.assetQueue, { queueId: id });
                    asset.uploadPercent = percent;
                },

                onUploadSuccess: function(id, data) {
                    self.assets.unshift(data.asset);

                    var asset = _.findWhere(self.assetQueue, { queueId: id });
                    var assetIndex = _.indexOf(self.assetQueue, asset);
                    self.assetQueue.splice(self.assetQueue, 1);
                },

                onUploadError: function(id, message) {
                    var asset = _.findWhere(self.assetQueue, { queueId: id });
                    Vue.set(asset, 'status', 'error');
                    Vue.set(asset, 'errorMessage', message);
                }
            });

            self.plugin = $uploader.data('dmUploader');
        },

        fileIcon: function(extension) {
            return resource_url('img/filetypes/'+ extension +'.png');
        }

    },

    ready: function() {
        if (! this.assets) {
            // No assets have been passed in as a prop? We'll retrieve them ourselves.
            this.$http.post(cp_url('assets/browse'), {
                container: this.container,
                folder: this.path
            }).success(function(data) {
                this.assets = data.assets;
                this.folder = data.folder;
                this.folders = data.folders;
                this.loadingComplete();
            });

        } else {
            // Assets have been passed in.
            this.loadingComplete();
        }

        // When an asset is edited, we'll update the title in our listing.
        this.$on('asset.updated', function(updated) {
            var asset = _.findWhere(this.assets, { uuid: updated.uuid });
            asset.title = updated.title;
        });

        // When a folder is edited, we'll update the title in our listing.
        this.$on('folder.updated', function(updated) {
            var folder = _.findWhere(this.folders, { path: updated.path });
            folder.title = updated.title;
        });

        // When a folder is created, we'll add it to the listing
        this.$on('folder.created', function(created) {
            this.folders.push(created);
        });

        // When the view mode is changed, set the preference in a cookie
        this.$watch('mode', function (mode) {
            Cookies.set('statamic.assets.listing_view_mode', mode);
        });
    }

};
