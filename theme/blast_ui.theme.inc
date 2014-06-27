<?php

function blast_ui_preprocess_show_blast_report(&$vars) {

   $path = drupal_get_path('module', 'blast_ui');
 
   drupal_add_css($path . '/theme/blast_report.css');
   drupal_add_js('http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js');

}


?>
