module.exports = {

    template: require('./builder.template.html'),

    props: {
        'fieldsetTitle': String,
        'create': {
            type: Boolean,
            default: false
        },
        'saveUrl': String
    },

    data: function () {
        return {
            loading: true,
            errorMessage: null,
            slug: null,
            fieldset: { fields: [] },
            fieldtypes: []
        }
    },

    methods: {
        getFieldtypes: function() {
            var self = this;
            this.$http.get(cp_url('/fieldsets/fieldtypes')).success(function(data) {
                _.each(data, function(fieldtype) {
                    self.fieldtypes.push(fieldtype);
                });

                if (self.create) {
                    self.getBlankFieldset();
                } else {
                    self.getFieldset();
                }
            });
        },

        getBlankFieldset: function() {
            this.fieldset = {
                title: '',
                fields: []
            };

            this.loading = false;
        },

        getFieldset: function() {
            var self = this;
            var url = cp_url('/fieldsets/' + get_from_segment(3) + '/get?partials=0');
            self.$http.get(url).success(function (data) {
                var fieldset = this.registerFieldKeys(data);

                // Delete keys we dont need.
                fieldset.fields = _.map(fieldset.fields, function(field) {
                    delete field.complete;
                    delete field.html;
                    return field
                });

                self.fieldset = fieldset;
                self.loading = false;
            }).error(function (data) {
                self.errorMessage = data.message;
            });
        },

        /**
         * Register keys in the fields
         *
         * Vue works better when the array of data that we'll be touching already
         * contains the keys. Here we'll go ahead and add the keys from the
         * config if they don't already exist in the fieldset.
         */
        registerFieldKeys: function(fieldset) {
            var self = this;

            fieldset.fields = _.map(fieldset.fields, function(field) {
                var config = _.findWhere(self.fieldtypes, { name: field.type }).config;

                _.each(config, function(configField) {
                    if (field[configField.name] === undefined) {
                        field[configField.name] = null;
                    }
                });

                return field;
            });

            return fieldset;
        },

        save: function() {
            this.$http.post(this.saveUrl, {
                slug: this.slug,
                fieldset: this.fieldset
            }).success(function(data) {
                window.location = data.redirect;
            });
        }
    },

    ready: function() {
        this.getFieldtypes();
    }
};
