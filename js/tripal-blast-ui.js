/**
 * @file
 * Initialize Tripal Blast UI page accordion element.
 */

// Attach behavior.
(function($, Drupal){
  Drupal.behaviors.TripalBlastUI = {
    attach: function (context, settings) {
      $('#accordion').accordion({
        icons: false,
        collapsible: true
      });

      // Listen to what is BLAST information link.
      $('#tripal-blast-information-link').click(function() {
        var win = jQuery('#tripal-blast-info-win');
        
        if (win.is(':visible')) {
          win.slideUp();
        }
        else {
          win.slideDown();
        }
      });

    }
  }
})(jQuery, Drupal);