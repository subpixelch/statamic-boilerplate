<template>
    <div class="redactor-fieldtype-wrapper">
        <textarea v-el:redactor :name="name" v-model="data"></textarea>
        <selector v-if="assetsEnabled"
                  :container="container"
                  :folder="folder"
                  :selected="selectedAssets"
                  :show.sync="assetSelector"
                  :view-mode="selectorViewMode"
        ></selector>
    </div>
</template>

<script>
import AssetFieldtypeSelector from '../assets/AssetFieldtypeSelector.vue'

module.exports = {

    components: {
        selector: AssetFieldtypeSelector
    },

    props: ['data', 'name', 'config'],

    data: function() {
        return {
            mode: 'write',
            assetSelector: false,
            selectedAssets: [],
            selectorViewMode: null
        }
    },

    methods: {
        update: function(html) {
            this.data = html;
        },

        addAsset: function() {
            this.assetSelector = true
        },

        insertLink: function(url, text) {
            if (text === '') {
                text = prompt('Enter link text');
            }

            $(this.$els.redactor).redactor(
                'insert.html',
                '<a href="' + url + '">' + text + '</a>'
            );
        },

        insertImage: function(url, text) {
            $(this.$els.redactor).redactor(
                'insert.html',
                '<img src="' + url + '" alt="' + text + '" />'
            );
        },

        appendImage: function(url, text) {
            var $r = $(this.$els.redactor);

            var code = $r.redactor('code.get');

            $r.redactor(
                'code.set',
                code + '<img src="' + url + '" alt="' + text + '" />'
            );
        }
    },

    computed: {
        assetsEnabled: function() {
            return this.config && typeof this.config.container !== 'undefined';
        },

        container: function() {
            return this.config.container;
        },

        folder: function() {
            return this.config.folder || '/';
        }
    },

    events: {

        'assets.selected': function (assets) {
            var self = this;
            var $r = $(self.$els.redactor);

            if (assets.length === 1) {
                var asset = assets[0];
                var selection = $r.redactor('selection.getHtml');
                var alt = asset.alt || '';
                // If there's no selection, we'll actually use the alt text
                selection = (selection !== '') ? selection : alt;

                if (asset.is_image) {
                    self.insertImage(asset.url, selection);
                } else {
                    self.insertLink(asset.url, selection);
                }

            } else {
                var code = $r.redactor('code.get');

                _.each(assets, function (asset) {
                    var url = asset.url;
                    var text = asset.alt || '';

                    if (asset.is_image) {
                        code += '<img src="' + url + '" alt="' + text + '" />';
                    } else {
                        if (text === '') {
                            text = prompt('Enter link text.');
                        }
                        code += '<a href="' + url + '">' + text + '</a>';
                    }
                });

                $r.redactor('code.set', code);
            }

            // We don't want to maintain the asset selections
            this.selectedAssets = [];
        }

    },

    ready: function() {
        this.selectorViewMode = Cookies.get('statamic.assets.listing_view_mode') || 'grid';

        var womp = this;

        var defaults = {
            changeCallback: function () {
                womp.update(this.code.get());
            }
        };

        if (this.config.settings && typeof this.config.settings !== 'string') {
            console.warn('Redactor Fieldtype: You must reference the settings name instead of adding them inline.')
        }

        // Get the appropriate configuration. If the one they've requested
        // doesnt exist, we'll use the first one defined.
        if (_.has(Statamic.redactorSettings, this.config.settings)) {
            var config = Statamic.redactorSettings[this.config.settings];
        } else {
            var config = Statamic.redactorSettings[_.first(_.keys(Statamic.redactorSettings))];
        }

        var settings = _.extend(defaults, config);

        settings.plugins = settings.plugins || [];

        if (this.assetsEnabled) {
            settings.plugins.push('assets');
        }

        $(this.$els.redactor).redactor(settings);
    }
};
</script>
