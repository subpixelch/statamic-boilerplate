var $ = require('jquery');
var Mousetrap = require('mousetrap');

Vue.config.debug = true;

require('./plugins');
require('./filters');
require('./mixins');
require('./components');
require('./fieldtypes');
require('./directives');

Vue.http.headers.common['X-CSRF-TOKEN'] = document.querySelector('#csrf-token').getAttribute('value');

var vm = new Vue({
    el: '#statamic',

    data: {
        isPublishPage: false,
        isPreviewing: false,
        showShortcuts: false,
        navVisible: false,
        version: Statamic.version,
        flashSuccess: false,
        flashError: false
    },

    computed: {
        showPage: function() {
            return !this.hasSearchResults;
        },

        hasSearchResults: function() {
            return this.$refs.search.hasItems;
        }
    },

    methods: {
        preview: function() {
            var self = this;
            self.$broadcast('previewing');
            self.isPreviewing = true;

            $('.sneak-peek-viewport').addClass('on');

            setTimeout(function() {
                $(self.$el).addClass('sneak-peeking');
                $('#sneak-peek').find('iframe').show();
                setTimeout(function() {
                    $(self.$el).addClass('sneak-peek-editing');
                }, 200);
            }, 200);
        },

        stopPreviewing: function() {
            var self = this;
            var $viewport = $('.sneak-peek-viewport');
            var $icon = $viewport.find('.icon');

            $(self.$el).removeClass('sneak-peek-editing');
            $('#sneak-peek').find('iframe').fadeOut().remove();
            $icon.hide();
            setTimeout(function() {
                $(self.$el).removeClass('sneak-peeking');
                $viewport.removeClass('on');
                setTimeout(function(){
                    $icon.show();
                    self.isPreviewing = false;
                    self.$broadcast('previewing.stopped');
                }, 200);
            }, 500);
        },

        toggleNav: function () {
            this.navVisible = !this.navVisible;
        }
    },

    ready: function() {
        Mousetrap.bind(['/', 'ctrl+f'], function(e) {
            $('#global-search').focus();
        }, 'keyup');

        Mousetrap.bind('?', function(e) {
            this.showShortcuts = true;
        }.bind(this), 'keyup');

        Mousetrap.bind('escape', function(e) {
            this.$broadcast('modal.close')
        }.bind(this), 'keyup');
    },

    events: {
        'setFlashSuccess': function (msg) {
            this.flashSuccess = msg
        },
        'setFlashError': function (msg) {
            this.flashError = msg
        }
    }
});
