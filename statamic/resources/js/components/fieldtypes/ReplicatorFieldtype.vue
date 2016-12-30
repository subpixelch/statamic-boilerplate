<template>
    <div class="replicator-fieldtype-wrapper">

        <div class="replicator-sets">
            <div class="list-group" v-for="(setIndex, set) in data">
                <div class="list-group-item group-header">
                    <div class="btn-group icon-group pull-right">
                        <i class="icon icon-menu drag-handle"></i>
                        <i class="icon" :class="{ 'icon-resize-100': !isHidden(set), 'icon-resize-full-screen': isHidden(set) }" v-on:click="toggle(set)"></i>
                        <button type="button" class="btn-more dropdown-toggle"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="icon icon-dots-three-vertical"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a @click="collapseAll">{{ translate('cp.collapse_all') }}</a></li>
                            <li><a @click="expandAll">{{ translate('cp.expand_all') }}</a></li>
                            <li class="warning"><a @click="deleteSet(this)">{{ translate('cp.delete_set') }}</a></li>
                        </ul>
                    </div>
                    <label>{{ setConfig(set.type).display || set.type }}</label>
                    <small class="help-block" v-if="setConfig(set.type).instructions" v-html="setConfig(set.type).instructions | markdown"></small>
                </div>
                <div class="list-group-item" v-show="!isHidden(set)">
                    <div class="row">
                        <div v-for="field in setConfig(set.type).fields" class="{{ colClass(field.width) }}">
                            <div class="form-group {{ field.type }}-fieldtype">
                                <label class="block">
                                    <template v-if="field.display">{{ field.display }}</template>
                                    <template v-if="!field.display">{{ field.name | capitalize }}</template>
                                    <i class="required" v-if="field.required">*</i>
                                </label>

                                <small class="help-block" v-if="field.instructions" v-html="field.instructions | markdown"></small>

                                <component :is="field.type + '-fieldtype'"
                                           :name="name + '.' + setIndex + '.' + field.name"
                                           :data.sync="set[field.name]"
                                           :config="field">
                                </component>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="btn-group">
            <button type="button" class="btn btn-default" v-for="set in config.sets" v-on:click="addSet(set.name)">
    			{{ set.display || set.name }}<i class="icon icon-plus icon-right"></i>
            </button>
        </div>
    </div>
</template>

<style>
    .replicator-fieldtype-wrapper {
        position: relative;
    }
    .replicator-controls {
        position: absolute;
        top: -34px;
        right: 0;
    }
    .replicator-controls .btn {
        padding: 7px 10px;
        height: auto;
        line-height: 1;
        font-size: 12px;
    }
</style>

<script>
var Vue = require('vue');

module.exports = {

    props: ['name', 'data', 'config'],

    data: function() {
        return {
            blank: {},
            sortableOptions: {}
        };
    },

    computed: {
        hasData: function() {
            return this.data !== null && this.data.length;
        }
    },

    ready: function() {
        // Initialize with an empty array if there's no data.
        if (! this.data) {
            this.data = [];
        }

        this.sortable();
    },

    methods: {

        sortable: function() {
            var self = this;
            var start = '';

            $(this.$el).find('.replicator-sets').sortable({
                axis: "y",
                revert: 175,
                placeholder: 'stacked-placeholder',
                handle: '.drag-handle',
                forcePlaceholderSize: true,
                start: function(e, ui) {
                    start = ui.item.index();
                    ui.placeholder.height(ui.item.height());
                },
                update: function(e, ui) {
                    var end  = ui.item.index();

                    // Make a local copy and reorder
                    var data = JSON.parse(JSON.stringify(self.data));
                    data.splice(end, 0, data.splice(start, 1)[0]);

                    self.data = data;
                }
            });
        },

        setConfig: function(type) {
            return _.findWhere(this.config.sets, { name: type });
        },

        deleteSet: function(set) {
            var self = this;

            swal({
                type: 'warning',
                title: translate('cp.are_you_sure'),
                confirmButtonText: translate('cp.yes_im_sure'),
                cancelButtonText: translate('cp.cancel'),
                showCancelButton: true
            }, function() {
                self.data.splice(set.$index, 1);
            });
        },

        addSet: function(type) {
            var newSet = { type: type };

            // Get nulls for all the set's fields so Vue can track them more reliably.
            var set = this.setConfig(type);
            _.each(set.fields, function(field) {
                newSet[field.name] = field.default || Statamic.fieldtypeDefaults[field.type] || null;
            });

            var index = this.data.length;
            this.data.$set(index, newSet);

            this.sortable();
        },

        toggle: function(set) {
            var hidden = set['#hidden'] || false;
            Vue.set(set, '#hidden', !hidden);
        },

        isHidden: function(set) {
            return set['#hidden'];
        },

        expandAll: function() {
              _.each(this.data, function (s) {
                    Vue.set(s, '#hidden', false);
              });
        },

        collapseAll: function () {
            _.each(this.data, function (s) {
                Vue.set(s, '#hidden', true);
            });
        },

        /**
         * Bootstrap Column Width class
         * Takes a percentage based integer and converts it to a bootstrap column number
         * eg. 100 => 12, 50 => 6, etc.
         */
        colClass: function(width) {
            if (this.$root.isPreviewing) {
                return 'col-md-12';
            }

            width = width || 100;
            return 'col-md-' + Math.round(width / 8.333);
        }
    }
};
</script>
