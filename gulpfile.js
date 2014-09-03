'use strict';

// Include Gulp & Tools We'll Use
var gulp = require('gulp');
var $ = require('gulp-load-plugins')();
var del = require('del');
var runSequence = require('run-sequence');
var livereload = require('gulp-livereload');
var uglify = require('gulp-uglifyjs');
var react = require('gulp-react');
var concat = require('gulp-concat-sourcemap');
var source = require('vinyl-source-stream');
var gutil = require('gulp-util');
var browserify = require('browserify');
var reactify = require('reactify');
var watchify = require('watchify');
var notify = require("gulp-notify");
var replace = require('gulp-replace');
var buffer = require('vinyl-buffer');
var sourcemaps = require('gulp-sourcemaps');
var minifyCSS = require('gulp-minify-css');

var AUTOPREFIXER_BROWSERS = [
    'ie >= 10',
    'ie_mob >= 10',
    'ff >= 30',
    'chrome >= 34',
    'safari >= 7',
    'opera >= 23',
    'ios >= 7',
    'android >= 4.4',
    'bb >= 10'
];

var scriptsDir = './app/Resources/jsx/';
var buildDir = './web/dist';


function handleErrors() {
    var args = Array.prototype.slice.call(arguments);
    console.error(args);
    //notify.onError({
    //    title: "Compile Error",
    //    message: "<%= error.message %>"
    //}).apply(this, args);
    this.emit('end'); // Keep gulp from hanging on this task
}

function buildScript(file, watch) {
    var props = {entries: [scriptsDir + '/' + file]};
    var bundler = watch ? watchify(props) : browserify(props);

    bundler.transform(reactify);

    var bundle = function() {
        return bundler
            .bundle()
            .pipe(source(file))
            .pipe(buffer())
            .pipe(sourcemaps.init({loadMaps: true}))
            // Add transformation tasks to the pipeline here.
            //.pipe(uglify())
            .pipe(sourcemaps.write(buildDir+'/js/'))
            .pipe(gulp.dest(buildDir+'/scripts/'))
            .pipe($.size({title: 'bottom-js'}));
    };

    bundler.on('update', function() {
        bundle();
        gutil.log('Rebundle...');
        livereload.changed();
    });

    return bundle();
}

gulp.task('build', ['styles', 'fonts', 'top-scripts'], function() {
    return buildScript('app.js', false);
});

gulp.task('jsx', function() {
    return buildScript('app.js', true);
});

gulp.task('top-scripts', function () {
    return gulp.src(['bower_components/modernizr/modernizr.js', 'bower_components/respond/dest/respond.min.js', 'web/bundles/fosjsrouting/js/router.js', 'web/js/fos_js_routes.js'])
        .pipe(uglify('top.js'))
        .pipe(gulp.dest('web/dist/scripts'))
        .pipe($.size({title: 'top-js'}));
});

gulp.task('fonts', function () {
    return gulp.src(['bower_components/**/*.{svg,eot,ttf,woff}'])
        .pipe($.flatten())
        .pipe(gulp.dest('web/dist/fonts'))
        .pipe($.size({title: 'fonts'}));
});

//Build CSS
gulp.task('styles', function () {
    // For best performance, don't add Sass partials to `gulp.src`
    return gulp.src([
        'app/Resources/scss/*.scss'
    ])
        .pipe($.changed('styles', {extension: '.scss'}))
        .pipe($.rubySass({
            style: 'expanded',
            precision: 10,
            loadPath: ['bower_components']
        })
        .on('error', console.error.bind(console))
    )
        .pipe($.autoprefixer(AUTOPREFIXER_BROWSERS))
        .pipe($.if('*.css[]', $.csso()))
        .pipe($.flatten())
        //.pipe(replace(/octicons\.([eot|woff|ttf|svg])/g, "/dist/fonts/octicons.$1"))
        //.pipe(minifyCSS())
        .pipe(gulp.dest('web/dist/styles'))
        .pipe($.size({title: 'styles'}));
});

// Clean Output Directory
gulp.task('clean', del.bind(null, ['web/dist']));

// Livereload
gulp.task('listen', function () {
    livereload.listen();
});

gulp.task('watch', function() {
    gulp.watch(['app/Resources/**/*.html.twig'], livereload.changed);
    gulp.watch(['app/Resources/scss/*.scss'], ['styles', livereload.changed]);
    gulp.watch(['app/Resources/jsx/**/*.js'], ['jsx', livereload.changed]);
    gulp.watch(['app/Resources/jsx/**/*.jsx'], ['jsx', livereload.changed]);
});

// Default Task
gulp.task('default', ['clean'], function (cb) {
    runSequence('styles', 'fonts', 'jsx', 'top-scripts', 'watch', 'listen', cb);
});