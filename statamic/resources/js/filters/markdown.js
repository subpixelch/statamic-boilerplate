var marked = require('marked');

marked.setOptions({
    gfm: false
});

module.exports = function(value) {
    return marked(value);
};
