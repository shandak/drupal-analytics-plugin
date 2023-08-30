const gulp = require('gulp');
const webpack = require('webpack-stream');
const { watch, series } = require('gulp');
const _ = require('lodash');

require('dotenv').config();

var dist = './assets/';
process.env.DIST = dist;

async function cleanWeb() {
  const del = await import('del');
  return del.deleteAsync(`${dist}/{bi,data,js,images}/**`, { force: true });
}

function moveAnalyticJSTask() {
  return gulp
    .src(['./node_modules/aesirx-analytics/dist/analytics.js'])
    .pipe(gulp.dest(`${dist}/js`));
}

function webpackBIApp() {
  return gulp
    .src('./assets/raw/bi/index.tsx')
    .pipe(webpack(require('./webpack.config.js')))
    .pipe(gulp.dest(`${dist}`));
}

exports.build = series(
  cleanWeb,
  moveAnalyticJSTask,
  webpackBIApp,
);
