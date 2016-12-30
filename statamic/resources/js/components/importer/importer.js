module.exports = {

    props: ['importer'],

    data: function() {
        return {
            loading: true,
            instructions: null,
            siteUrl: '',
            exporting: false,
            exported: false,
            exportFailed: false,
            exportError: null,
            importing: false,
            imported: false,
            summary: null,
            showAllPages: false,
            showCollections: [],
            showTaxonomies: [],
            showGlobals: []
        }
    },

    computed: {
        totalPages: function () {
            return Object.keys(this.summary.pages).length;
        }
    },

    ready: function () {
        this.$http.get(cp_url('import/'+this.importer+'/details')).success(function (response) {
            this.loading = false;
            this.instructions = response.instructions;
            this.json = response.json;
        });
    },

    methods: {
        export: function () {
            this.exporting = true;
            this.$http.post(cp_url('import/'+this.importer+'/export'), { url: this.siteUrl }).success(function (response) {
                if (response.success) {
                    this.exporting = false;
                    this.exported = true;
                    this.summary = response.summary;
                    this.setChecks();
                }
            }).error(function (response) {
                this.exporting = false;
                this.exportFailed = true;
                this.exportError = response.error;
            });
        },

        import: function () {
            this.importing = true;
            this.$http.post(cp_url('import/'+this.importer+'/import'), { summary: this.summary }).success(function (response) {
                this.importing = false;
                this.imported = true;
                console.log(response);
            });
        },

        setChecks: function () {
            _.each(this.summary.pages, function (page) {
                page._checked = true;
            });

            _.each(this.summary.collections, function (collection) {
                _.each(collection.entries, function (entry) {
                    entry._checked = true;
                })
            });

            _.each(this.summary.taxonomies, function (taxonomy) {
                _.each(taxonomy.terms, function (term) {
                    term._checked = true;
                })
            });

            _.each(this.summary.globals, function (global) {
                _.each(global.variables, function (variable) {
                    variable._checked = true;
                })
            });
        },

        size: function (obj) {
            return _.size(obj);
        },

        showCollection: function (collection) {
            this.showCollections.push(collection);
            _.uniq(this.showCollections);
        },

        hideCollection: function (hidden) {
            this.showCollections = _.reject(this.showCollections, function (c) {
                return c === hidden;
            })
        },

        shouldShowCollection: function (collection) {
            return _.contains(this.showCollections, collection);
        },

        showTaxonomy: function (taxonomy) {
            this.showTaxonomies.push(taxonomy);
            _.uniq(this.showTaxonomies);
        },

        hideTaxonomy: function (hidden) {
            this.showTaxonomies = _.reject(this.showTaxonomies, function (t) {
                return t === hidden;
            })
        },

        shouldShowTaxonomy: function (taxonomy) {
            return _.contains(this.showTaxonomies, taxonomy);
        },

        showGlobal: function (global) {
            this.showGlobals.push(global);
            _.uniq(this.showGlobals);
        },

        hideGlobal: function (hidden) {
            this.showGlobals = _.reject(this.showGlobals, function (g) {
                return g === hidden;
            })
        },

        shouldShowGlobal: function (global) {
            return _.contains(this.showGlobals, global);
        }
    }
};
