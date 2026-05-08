// Colors TimelineJS timenav markers from drupalSettings.coveTimelineColors,
// with auto black/white text based on background luminance.

(function () {
  'use strict';

  // Black or white text depending on background luminance (YIQ).
  function readableTextColor(hex) {
    var clean = hex.replace('#', '');
    if (clean.length !== 6) {
      return '#000';
    }
    var r = parseInt(clean.slice(0, 2), 16);
    var g = parseInt(clean.slice(2, 4), 16);
    var b = parseInt(clean.slice(4, 6), 16);
    var yiq = (r * 299 + g * 587 + b * 114) / 1000;
    return yiq >= 140 ? '#000' : '#fff';
  }

  function applyColors() {
    var colors = window.drupalSettings && window.drupalSettings.coveTimelineColors;
    if (!colors) {
      return;
    }
    Object.keys(colors).forEach(function (id) {
      // getElementById tolerates digit-leading IDs unlike querySelector.
      var markerEl = document.getElementById(id + '-marker');
      var content = markerEl && markerEl.querySelector('.tl-timemarker-content');
      if (content) {
        content.style.backgroundColor = colors[id];
        var headline = content.querySelector('.tl-headline');
        if (headline) {
          headline.style.color = readableTextColor(colors[id]);
        }
      }
    });
  }

  // Poll until TimelineJS has rendered the markers, then apply (max 10s).
  var attempts = 0;
  var interval = setInterval(function () {
    if (document.querySelectorAll('.tl-timemarker').length > 0 || attempts > 50) {
      clearInterval(interval);
      applyColors();
    }
    attempts++;
  }, 200);
})();
