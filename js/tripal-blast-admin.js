/**
 * @file
 * Initialize Tripal Blast Tabs page element.
 */

// Attach behavior.
(function($, Drupal){
  Drupal.behaviors.TripalBlastAdmin = {
    attach: function (context, settings) {
      $('#tripal-blast-tabs').tabs();

}}})(jQuery, Drupal);