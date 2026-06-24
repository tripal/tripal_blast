/**
 * @file
 * Initialize Tripal Blast UI page accordion element.
 */

// Attach behavior.
(function($, Drupal, once){
  Drupal.behaviors.TripalBlastUI = {
    attach: function (context, settings) {
      $('#tripal-blast-accordion', context).accordion({
        icons: false,
        collapsible: true
      });

      // Listen to what is BLAST information link.
      var infoLink = 'tripal-blast-nav-blast';
      var win = $('#tripal-blast-information-window', context);

      once('tripal-blast-info', '#' + infoLink, context).forEach(function (element) {
        $(element).click(function(e) {
          e.preventDefault();

          if (win.is(':visible')) {
            win.slideUp();
          }
          else {
            win.slideDown();
          }
        });
      });
    }
  };
})(jQuery, Drupal, once);