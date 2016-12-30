module.exports = {

    template: require('./container.template.html'),

    props: {
        isNew: Boolean,
        container: Object
    },

    data: function () {
        return {
            config: {
                title: null,
                handle: null,
                driver: 'local',
                fieldset: null,
                local: {},
                s3: {}
            },
            drivers: [
                { value: 'local', text: 'Local' },
                { value: 's3', text: 'Amazon S3' }
            ],
            isHandleModified: false,
            errors: []
        };
    },

    computed: {
        driver: function () {
            return this.config.driver;
        },
        s3Regions: function () {
            return [
                { value: 'us-east-1', text: 'US East / US Standard / us-east-1' },
                { value: 'us-west-2', text: 'US West (Oregon) / us-west-2' },
                { value: 'us-west-1', text: 'US West (N. California) / us-west-1' },
                { value: 'eu-west-1', text: 'EU (Ireland) / eu-west-1' },
                { value: 'eu-central-1', text: 'EU (Frankfurt) / eu-central-1' },
                { value: 'ap-southeast-1', text: 'Asia Pacific (Singapore) / ap-southeast-1' },
                { value: 'ap-northeast-1', text: 'Asia Pacific (Tokyo) / ap-northeast-1' },
                { value: 'ap-southeast-2', text: 'Asia Pacific (Sydney) / ap-southeast-2' },
                { value: 'ap-northeast-2', text: 'Asia Pacific (Seoul) / ap-northeast-2' },
                { value: 'sa-east-1', text: 'South America (Sao Paulo) / sa-east-1)' }
            ]
        },
        hasErrors: function() {
            return _.size(this.errors) !== 0;
        }
    },

    ready: function () {
        if (! this.isNew) {
            var driver = this.container.driver || 'local';
            this.config.driver = driver;
            this.config.title = this.container.title;
            this.config.handle = this.container.handle;
            this.config.fieldset = this.container.fieldset;
            this.config[driver] = this.container;

        } else {
            // For new containers, set the region dropdown to the first option
            this.config.s3.region = _.first(this.s3Regions).value;
        }

        if (this.isNew) {
            this.syncTitleAndHandleFields();
        }
    },

    methods: {

        save: function () {
            var url = (this.isNew) ? cp_url('configure/content/assets') : cp_url('configure/content/assets/'+this.container.id);

            this.$http.post(url, this.config).success(function (response) {
                if (response.success) {
                    window.location = response.redirect;
                } else {
                    this.errors = response.errors;
                }
            });
        },

        syncTitleAndHandleFields: function() {
            this.$watch('config.title', function(title) {
                if (this.isHandleModified) return;

                this.config.handle = this.$slugify(title);
            });
        }

    }

};
