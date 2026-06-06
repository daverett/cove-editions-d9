/**
 * @file
 * Keeps the page in place when the cove_og_content exposed filters are applied,
 * cancelling core's scroll-to-top. Pager clicks are left untouched.
 */

(function ($, Drupal, once) {
  'use strict';

  var VIEW_NAME = 'cove_og_content';
  var FORM_SELECTOR = '#views-exposed-form-cove-og-content-block-1';

  var pendingScrollY = null;

  function isOurViewRequest(settings) {
    if (!settings) {
      return false;
    }
    var haystack =
      (settings.url || '') +
      '&' +
      (typeof settings.data === 'string' ? settings.data : '');
    return (
      haystack.indexOf('/views/ajax') !== -1 &&
      haystack.indexOf('view_name=' + VIEW_NAME) !== -1
    );
  }

  Drupal.behaviors.coveOgContentFilterScroll = {
    attach: function () {
      once('cove-og-content-filter-scroll', 'body').forEach(function () {
        // Remember the position the moment the exposed filter is submitted.
        $(document).on('mousedown keydown', FORM_SELECTOR + ' [type="submit"]', function () {
          pendingScrollY = window.scrollY;
        });
        $(document).on('submit', FORM_SELECTOR, function () {
          pendingScrollY = window.scrollY;
        });

        // Restore it after the refresh so core's scroll-to-top is undone. The
        // flag is always cleared on this view's requests, so it never outlives
        // a single request/response cycle (a pager click finds it already null).
        $(document).on('ajaxComplete', function (event, xhr, settings) {
          if (!isOurViewRequest(settings)) {
            return;
          }
          var y = pendingScrollY;
          pendingScrollY = null;
          if (y === null) {
            return;
          }
          window.requestAnimationFrame(function () {
            window.scrollTo(0, y);
          });
        });
      });
    }
  };
})(jQuery, Drupal, once);
