var gulp = require('gulp');
//var spawn = require('./gulp_suport/spawn');
var eslint = require('gulp-eslint');
var stylus = require('gulp-stylus');
var concat = require('gulp-concat');
var browserify = require('gulp-browserify');
var reactify = require('reactify');

var cssmin = require('gulp-cssmin');
var uglify = require('gulp-uglify');

var gutil = require('gulp-util');

var paths = {
  vendor: [
//    'client/vendor/underscore/underscore-min.js',
//    'client/vendor/zepto/zepto.min.js',
    'client/vendor/director/build/director.min.js',
//    'client/vendor/backbone/backbone-min.js',
//    'client/vendor/backbone-associations/backbone-associations-min.js',
    'client/vendor/react/react-with-addons.min.js',
    'client/vendor/react/react-dom.min.js',
    'client/vendor/jquery/dist/jquery.min.js',
    'client/vendor/bootstrap/dist/js/bootstrap.min.js'
  ],
  vendor_dev: [
//    'client/vendor/underscore/underscore.js',
//    'client/vendor/zepto/zepto.js',
    'client/vendor/director/build/director.js',
//    'client/vendor/backbone/backbone.js',
//    'client/vendor/backbone-associations/backbone-associations.js',
    'client/vendor/react/react-with-addons.js',
    'client/vendor/react/react-dom.js',
    'client/vendor/jquery/dist/jquery.js',
    'client/vendor/bootstrap/dist/js/bootstrap.js'
  ]
};


var browserifyOpts = {
    transform: ['reactify'],
    extensions: ['.js', '.jsx'],
    debug: !gulp.env.production,
    paths: ['./client/']
};

// Style
gulp.task('stylus', ['stylus-public', 'stylus-app']);

gulp.task('stylus-app', function() {
  return gulp.src('client/css/index.styl')
      .pipe(stylus())
      .pipe(cssmin())
      .pipe(gulp.dest('client/public/css'));
});

gulp.task('stylus-public', function() {
  return gulp.src('client/stylus/public/public.styl')
      .pipe(stylus())
      .pipe(cssmin())
      .pipe(gulp.dest('client/public/css'));
});


// Client JS Vendor
gulp.task('js-client-vendor', function() {
  return gulp.src(paths.vendor)
             .pipe(uglify())
             .pipe(concat('vendor.js'))
             .pipe(gulp.dest('client/public/js/'));
});

gulp.task('js-client-vendor--dev', function() {
  return gulp.src(paths.vendor_dev)
             .pipe(concat('vendor.js'))
             .pipe(gulp.dest('client/public/js/'));
});

// Client APP
gulp.task('js-client-app', function() {
  return gulp.src('client/js/app.jsx')
             .pipe(browserify(browserifyOpts))
             .on('error', gutil.log)
             .pipe(uglify())
             .pipe(concat('app.js'))
             .pipe(gulp.dest('client/public/js/'));
});

gulp.task('js-client-app--dev', function() {
  return gulp.src('client/js/app.jsx')
             .pipe(browserify(browserifyOpts))
             .on('error', gutil.log)
             .pipe(concat('app.js'))
             .pipe(gulp.dest('client/public/js/'));
});


// Combined tasks
gulp.task('js-client--dev', ['js-client-vendor--dev', 'js-client-app--dev']);
gulp.task('js-client', ['js-client-vendor', 'js-client-app']);


gulp.task('server', ['stylus-app', 'js-client--dev'], function(cb) {
    // var opts = {cwd: __dirname + '/server'};

    // spawn.run('../node_modules/nodemon/bin/nodemon.js', ['app.js'], opts, function(err) {
    //     console.log('server fail with error:', err);
    //     cb(err);
    // });

     // Rebild stylus on change
     gulp.watch('client/css/**/*.styl', ['stylus']);

     // Rebild vendor js on change
     //gulp.watch('client/vendor/**/*.js', ['js-client-vendor--dev']);

    // Rebild client app js on change
     gulp.watch(['client/js/**/*.js', 'client/js/**/*.jsx'], ['js-client-app--dev']);
});

gulp.task('lint', function () {
    return gulp.src(['client/js/**/*.js','client/js/**/*.jsx'])
        // eslint() attaches the lint output to the eslint property
        // of the file object so it can be used by other modules.
        .pipe(eslint())
        // eslint.format() outputs the lint results to the console.
        // Alternatively use eslint.formatEach() (see Docs).
        .pipe(eslint.format())
        // To have the process exit with an error code (1) on
        // lint error, return the stream and pipe to failOnError last.
        .pipe(eslint.failAfterError());
});

gulp.task('default', ['lint', /*'stylus',*/ 'js-client'], function() {
  // place code for your default task here
});