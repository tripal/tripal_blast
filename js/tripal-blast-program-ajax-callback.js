/**
 * @file
 * AJAX callback used in BLAST programs forms.
 */

(function($) {
  // Argument passed from InvokeCommand.
  $.fn.ajaxFieldUpdateCallback = function(argument) {
    var $el = $('#tripal-blast-fld-select-gap-cost');

    $el.html(' ');    
    $.each(argument, function(key, value) {      
      $el.append($('<option></option>')
        .attr('value', value)
        .text(value));
    });
    
  };
})(jQuery);
