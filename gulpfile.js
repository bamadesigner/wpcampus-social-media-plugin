const autoprefixer = require('gulp-autoprefixer');
const cleanCSS = require('gulp-clean-css');
const gulp = require('gulp');
const mergeMediaQueries = require('gulp-merge-media-queries');
const notify = require('gulp-notify');
const rename = require('gulp-rename');
const sass = require('gulp-sass');

// Define the source paths for each file type.
const src = {
	sass: ['assets/src/sass/**/*']
};

// Define the destination paths for each file type.
const dest = {
	sass: 'assets/build/css'
};

// Take care of SASS.
gulp.task('sass', function() {
	return gulp.src(src.sass)
		.pipe(sass({
			outputStyle: 'expanded'
		}).on('error', sass.logError))
		.pipe(mergeMediaQueries())
		.pipe(autoprefixer({
			browsers: ['last 2 versions'],
			cascade: false
		}))
		.pipe(cleanCSS({
			compatibility: 'ie8'
		}))
		.pipe(rename({
			suffix: '.min'
		}))
		.pipe(gulp.dest(dest.sass))
		.pipe(notify('WPC Social Media SASS compiled'));
});

// Compile all the things.
gulp.task('compile',['sass']);

// I've got my eyes on you(r file changes).
gulp.task('watch',function() {
	gulp.watch(src.sass,['sass']);
});

// Let's get this party started.
gulp.task('default',['compile']);
