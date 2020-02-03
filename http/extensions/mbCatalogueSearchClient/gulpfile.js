/*
 Install
 composer    composer require --dev <package>
 gulp        npm install --save-dev <package>
 bower       bower install --dev <package> ???
 */

const gulp      = require('gulp'),
    copy        = require('gulp-copy'),
    livereload  = require('gulp-livereload'),
    connect     = require('gulp-connect-php'),
    concat      = require('gulp-concat'),
    sass        = require('gulp-sass'),
    minifyCSS   = require('gulp-minify-css'),
    uglify      = require('gulp-uglify'),
    rename      = require('gulp-rename'),
    open        = require('open'),
    path        = require('path'),
    browserSync = require('browser-sync')
    ;

var conf = {
    watch: {
        files: 'web/**/*.{php,js,css}'
    },
    sass: {
        files  : 'sass/**/*.scss',
        dest   : 'web/css',
        options: {
            outputStyle  : 'compressed',
            includePaths : []
        }
    }
};

gulp.task('default', ['watch'], () => {
    connect.server({base: 'web'});
    open('http://localhost:8000/index.php');
});

gulp.task('init', ['clean'], () => {
    composer();
    return bower();
});

gulp.task('scripts', () => {
    gulp.src(['web/js/Storage.js', 'web/js/Search.js', 'web/js/main.js'])
        .pipe(concat('js/all.js'))
        .pipe(gulp.dest('web'));
});

gulp.task('sass', function() {
    return gulp.src(conf.sass.files)
        .pipe(sass(conf.sass.options))
        .pipe(sass().on('error', sass.logError))
        .pipe(gulp.dest(conf.sass.dest));
});

gulp.task('watch', () => {
    livereload.listen();

gulp.watch(conf.sass.files, ['sass']);
    gulp.watch(conf.watch.files).on('change', function(file) {
        livereload.changed(file.path);
        browserSync.reload();
    });
});

gulp.watch(['web/js/Storage.js', 'web/js/Search.js', 'web/js/main.js'], ['scripts']);

gulp.task('browser-sync', () => {
    connect.server({base: 'web'}, () => {
        browserSync({
            proxy: '127.0.0.1:3000',
            open: false
        });
    });

    gulp.watch(conf.watch.files).on('change', function(file) {
        browserSync.reload();
    });

    open('http://localhost:8000/index.php');
});

gulp.task('default', ['watch'], () => {
    connect.server({base: 'web'});
    open('http://localhost:8000/index.php');
});
