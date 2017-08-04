// Require all the things (that we need)
var watch = require('gulp-watch');
var gulp = require('gulp');
var phpcs = require('gulp-phpcs');

// Define the source paths for each file type
var src = {
	php: ['**/*.php','!node_modules/**','!vendor/**']
};

// Check our PHP.
gulp.task('php',function() {
	gulp.src(src.php)
		.pipe(phpcs({
			bin: './vendor/bin/phpcs',
			standard: 'WordPress-Core'
		}))
		// Log all problems that was found
		.pipe(phpcs.reporter('log'));
});

// Our default tasks.
gulp.task('default',['test']);

// Test all the things
gulp.task('test',['php']);

// I've got my eyes on you(r file changes)
gulp.task('watch',function() {
	gulp.watch(src.php,['php']);
});