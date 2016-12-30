module.exports = {

    template: require('./folder-editor.template.html'),

    props: {
        show: Boolean,
        container: String,
        path: String,
        create: { type: Boolean, default: false }
    },

    data: function() {
        return {
            form: {},
            folder: {},
            loading: true,
            saving: false,
            basenameModified: false
        }
    },

    methods: {

        reset: function() {
            this.path = '';
            this.folder = {};
            this.form = {};
            this.loading = true;
        },

        stopEditing: function() {
            $(this.$el).modal('hide');
        },

        getFolder: function() {
            if (this.create) {
                this.getBlankFolder();
            } else {
                this.getExistingFolder();
            }
        },

        getBlankFolder: function() {
            this.folder = {};
            this.form = {
                container: this.container,
                parent: this.path,
                title: '',
                basename: ''
            };
            this.loading = false;
        },

        getExistingFolder: function() {
            var url = cp_url('assets/folders/' + this.container + '/' + this.path);

            this.$http.get(url).success(function(data) {
                this.folder = data;
                this.form = {
                    title: data.title
                };
                this.loading = false;
            });
        },

        save: function() {
            this.saving = true;

            if (this.create) {
                this.saveNewFolder();
            } else {
                this.saveExistingFolder();
            }
        },

        saveNewFolder: function() {
            var url = cp_url('assets/folders');

            this.$http.post(url, this.form).success(function(data) {
                this.$dispatch('folder.created', data.folder);
                this.saving = false;
                this.close();
            });
        },

        saveExistingFolder: function() {
            var url = cp_url('assets/folders/' + this.container + '/' + this.path);

            this.$http.post(url, this.form).success(function(data) {
                this.$dispatch('folder.updated', data.folder);
                this.saving = false
                this.close();
            });
        },

        close: function() {
            this.show = false;
        }

    },

    ready: function() {
        this.getFolder();

        this.$watch('show', function(val) {
            if (!val) {
                this.folder = null;
            }
        });

        this.$watch('form.title', function(title) {
            if (this.create && !this.basenameModified) {
                this.form.basename = this.$slugify(title);
            }
        });
    }

};
