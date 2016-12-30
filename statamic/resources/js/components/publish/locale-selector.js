module.exports = {

    template: require('./locale-selector.template.html'),

    props: ['locales'],

    data: function() {
        return {
            ready: false
        };
    },

    computed: {

        activeLocale: function() {
            return _.findWhere(this.locales, { is_active: true });
        }

    },

    methods: {

        classes: function(locale) {
            var classes = 'icon-status pull-right icon-status-';
            classes += (locale.is_active) ? 'live' : 'hidden';
            return classes;
        },

        select: function(locale) {
            if (! locale.is_active) {
                window.location = locale.url;
            }
        }

    },

    ready: function() {
        this.locales = JSON.parse(this.locales);
        this.ready = true;
    }

};
