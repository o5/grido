'use strict';

var gulp        = require('gulp'),
    merge       = require('merge-stream'),
    sass        = require('gulp-sass'),
    rename      = require('gulp-rename'),
    cssnano     = require('gulp-cssnano'),
    concat      = require('gulp-concat'),
    uglify      = require('gulp-uglify');

var assetsDir   = __dirname + '/assets',
    cssDir      = assetsDir + '/dist/css',
    jsDir       = assetsDir + '/dist/js';

gulp.task('sass', function () {
    return gulp.src(assetsDir + '/sass/*.scss')
        .pipe(sass().on('error', sass.logError))
        .pipe(gulp.dest(cssDir));
});

gulp.task('css', ['sass'], function () {
    return gulp.src([cssDir + '/*.css', '!' + cssDir + '/*.min.css'])
        .pipe(cssnano())
        .pipe(rename({extname: '.min.css'}))
        .pipe(gulp.dest(cssDir));
});

gulp.task('scripts', function() {
    var standalone = gulp.src(assetsDir + '/js/grido.js')
        .pipe(gulp.dest(jsDir));

    var bundle = gulp.src(assetsDir + '/js/**/*.js')
        .pipe(concat('grido.bundle.js'))
        .pipe(gulp.dest(jsDir));

    return merge(standalone, bundle);
});

gulp.task('js', ['scripts'], function() {
   return gulp.src([jsDir + '/*.js', '!' + jsDir + '/*.min.js'])
        .pipe(uglify({preserveComments: 'license'}))
        .pipe(rename({extname: '.min.js'}))
        .pipe(gulp.dest(jsDir));
});

gulp.task('build', ['css', 'js']);

gulp.task('default', function () {
    gulp.watch(assetsDir + '/sass/*.scss', ['css']);
    gulp.watch(assetsDir + '/js/**/*.js', ['js']);
});
