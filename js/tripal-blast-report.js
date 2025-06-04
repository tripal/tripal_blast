/**
 * @file
 * Scripts required in BLAST report page.
 */
// Attach behavior.
(function($, Drupal){
  Drupal.behaviors.TripalBlastReport = {
    attach: function (context, settings) {      
      $('#blast_report tr:not(.result-summary)').hide();
      $('#blast_report tr:first-child').show();

      // When a results summary row is clicked then show the next row in the table
      // which should be corresponding the alignment information
      var jq364 = window.jQuery.noConflict(true); // Drupal 11 uses jQuery 4.0.0-beta2 which doesn't support .toggle() on a <tr> element anymore. Fallback to use jQuery 3.6.4
      jq364('#blast_report tr.result-summary').click(function(){
        jq364(this).next('tr').toggle();
        jq364(this).find('.arrow').toggleClass('up');
      });

}}})(jQuery, Drupal);