<template>
    <div class="markdown-fieldtype-wrapper">

        <div class="markdown-toolbar clearfix">
            <ul class="markdown-modes">
                <li :class="{ 'active': mode == 'write' }">
                    <a href="" @click.prevent="mode = 'write'" tabindex="-1">{{ translate('cp.write') }}</a>
                </li>
                <li :class="{ 'active': mode == 'preview' }">
                    <a href="" @click.prevent="mode = 'preview'" tabindex="-1">{{ translate('cp.preview') }}</a>
                </li>
            </ul>

            <ul class="markdown-buttons">
                <li><a href="" @click.prevent="bold" tabindex="-1"><b>B</b></a></li>
                <li><a href="" @click.prevent="italic" tabindex="-1"><i>i</i></a></li>
                <li><a href="" @click.prevent="insertLink('')" tabindex="-1">
                    <span class="icon icon-link"></span>
                </a></li>
                <li><a href="" @click.prevent="insertImage('')" tabindex="-1">
                    <span class="icon icon-image"></span>
                </a></li>
            </ul>
        </div>

        <div class="mode-wrap mode-{{ mode }}">
            <div class="markdown-writer"
                 v-el:writer
                 v-show="mode == 'write'"
                 @dragover="draggingFile = true"
                 @dragleave="draggingFile = false"
                 @drop="draggingFile = false"
                 @keydown="shortcut">

                <div class="editor" v-el:codemirror></div>

                <div class="helpers">
                    <div class="markdown-cheatsheet-helper" v-if="cheatsheet">
                        <a href="" @click.prevent="showCheatsheet = true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="208" height="128" viewBox="0 0 208 128"><mask id="a"><rect width="100%" height="100%" fill="#fff"/><path d="M30 98v-68h20l20 25 20-25h20v68h-20v-39l-20 25-20-25v39zM155 98l-30-33h20v-35h20v35h20z"/></mask><rect width="100%" height="100%" ry="15" mask="url(#a)"/></svg>
                            {{ translate('cp.markdown_cheatsheet') }}
                        </a>
                    </div>
                    <div class="markdown-asset-helper" v-if="assetsEnabled">
                        <a href="" @click.prevent="addAsset"><span class="icon icon-image"></span> {{ translate('cp.add_asset') }}</a> (or drag &amp; drop)
                    </div>
                </div>

                <div class="drag-notification" v-if="assetsEnabled && draggingFile">
                    <i class="icon icon-download"></i>
                    <h3>{{ translate('cp.drop_to_upload') }}</h3>
                </div>
            </div>

            <div v-show="mode == 'preview'" v-html="data || '' | markdown" class="markdown-preview"></div>
        </div>

        <selector v-if="assetsEnabled"
                  :container="container"
                  :folder="folder"
                  :selected="selectedAssets"
                  :show.sync="assetSelector"
                  :view-mode="selectorViewMode"
        ></selector>

        <modal :show.sync="showCheatsheet" class="markdown-modal">
            <template slot="header">{{ translate('cp.markdown_cheatsheet') }}</template>
            <template slot="body">
                {{{ translate('markdown.cheatsheet') }}}
            </template>
        </modal>

    </div>
</template>

<script>
var CodeMirror = require('codemirror');

require('codemirror/addon/edit/closebrackets');
require('codemirror/addon/edit/matchbrackets');
require('codemirror/mode/htmlmixed/htmlmixed');
require('codemirror/mode/xml/xml');
require('codemirror/mode/markdown/markdown');
require('codemirror/mode/gfm/gfm');
require('codemirror/mode/javascript/javascript');
require('codemirror/mode/css/css');
require('codemirror/mode/clike/clike');
require('codemirror/mode/php/php');
require('codemirror/mode/yaml/yaml');

import AssetFieldtypeSelector from './assets/AssetFieldtypeSelector.vue'

module.exports = {

    components: {
        selector: AssetFieldtypeSelector
    },

    props: ['data', 'name', 'config'],

    data: function() {
        return {
            mode: 'write',
            selections: null,      // CodeMirror text selections
            assetSelector: false,  // Is the asset selector opened?
            selectedAssets: [],    // Assets selected in the selector
            selectorViewMode: null,
            draggingFile: false,
            showCheatsheet: false,
            codemirror: null       // The CodeMirror instance
        };
    },

    methods: {
        /**
         * Get the text for a selection
         *
         * @param  Range selection  A CodeMirror Range
         * @return string
         */
        getText: function(selection) {
            var i = _.indexOf(this.selections, selection);

            return this.codemirror.getSelections()[i];
        },

        /**
         * Inserts an image at the selection
         *
         * @param  String url  URL of the image
         * @param  String alt  Alt text
         */
        insertImage: function(url, alt) {
            var cm = this.codemirror.doc

            var selection = '';
            if (cm.somethingSelected()) {
                selection = cm.getSelection();
            } else if (alt) {
                selection = alt;
            }

            var url = url || '';

            // Replace the string
            var str = '![' + selection + ']('+ url +')';
            cm.replaceSelection(str, 'start');

            // Select the text
            var line = cm.getCursor().line;
            var start = cm.getCursor().ch + 2; // move past the ![
            var end = start + selection.length;
            cm.setSelection({line:line,ch:start}, {line:line,ch:end});

            this.codemirror.focus();
        },

        /**
         * Appends an image to the end of the data
         *
         * @param  String url  URL of the image
         * @param  String alt  Alt text
         */
        appendImage: function(url, alt) {
            alt = alt || '';
            this.data += '\n\n!['+alt+']('+url+')';
        },

        /**
         * Inserts a link at the selection
         *
         * @param  String url   URL of the link
         * @param  String text  Link text
         */
        insertLink: function(url, text) {
            var cm = this.codemirror.doc

            var selection = '';
            if (cm.somethingSelected()) {
                selection = cm.getSelection();
            } else if (text) {
                selection = text;
            }

            if (! url) {
                url = prompt('Enter URL', 'http://');
                if (! url) {
                    url = '';
                }
            }

            // Replace the string
            var str = '[' + selection + ']('+ url +')';
            cm.replaceSelection(str, 'start');

            // Select the text
            var line = cm.getCursor().line;
            var start = cm.getCursor().ch + 1; // move past the first [
            var end = start + selection.length;
            cm.setSelection({line:line,ch:start}, {line:line,ch:end});

            this.codemirror.focus();
        },

        /**
         * Inserts a link at the end of the data
         *
         * @param  String url   URL of the link
         * @param  String text  Link text
         */
        appendLink: function(url, text) {
            text = text || '';
            this.data += '\n\n['+text+']('+url+')';
        },

        /**
         * Toggle the boldness on the current selection(s)
         */
        bold: function() {
            var self = this;
            var replacements = [];

            _.each(self.selections, function (selection, i) {
                var replacement = (self.isBold(selection))
                    ? self.removeBold(selection)
                    : self.makeBold(selection);

                replacements.push(replacement);
            });

            this.codemirror.replaceSelections(replacements, 'around');

            this.codemirror.focus();
        },

        /**
         * Check if a string is bold
         *
         * @param  Range  selection  CodeMirror selection
         * @return Boolean
         */
        isBold: function (selection) {
            return this.getText(selection).match(/^\*{2}(.*)\*{2}$/);
        },

        /**
         * Make a string bold
         *
         * @param  Range  selection  CodeMirror selection
         * @return String
         */
        makeBold: function (selection) {
            return '**' + this.getText(selection) + '**';
        },

        /**
         * Remove the boldness from a string
         *
         * @param  Range  selection  CodeMirror selection
         * @return String
         */
        removeBold: function (selection) {
            var text = this.getText(selection);

            return text.substring(2, text.length-2);
        },

        /**
         * Toggle the italics on the current selection(s)
         */
        italic: function() {
            var self = this;
            var replacements = [];

            _.each(self.selections, function (selection, i) {
                var replacement = (self.isItalic(selection))
                    ? self.removeItalic(selection)
                    : self.makeItalic(selection);

                replacements.push(replacement);
            });

            this.codemirror.replaceSelections(replacements, 'around');

            this.codemirror.focus();
        },

        /**
         * Check if a string is italic
         *
         * @param  Range  selection  CodeMirror selection
         * @return Boolean
         */
        isItalic: function (selection) {
            return this.getText(selection).match(/^\_(.*)\_$/);
        },

        /**
         * Make a string italic
         *
         * @param  Range  selection  CodeMirror selection
         * @return String
         */
        makeItalic: function (selection) {
            return '_' + this.getText(selection) + '_';
        },

        /**
         * Remove the italics from a string
         *
         * @param  Range  selection  CodeMirror selection
         * @return String
         */
        removeItalic: function (selection) {
            var text = this.getText(selection);

            return text.substring(1, text.length-1);
        },

        /**
         * Bind the uploader plugin
         */
        bindUploader: function() {
            var self = this;
            var $uploader = $(this.$els.writer);

            $uploader.dmUploader({
                url: cp_url('assets'),
                extraData: {
                    container: self.container,
                    folder: self.folder,
                    _token: document.querySelector('#csrf-token').getAttribute('value')
                },
                onUploadSuccess: function(id, data) {
                    if (data.asset.is_image) {
                        self.appendImage(data.asset.url);
                    } else {
                        self.appendLink(data.asset.url);
                    }
                },
                onUploadError: function(id, message) {
                }
            });

            self.plugin = $uploader.data('dmUploader');
        },

        /**
         * Open the asset selector
         */
        addAsset: function() {
            this.assetSelector = true;
        },

        /**
         * Execute a keyboard shortcut, when applicable
         */
        shortcut: function(e) {
            var key = e.keyCode;
            var meta = e.metaKey === true;

            if (meta && key === 66) { // cmd+b
                this.bold();
                e.preventDefault();
            }

            if (meta && key === 73) { // cmd+i
                this.italic();
                e.preventDefault();
            }

            if (meta && key === 75) { // cmd+k
                this.insertLink();
                e.preventDefault();
            }
        }
    },

    computed: {
        assetsEnabled: function() {
            return this.config && this.config.container;
        },

        container: function() {
            return this.config.container;
        },

        folder: function() {
            return this.config.folder || '/';
        },

        cheatsheet: function() {
            return this.config && this.config.cheatsheet;
        }
    },

    events: {

        /**
         * When assets are selected from the modal, this event gets fired.
         *
         * @param  Array assets  All the assets that were selected
         */
        'assets.selected': function (assets) {
            var self = this;

            // If one asset is chosen, it's safe to replace the selection.
            // Otherwise we'll just tack on the assets to the end of the text.
            var method = (assets.length === 1) ? 'insert' : 'append';

            _.each(assets, function (asset) {
                var alt = asset.alt || '';
                if (asset.is_image) {
                    self[method+'Image'](asset.url, alt);
                } else {
                    self[method+'Link'](asset.url, alt);
                }
            });

            // We don't want to maintain the asset selections
            this.selectedAssets = [];
        }

    },

    ready: function() {
        if (this.assetsEnabled) {
            this.selectorViewMode = Cookies.get('statamic.assets.listing_view_mode') || 'grid';
            this.bindUploader();
        }

        var self = this;

        self.codemirror = CodeMirror(this.$els.codemirror, {
            value: self.data || '',
            mode: 'gfm',
            dragDrop: false,
            lineWrapping: true,
            viewportMargin: Infinity,
            tabindex: 0,
        });

        self.codemirror.on('change', function (cm) {
            self.data = cm.doc.getValue();
        });

        // Expose the array of selections to the Vue instance
        self.codemirror.on('beforeSelectionChange', function (cm, obj) {
            self.selections = obj.ranges;
        });

        // Update CodeMirror if we change the value independent of CodeMirror
        this.$watch('data', function(val) {
            if (val !== self.codemirror.doc.getValue()) {
                self.codemirror.doc.setValue(val);
            }
        });
    }

};
</script>
