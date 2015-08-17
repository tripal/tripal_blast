<?php
function blast_ui_services_resources() {
  return array(
    'blast' => array(
      'retrieve' => array(
        'help' => 'Retrieves a blast_ui',       
        'callback' => '_blast_ui_retrieve',
         'access callback' => 'user_access',
        'access arguments' => array('access user profiles'),
        'args' => array(),
      ),
      'create' => array(
        'help' => 'Creates a blast_ui',
        'callback' => '_blast_ui_create',
        'access arguments' => array('access content'),
        'access arguments append' => false,
        'args' => array(
           array(
              'name' => 'param',
              'type' => 'array',
              'description' => 'The node information',
              'source' => 'data',
              'optional' => FALSE,
          ),
        ),
      ),
        'update' => array(
        'help' => 'Updates a blast_ui',
        'callback' => '_blast_ui_update',
       'access callback' => 'user_access',
        'access arguments' => array('access user profiles'),
        'args' => array(
          array(
            'name' => 'id',
            'type' => 'int',
            'description' => 'The id of the node to update',
            'source' => array('path' => '0'),
            'optional' => FALSE,
          ),
          array(
            'name' => 'data',
            'type' => 'struct',
            'description' => 'The node data object',
            'source' => 'data',
            'optional' => FALSE,
          ),
        ),
      ),
      'delete' => array(
        'help' => 'Deletes a blast_ui',
        'callback' => '_blast_ui_delete',
         'access callback' => 'user_access',
       'access arguments' => array('access content'),
        'access arguments append' => TRUE,
        'args' => array(
          array(
            'name' => 'nid',
            'type' => 'int',
            'description' => 'The id of the note to delete',
            'source' => array('path' => '0'),
            'optional' => FALSE,
          ),
        ),
      ),
      'index' => array(
        'help' => 'Retrieves a listing of blast_ui',
        'callback' => '_blast_ui_index',
        'access callback' => 'user_access',
        'access arguments' => array('access content'),
        'access arguments append' => FALSE,
        'args' => array(),
        ),
     
       'actions' => array(
    	'getDatabaseType' => array(
    	'help' => 'Retrieves a listing of database',
        'callback' => '_blast_ui_getDatabaseType',
        'access callback' => 'user_access',
        'access arguments' => array('access content'),
        'access arguments append' => FALSE,
        'args' => array(),
         ),
         'getDatabaseOptions' => array(
    	'help' => 'Retrieves a listing of database',
        'callback' => '_blast_ui_getDatabaseOption',
        'access callback' => 'user_access',
        'access arguments' => array('access content'),
        'access arguments append' => FALSE,
        'args' => array(),
         ),
        ),
       ),
   );
    
}





function _blast_ui_create($param) {
  
 return services_error('Missing _blast_ui_create', 406);
}


/**
* Callback for updating note services.
*
* @param int $id
* @param object $data
* @return object
*/
function _blast_ui_update($id, $data) {
  return services_error('Missing _blast_ui_update', 406);
} 
/**
* Callback for retrieving note services.
*
* @param int $id
* @return object
*/
function _blast_ui_retrieve($id) {
 return services_error('Missing _blast_ui_retrieve', 406);
}

/**
* Callback for deleting note services.
*
* @param int $id
* @return object
*/
function _blast_ui_delete($id) {
  return services_error('Missing _blast_ui_delete', 406);
}

function _blast_ui_index($page, $parameters) {
 return array(
      'Nucleotide' => array ( 
        'Nucleotide' => 'blastn',
      	'protein' => 'blastx',
      ),
      'Protein' => array ( 
        'Nucleotide' => 'tblastn',
      	'protein' => 'blastp',
      ),
    ); 
  
//  return services_error('Missing _blast_ui_index solved', 406);
}

function _blast_ui_getDatabaseType() {

    return array(
      'Nucleotide' => array ( 
        'Nucleotide' => 'blastn',
      	'protein' => 'blastx',
      ),
      'Protein' => array ( 
        'Nucleotide' => 'tblastn',
      	'protein' => 'blastp',
      ),
    ); 
}


function _blast_ui_getDatabaseOption() {

 $db_type = 'nucleotide';
 $options = get_blast_database_options($db_type);
return $options;
}




















