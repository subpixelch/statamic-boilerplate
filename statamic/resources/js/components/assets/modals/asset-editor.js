module.exports = {

    template: require('./asset-editor.template.html'),

    components: {
        'focal-point': require('../focal/focal'),
        'publish-fields': require('../../publish/fields'),
    },

    props: {
        uuid: String,
        show: {
            type: Boolean,
            required: true,
            default: false
        }
    },

    data: function() {
        return {
            fields: {},
            asset: {},
            loading: true,
            saving: false
        }
    },

    methods: {

        getAsset: function() {
            var url = cp_url('assets/' + this.uuid);

            this.$http.get(url).success(function(data) {
                this.asset = data.asset;
                this.fields = data.fields;
                this.loading = false;
            });
        },

        save: function() {
            this.saving = true;

            var url = cp_url('assets/' + this.uuid);

            this.$http.post(url, this.fields).success(function(data) {
                this.$dispatch('asset.updated', data.asset);
                this.saving = false;
                this.close();
            });
        },

        close: function() {
            this.show = false;
        }

    },

    ready: function() {
        this.getAsset();

        this.$watch('show', function(val) {
            if (!val) {
                this.uuid = null;
            }
        });
    }

};
