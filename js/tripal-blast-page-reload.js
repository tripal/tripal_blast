(function ($, Drupal) {
  Drupal.behaviors.blastuiSetTimeout = {
    attach: function (context, settings) {
      setTimeout(function () {
        window.location.reload(1);
      }, 5000);
    }
  };
})(jQuery, Drupal);
