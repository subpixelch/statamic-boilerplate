const Elixir = require('laravel-elixir');
const Task = Elixir.Task;
const gulp = require('gulp');
const changed = require('gulp-changed');

const imageMin = require('gulp-imagemin');
require('laravel-elixir-vue-2');

/*
 * Configure extra Elixir tasks
 */
Elixir.extend('images', function() {
    new Task('images', function() {
        this.recordStep('Optimizing images');
        this.src = { path: 'themes/default/resources/img/**/*' };
        this.output = { path: './public/default/img' };

        gulp.src(this.src.path)
            .pipe(changed(this.output.path))
            .pipe(imageMin())
            .pipe(gulp.dest(this.output.path));
    }).watch('themes/default/resources/img/**/*');
});

/*
 |--------------------------------------------------------------------------
 | Elixir Asset Management
 |--------------------------------------------------------------------------
 |
 | Elixir provides a clean, fluent API for defining some basic Gulp tasks
 | for your Laravel application. By default, we are compiling the Sass
 | file for your application as well as publishing vendor resources.
 |
 */

Elixir.config.assetsPath = 'themes/default/resources';

Elixir((mix) => {
    mix.sass('app.scss', './public/default/css/default.css');

    mix.webpack('scripts.js', './public/default/js/default.js');

    mix.images();
});
