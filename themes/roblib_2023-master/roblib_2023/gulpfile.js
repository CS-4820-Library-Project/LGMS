//@format
const yaml = require('js-yaml');
const fs = require('fs');
const {SITE, PORT, BSREWRITE, PATHS} = loadConfig();
//var server = require('browser-sync').create();
//global.server = server;
const gulp = require('gulp');
const plumber = require('gulp-plumber');
const autoprefixer = require('gulp-autoprefixer');
const sass = require('gulp-sass')(require('sass'));
const browsersync = require('browser-sync').create();
const sourcemaps = require('gulp-sourcemaps');
const cleanCSS = require('gulp-clean-css');

function loadConfig() {
  var ymlFile = fs.readFileSync('config.yml', 'utf8');
  return yaml.load(ymlFile);
}

// BrowserSync
function bsInit__local(done) {
  browsersync.init({
    logLevel: 'debug',
    proxy: SITE.Local.Url,
    //proxy: 'https://d10_dev.lndo.site/',
  });
  done();
}

function bsInit__remote(done) {
  browsersync.init({
    //logLevel: 'debug',
    proxy: SITE.Remote.Url,
    serveStatic: ['.'],
    files: PATHS.Watch,
    plugins: ['bs-rewrite-rules'],
    rewriteRules: [
      {
        match: BSREWRITE.Css.Match,
        replace: BSREWRITE.Css.Replace,
      },
      {
        match: BSREWRITE.Js.Match,
        replace: BSREWRITE.Js.Replace,
      },
    ],
  });
  done();
}

// BrowserSync Reload
function bsReload(done) {
  browsersync.reload();
  done();
}

var cp = require('child_process');
function drush() {
  return cp.exec('lando drush cr');
}

// Compile CSS
function styles() {
  'use strict';
  return (
    gulp
      //.src(PATHS.Scss.Dir + '/**/*.scss')
      .src('./css/sass/**/*.scss')
      .pipe(plumber())
      .pipe(sourcemaps.init())
      .pipe(
        sass
          .sync({
            includePaths: PATHS.Scss.Libraries,
          })
          .on('error', sass.logError),
      )
      .pipe(cleanCSS())
      .pipe(sourcemaps.write('.'))
      //.pipe(gulp.dest(PATHS.Css.Dir))
      .pipe(gulp.dest('./css'))
      .pipe(browsersync.stream())
  );
}

// Watch Files
function watchFiles() {
  'use strict';
  //gulp.watch(PATHS.Scss.Dir + '/**/*.scss', styles);
  gulp.watch('css/sass/**/*.scss', styles);
  gulp.watch('./templates/**/*.twig', drush);
}

// Group complex tasks
const build = gulp.parallel(styles);
const watch_remote = gulp.series(
  styles,
  gulp.parallel(watchFiles, bsInit__remote),
);
const watch_local = gulp.series(
  styles,
  gulp.parallel(watchFiles, bsInit__local),
);

// Export tasks
exports.build = build;
exports.styles = styles;
exports.drush = drush;
exports.watch = watch_remote;
exports.remote = watch_remote;
exports.local = watch_local;
exports.default = watch_remote;
