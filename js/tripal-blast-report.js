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
      $('#blast_report tr.result-summary').click(function(){
        $(this).next('tr').toggle();
        $(this).find('.arrow').toggleClass('up');
      });

}}})(jQuery, Drupal);