/**
 * Captures the full height document even if it's not showing on the screen or captures with the provided range of screen sizes.
 *
 * A basic example for taking a screen shot using phantomjs which is sampled for https://nodejs-dersleri.github.io/
 *
 * usage : phantomjs responsive-screenshot.js {url} [output format] [doClipping]
 *
 * examples >
 *    phantomjs responsive-screenshot.js https://nodejs-dersleri.github.io/
 *    phantomjs responsive-screenshot.js https://nodejs-dersleri.github.io/ pdf
 *    phantomjs responsive-screenshot.js https://nodejs-dersleri.github.io/ true
 *    phantomjs responsive-screenshot.js https://nodejs-dersleri.github.io/ png true
 *
 * @author Salih sagdilek <salihsagdilek@gmail.com>
 */

/**
 * http://phantomjs.org/api/system/property/args.html
 *
 * Queries and returns a list of the command-line arguments.
 * The first one is always the script name, which is then followed by the subsequent arguments.
 */
var args = require('system').args;
/**
 * http://phantomjs.org/api/fs/
 *
 * file system api
 */
var fs = require('fs');

/**
 * http://phantomjs.org/api/webpage/
 *
 * Web page api
 */
var page = new WebPage();

/**
 * if url address does not exist, exit phantom
 */
if ( 1 == args.length ) {
  console.log('Usage:');
  console.log('  phantomjs screengrab.js {url}');
  console.log('  -p path');
  console.log('  -f output file');
  console.log('  -w viewport width');
  console.log('  -h viewport height');
  console.log('  -e (png|jpg) extension');
  console.log('  -c (true|false) clipping');
  phantom.exit();
}

/**
 *  setup url address (second argument);
 */
var urlAddress = args[1].toLowerCase();

/**
 *  stage output path
 */
var outputPath = getArgument('p', './');
var outputFile = getArgument('f', '');

/**
 * set output extension format
 * @type {*}
 */
var ext = '.' + getArgument('e', 'png');

/**
 * set if clipping ?
 * @type {boolean}
 */
var clipping = getArgument('c', false);

/**
 * setup viewports
 */
var viewports = [
  {
    width : getArgument('w', 1200),
    height : getArgument('h', 800)
  }
];

/**
 * Main
 */
page.open(urlAddress, function (status) {
  if ( 'success' !== status ) {
    console.log('Unable to load the url address!');
  }
  else {
    var folder = outputPath + (String(outputPath).match(/\/$/) ? '' : '/');
    if ( !fs.makeTree(folder) ) {
      console.log('"' + dir + '" is NOT created.');
      phantom.exit();
    }
    var filename, output, key;
    function render(n) {
      if ( !!n ) {
        key = n - 1;
        page.viewportSize = viewports[key];
        if ( clipping ) {
          page.clipRect = viewports[key];
        }
        if (viewports.length > 1 || outputFile == ''){
          filename = getFileName(viewports[key]);
          output = folder + urlToDir(urlAddress) + '/' + filename;
        }
        else {
          filename = outputFile;
          output = folder + filename;
        }
        var meta = page.evaluate(function() {
          var result = [];
          var metaDataList = document ? document.querySelectorAll('head meta') : [];
          for (var i = 0; i < metaDataList.length; i++) {
            var meta = metaDataList[i];
            var object = {};
            if (meta && meta.attributes) {
              for (var j = 0; j < meta.attributes.length; j++) {
                var attr = meta.attributes[j].nodeName;
                object[attr] = meta.attributes[j].nodeValue;
              }
              result.push(object);
            }
          }
          return result;
        });
        page.render(output);
        console.log('Url: ' + urlAddress);
        console.log('Path: ' + output);
        console.log('File: ' + filename);
        console.log('Title: ' + page.title);
        for (var k in meta) {
          var name = '', property = '', content = '';
          for (var v in meta[k]) {
            if (v == 'name') console.log(meta[k]['name']+': '+meta[k]['content']);
            if (v == 'property') console.log(meta[k]['property']+': '+meta[k]['content']);
          }
        }
        render(key);
      }
    }
    render(viewports.length);
  }
  phantom.exit();
});

/**
 * filename generator helper
 * @param viewport
 * @returns {string}
 */
function getFileName(viewport) {
  var d = new Date();
  var date = [
    d.getUTCFullYear(),
    d.getUTCMonth() + 1,
    d.getUTCDate()
  ];
  var time = [
    d.getHours() <= 9 ? '0' + d.getHours() : d.getHours(),
    d.getMinutes() <= 9 ? '0' + d.getMinutes() : d.getMinutes()
    // d.getSeconds() <= 9 ? '0' + d.getSeconds() : d.getSeconds(),
    // d.getMilliseconds()
  ];
  var resolution = viewport.width + (clipping ? "x" + viewport.height : '');
  return date.join('-') + '_' + time.join('-') + "_" + resolution + ext;
}

/**
 * url to directory helper
 *
 * @param url
 * @returns {string}
 */
function urlToDir(url) {
  var dir = url
    .replace(/^(http|https):\/\//, '')
    .replace(/\/$/, '');
  return dir;
}

/**
 * [getArgument description]
 * @param  {[type]} key [description]
 * @return {[type]}     [description]
 */
function getArgument( key, def ){
  for (var i=1;i<args.length;i++) {
    if (args[i] == '-' + key) {
      return typeof args[i+1] == 'undefined' ? def : args[i+1];
    }
  }
  return def;
}