module.exports = {

    template: require('./focal.template.html'),

    props: {
        url: String,
        data: {}
    },

    data: function() {
        return {
            show: false,
            x: 50,
            y: 50
        }
    },

    computed: {
        bgPosition: function() {
            return this.x + '% ' + this.y + '%';
        }
    },

    methods: {
        open: function() {
            this.show = true;
        },

        define: function(e) {
            var $el = $(e.target);

            var imageW = $el.width();
            var imageH = $el.height();

            var offsetX = e.pageX - $el.offset().left;
            var offsetY = e.pageY - $el.offset().top;

            this.x = ((offsetX/imageW)*100).toFixed();
            this.y = ((offsetY/imageH)*100).toFixed();
        },

        select: function() {
            this.data = this.x + '-' + this.y;
            this.show = false;
        },

        cancel: function() {
            this.resetCoords();
            this.show = false;
        },

        resetCoords: function() {
            if (this.data) {
                var coords = this.data.split('-');
                this.x = coords[0];
                this.y = coords[1];
            } else {
                this.x = 50;
                this.y = 50;
            }
        }
    },

    ready: function() {
        this.resetCoords();
    }

};
