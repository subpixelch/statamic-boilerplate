module.exports = {

    template: require('./asset.template.html'),

    props: {
        asset: Object
    },

    data: function() {
        return {
            editing: false
        }
    },

    computed: {

        isImage: function() {
            return this.asset.basename.toLowerCase().match(/(jpe?g|gif|png)$/);
        },

        isUploaded: function () {
            return Boolean(this.asset.id);
        },

        thumbnail: function() {
            return this.asset.thumbnail;
        },

        toenail: function() {
            return this.asset.toenail;
        },

        icon: function() {
            return resource_url('img/filetypes/'+ this.asset.extension +'.png');
        },

        uploadPercent: function() {
            return this.asset.uploadPercent;
        },

        uploadSucceeded: function() {
            return this.asset.status === 'success';
        },

        uploadFailed: function() {
            return this.asset.status === 'error';
        },

        label: function() {
            return this.asset.title || this.asset.basename;
        }
    },

    methods: {

        edit: function() {
            this.editing = true;
        },

        remove: function() {
            this.$dispatch('asset.remove', this.asset);
        },

        makeZoomable: function() {
            if (this.isImage && this.isUploaded) {
                new Luminous($(this.$el).find('a.zoom')[0], {
                    closeOnScroll: true,
                    captionAttribute: 'title'
                });
            }
        }

    },

    ready: function() {
        this.makeZoomable();

        this.$watch('asset', function (asset) {
            console.log(asset);
            if (asset.id) {
                this.$nextTick(function () {
                    this.makeZoomable();
                });
            }
        }, { deep: true });
    }
};
