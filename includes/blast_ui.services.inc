<?php
/**
 * Implements hook_services_resources().
 * Hooks provided by Services for the definition of new services.
 *
 * Author: Prateek Gupta
 * Created: 2016
 */

function blast_ui_services_resources() {
   return array(
       'blast' => array(
          'retrieve' => array(
            'help' => 'Retrieves a blastResult',
            'callback' => 'getBlastData',
            'access callback' => 'user_access',
            'access arguments' => array(
                'access content'
            ),
            'access arguments append' => FALSE,
            'args' => array(
                array(
                  'name' => 'job_id',
                  'type' => 'int',
                  'description' => 'The information needed',
                  'source' => array(
                      'path' => '0'
                  ),
                  'optional' => FALSE
               )
            )
         ),
         'index' => array(
            'help' => 'Retrieves a listing of blast_ui',
            'callback' => '_blast_ui_index',
            'access callback' => 'user_access',
            'access arguments' => array(
                'access content'
            ),
            'access arguments append' => FALSE,
            'args' => array ()
         ),

         'actions' => array(
             'makeJobRequest' => array(
               'help' => 'Retrieves a listing of database',
               'callback' => '_blast_ui_makeJobRequest',
               'access callback' => 'user_access',
               'access arguments' => array(
                   'access content'
               ),
               'args' => array(
                   array(
                      'name' => 'data',
                     'type' => 'array',
                     'description' => 'Retrieve blast job id',
                     'source' => 'data',
                     'optional' => FALSE
                  )
               ),
               'access arguments append' => FALSE
            ),
            
            'getBlastStatus' => array(
              'help' => 'Check status of Blast with jobId',
              'callback' => 'getBlaststatus',
              'access callback' => 'user_access',
              'access arguments' => array(
                'access content' 
              ),
              'args' => array(
                array(
                  'name' => 'data',
                  'type' => 'array',
                  'description' => 'Retrieve status of blast',
                  'source' => 'data',
                  'optional' => FALSE 
                ) 
              ),
              'access arguments append' => FALSE 
            ),

            'getDatabaseOptions' => array(
               'help' => 'Retrieves a listing of database',
               'callback' => '_blast_ui_getDatabaseOption',
               'access callback' => 'user_access',
               'access arguments' => array(
                   'access content'
               ),
               'args' => array(
                   array(
                     'name' => 'data',
                     'type' => 'array',
                     'description' => 'The set database_type information',
                     'source' => 'data',
                     'optional' => FALSE
                  )
               ),
               'access arguments append' => false
            )
         )
      )
   );
 }


/**
 * Callback for retrieving blast services.
 *
 * @param int $id
 * @return object
 */
function _blast_ui_retrieve( $id ) {
  return getBlastData( $id );
}


/**
 * Callback for Index blast services.
 * @return object
 */

function _blast_ui_index() {
  return array(
    'query_type' => array(
      '1' => 'nucleotide',
      '2' => 'protein'
    ),
    'db_type' => array(
      '1' => 'nucleotide',
      '2' => 'protein'
    ),
    'blast_program' => array(
      '1' => 'blastn',
      '2' => 'blastx',
      '3' => 'tblastn',
      '4' => 'blastp'
    )
  );
}


/*
 **  Method returns Advanced options and Database Type
 **  @param Object $data
 **  web service url :  http://localhost:8080/restapi/blast/getDatabaseOptions.json
 */

function _blast_ui_getDatabaseOption( $data ) {
 
   if ( !isset( $data['blast_program'] ) ) {
      return services_error(t( 'Missing blast attribute blast_program [ set blastn, blastx, tblastn, blastp in post request of blast_program ]'), 406 );
   }
   
   $blast_program = $data[ 'blast_program' ];
   $db_type = ($blast_program == 'blastn' || $blast_program == 'tblastn') 
            ? 'nucleotide' : 'protein';
   $options_database = get_blast_database_options( $db_type );

   // Data Set send to user for selection of Blastn
   if ( $blast_program == 'blastn' ) {
      return array(
         'database' => $options_database,
         'max_target_sequences' => _get_max_target( $blast_program ),
         'word_size' => _get_word_size( $blast_program ),
         'match_mismatch_scores' => _get_match_mismatch( $blast_program ),
         'gap_costs' => _get_gap( $blast_program )
      );
   }
   
   // Data Set send to user for selection of Blastx
   elseif ( $blast_program == 'blastx' ) {
      return array(
         'database' => $options_database,
         'max_target_sequences' => _get_max_target( $blast_program ),
         'word_size' => _get_word_size( $blast_program ),
         'matrix_options' => _get_matrix_options()

      );
   }
   
   //Data Set send to user for selection of tblastn
   elseif ( $blast_program == 'tblastn' ) {
      return array(
         'database' => $options_database,
         'max_target_sequences' => _get_max_target( $blast_program ),
         'word_size' => _get_word_size( $blast_program ),
         'matrix_options' => _get_matrix_options(),
         'gap_costs' => getGap_cost()
      );
   }
   
   //Data Set send to user for selection of blastp
   elseif ( $blast_program == 'blastp' ) {
      return array(
         'database' => $options_database,
         'max_target_sequences' => _get_max_target( $blast_program ),
         'word_size' => _get_word_size( $blast_program ),
         'matrix_options' => _get_matrix_options(),
         'gap_costs' => getGap_cost()
      );
    }
 }


/**
  * validate the data
  */
function blast_validation( $query_type, $db_type, $data ) {
  if ( $query_type == 'nucleotide' ) {
    if ( $db_type == 'nucleotide' ) {
       if ( !isset( $data['database'] ) ) {
          return services_error( "Missing blast attribute 'database' [ query blast/getDatabaseOptions for options :- Add database you need to use]", 406 );
       }
       if ( !isset( $data['max_target_sequences'] ) ) {
          return services_error( "Missing blast attribute 'max_target_sequences' [ query blast/getDatabaseOptions for options ]", 406 );
       }
       if ( !isset( $data['word_size'] ) ) {
          return services_error( "Missing blast attribute 'word_size' [ query blast/getDatabaseOptions for options ]", 406 );
       }
       if ( !isset( $data['match_mismatch_scores'] ) ) {
          return services_error( "Missing blast attribute 'match_mismatch_scores' [ query blast/getDatabaseOptions for options]", 406 );
       }
       if ( isset( $data['query'] ) ) {
          if ( validate_fasta_sequence( $query_type, $data['query'] ) == 1 ) {
             return services_error( 'Please enter valid fasta sequence', 406 );
          }
       }
       else {
          return services_error( 'Missing blast attribute data[query]', 406 );
       }
    }
    else {
       if ( !isset( $data['database'] ) ) {
          return services_error( "Missing blast attribute 'database' [ query blast/getDatabaseOptions for options]", 406 );
       }
       if ( !isset( $data['max_target_sequences'] ) ) {
          return services_error( "Missing blast attribute 'max_target_sequences' [ query blast/getDatabaseOptions for options ]", 406 );
       }
       if ( isset( $data['query'] ) ) {
          if ( validate_fasta_sequence( $query_type, $data['query'] ) == 1 ) {
             return services_error( 'Please enter valid fasta sequence', 406 );
          }
       }
       else {
          return services_error( 'Missing blast attribute data[query]', 406 );
       }
    }
  }
  else {
      if ( $db_type == 'nucleotide' ) {
         if ( !isset( $data[ 'database' ] ) ) {
            return services_error( "Missing blast attribute 'database' [ query blast/getDatabaseOptions for options]", 406 );
         }
         if ( !isset( $data[ 'max_target_sequences' ] ) ) {
            return services_error( "Missing blast attribute 'max_target_sequences' [ query blast/getDatabaseOptions for options ]", 406 );
         }
         
         if ( isset( $data[ 'query' ] ) ) {
            if ( validate_fasta_sequence( $query_type, $data[ 'query' ] ) == 1 ) {
               return services_error( 'Please enter valid fasta sequence', 406 );
            }
         }
         else {
            return services_error( 'Missing blast attribute query', 406 );
         }
      }
      else {
         if ( !isset( $data[ 'database' ] ) ) {
            return services_error( "Missing blast attribute 'database' [ query blast/getDatabaseOptions for options]", 406 );
         }
         if ( !isset( $data[ 'max_target_sequences' ] ) ) {
            return services_error( "Missing blast attribute 'max_target_sequences' [ query blast/getDatabaseOptions for options ]", 406 );
         }
         if ( isset( $data[ 'query' ] ) ) {
            if ( validate_fasta_sequence( $query_type, $data[ 'query' ] ) == 1 ) {
               return services_error( 'Please enter valid fasta sequence', 406 );
            }
         }
         else {
            return services_error( 'Missing blast attribute query', 406 );
         }
      }
   }
 }


/*
 ** Method call the blast_ui_getblastJobId by setting all the needed
 ** parameters
 ** web service url :  http://localhost:8080/restapi/blast/makeJobRequest.json
 */
function _blast_ui_makeJobRequest( $data ) {
   $query_type;
   $db_type;

   // Hard-code output type to ASN.1 because it can be converted to any format
   $output_format = 'asn';

   // Setting the default value of qRange and eValue from blast_ui.admin
   $eVal   = variable_get( 'eVal', '' );
   $qRange = variable_get( 'qRange', '' );   
   
   // check values in post query
   if ( !isset( $data[ 'query_type'] ) ) {
      return services_error( 'Missing blast attribute query_type [ set necleotide or protein in post request of query_type ]', 406 );
   }
   if ( !isset( $data[ 'db_type' ] ) ) {
      return services_error( 'Missing blast attribute db_type [ set necleotide or protein in post request of db_type ]', 406 );
   }
   
   $query_type = $data[ 'query_type' ];
   $db_type    = $data[ 'db_type' ];
   
   // check the user input in $data
   blast_validation( $query_type, $db_type, $data );
   
   if ( isset( $data[ 'eVal' ] ) ) {
      $eVal = $data[ 'eVal' ];
   }
    
   if ( isset( $data[ 'output_format' ] ) ) {
      $output_format = $data[ 'output_format' ];
      if(strcasecmp($output_format, 'xml') == 0) {
        $output_format = 'json';
      }  
   }
    
   if ( isset( $data[ 'culling_limit' ] ) ) {
      $qRange = $data[ 'culling_limit' ];
   }

   if ( $query_type == 'nucleotide' ) {
      if ( $db_type == 'nucleotide' ) {
         $blast_program    = 'blastn';
         $options_database = get_blast_database_options( $data[ 'db_type' ] );
         $Databasekey      = array_search( $data[ 'database' ], $options_database );
         $advanced_options = advanced_options( $blast_program, $eVal, $qRange, $data );
      }
      elseif ( $db_type == 'protein' ) {
         $blast_program    = 'blastx';
         // how to get gap cost - question
         $options_database = get_blast_database_options( $data[ 'db_type' ] );
         $Databasekey      = array_search( $data[ 'database' ], $options_database );
         $advanced_options = advanced_options( $blast_program, $eVal, $qRange, $data );
      }
   }
   elseif ( $query_type == 'protein' ) {
      if ( $db_type == 'nucleotide' ) {
         $blast_program    = 'tblastn';
         $options_database = get_blast_database_options( $data[ 'db_type' ] );
         $Databasekey      = array_search( $data[ 'database' ], $options_database );
         $advanced_options = advanced_options( $blast_program, $eVal, $qRange, $data );

      }
      elseif ( $db_type == 'protein' ) {
         $blast_program    = 'blastp';
         $options_database = get_blast_database_options( $data[ 'db_type' ] );
         $Databasekey      = array_search( $data[ 'database' ], $options_database );
         $advanced_options = advanced_options( $blast_program, $eVal, $qRange, $data );
      }
   }

   return blast_ui_getblastJobId( $data[ 'query_type' ], $data[ 'db_type' ], 
                                  $data[ 'query' ], $Databasekey, $advanced_options, 
                                  getOutputFormat($output_format),$output_format);
 }

 
/*
 ** Define the advanced Options for Blast
 ** @return : advanced_options according to blast_program
 */
function advanced_options( $blast_program, $eVal, $qRange, $data ) {
  if ( $blast_program == 'blastn' ) {
    $wordvalue = intval( $data[ 'word_size' ] );
    $numAlign  = intval( $data[ 'max_target_sequences' ] );
    
    $Data_GapCost         = _get_gap( $blast_program );
    $gapkey               = array_search( $data[ 'gap_costs' ], $Data_GapCost );
    
    $gap                  = _set_gap( $gapkey );
    $Data_Mismatch_Scores = _get_match_mismatch( $blast_program );
    
    $Mismatch_Scores_key  = array_search( $data[ 'match_mismatch_scores' ], $Data_Mismatch_Scores );
    $m_m                  = _set_match_mismatch( $Mismatch_Scores_key );

    return array(
       'max_target_seqs' => $numAlign,
       'evalue' => $eVal,
       'word_size' => $wordvalue,
       'gapopen' => $gap[ 'gapOpen' ],
       'gapextend' => $gap[ 'gapExtend' ],
       'penalty' => $m_m[ 'penalty' ],
       'reward' => $m_m[ 'reward' ],
       'culling_limit' => $qRange
    );
  }
  elseif ( $blast_program == 'blastx' ) {
    $wordSize = intval( $data[ 'word_size' ] );
    $numAlign = intval( $data[ 'max_target_sequences' ] );
    $matrix   = $data[ 'matrix_options' ];
    $gap      = getGap( $matrix, 1 );

    return array(
       'max_target_seqs' => $numAlign,
       'evalue' => $eVal,
       'word_size' => $wordSize,
       'gapopen' => $gap[ 'gapOpen' ],
       'gapextend' => $gap[ 'gapExtend' ],
       'culling_limit' => $qRange,
       'matrix' => $matrix
    );
  }
  elseif ( $blast_program == 'tblastn' ) {
    $wordSize = intval( $data[ 'word_size' ] );
    $numAlign = intval( $data[ 'max_target_sequences' ] );
    $matrix   = $data[ 'matrix_options' ];
    $gap      = getGap( $matrix, 1 );

    return array(
       'max_target_seqs' => $numAlign,
       'evalue' => $eVal,
       'word_size' => $wordSize,
       'gapopen' => $gap[ 'gapOpen' ],
       'gapextend' => $gap[ 'gapExtend' ],
       'culling_limit' => $qRange,
       'matrix' => $matrix
    );
  }
  elseif ( $blast_program == 'blastp' ) {
    $wordSize = intval( $data[ 'word_size' ] );
    $numAlign = intval( $data[ 'max_target_sequences' ] );
    $matrix   = $data[ 'matrix_options' ];
    $gap      = getGap( $matrix, 1 );

    return array(
       'max_target_seqs' => $numAlign,
       'evalue' => $eVal,
       'word_size' => $wordSize,
       'gapopen' => $gap[ 'gapOpen' ],
       'gapextend' => $gap[ 'gapExtend' ],
       'culling_limit' => $qRange,
       'matrix' => $matrix
    );
  }
}


/*
 ** Call the core API of Triple
 ** @return :  Job_ID
 */
function blast_ui_getblastJobId($query_type, $db_type, $seqQuery, $Databasekey, 
                                $adv_options, $output_format, $format_type) { 
  if ( $query_type == 'nucleotide' ) {
    if ( $db_type == 'nucleotide' ) {
       $blast_program = 'blastn';
    }
    elseif ( $db_type == 'protein' ) {
       $blast_program = 'blastx';
    }
  }
  elseif ( $query_type == 'protein' ) {
    if ( $db_type == 'nucleotide' ) {
       $blast_program = 'tblastn';
    }
    elseif ( $db_type == 'protein' ) {
       $blast_program = 'blastp';
    }
  }
   
  $query = variable_get('file_temporary_path', file_directory_temp()) 
         . '/' . date( 'YMd_His' ) . '_query.fasta';
  file_put_contents( $query, $seqQuery );
  $blastdb_node      = node_load( $Databasekey );
  $blastdb_name      = $blastdb_node->db_name;
  $blastdb_with_path = $blastdb_node->db_path;

  global $user;

  // Save all result files (.asn, .xml, .tsv, .html) in the public files directory.
  // Usually [drupal root]/sites/default/files.
  $output_dir = tripal_get_files_dir('tripal_blast');
  $output_filestub = $output_dir . DIRECTORY_SEPARATOR . date('YMd_His') . '.blast';
 
  $job_args = array(
     'program'         => $blast_program,
     'query'           => $query,
     'database'        => $blastdb_with_path,
     'output_filename' => $output_filestub,
     'options'         => $adv_options, 
     'format'          => $output_format,
     'format_type'     => $format_type,
  );
   
  $job_id = tripal_add_job( t( 'BLAST (@program): @query', array(
    '@program' => $blast_program,
    '@query' => $query 
  ) ), 'blast_job', 'run_BLAST_tripal_job_services', $job_args, $user->uid);

  ob_start();
  tripal_launch_job( 1, $job_id );
  ob_end_clean();

  return array(
       'job_id' => $job_id 
  );
}


/*
**
** Check status of the job entered in the queue with jobId 
*/
function getBlaststatus( $data ) {
  if ( !isset( $data[ 'job_id'] ) ) {
      return services_error( 'A job_id is required to check the blast status', 406 );
  }
  $job_id = $data[ 'job_id'];
 
   $sql =  "SELECT status FROM tripal_jobs WHERE job_id = :job_id ";
   $job_res = db_query($sql, array(':job_id' => $job_id))->fetchField();
   
   return $job_res; 
 }


/*
 **Method returns file output data
 **  @param int $job_id
 **  web service url :  http://localhost:8080/restapi/blast/job_id
 */
function getBlastData($job_id) {

  // Make sure $job_id is a number. If not, a web service was called incorrectly
  if (!intval($job_id)) {
    $msg = "The web service request was incorrectly specified. ";
    $msg .= "The job id provided ($job_id) is not a valid job id.";
    return array('status' => 400, 'msg' => $msg);
  }
  
  $result ;
  $sql = "SELECT job_name FROM tripal_jobs WHERE job_id = :job_id ";
  $query_name = db_query( $sql, array(
     ':job_id' => $job_id 
  ) )->fetchField();

  // Remove blast type from query name
  $query_path = preg_replace("/BLAST \(.*?\):\s+/", '', $query_name);

  // Strip path off query file
  $query_file = preg_replace("/^.*\/(\d.*)/", "$1", $query_path);
  $result_file_prefix = str_replace('_query.fasta', '', $query_file );

  $output_dir = tripal_get_files_dir('tripal_blast');
  $file_path = $output_dir . DIRECTORY_SEPARATOR . $result_file_prefix;

  /*
  **  defined all the format here to support output format, file_exists search the file
  **  In temp folder, if exits, get output in result.
  */

  if (file_exists("$file_path.blast.json"))
  {
    $file_path = "$file_path.blast.json";
    $result = json_decode(file_get_contents($file_path), true);
  }
  else if (file_exists("$file_path.blast.asn"))
  {
     $file_path = "$file_path.blast.asn";
     $result = readfile($file_path);
  }
  else {
     $result = "Unable to find BLAST results file. ";
// Debugging aid
//       $result = "Unable to find BLAST results file. "
//               . "Looking for results for job name [$query_name]<br>"
//               . "in directory [$output_dir],<br>"
//               . "derived from query path [$query_path],<br>"
//               . "and result_file_prefix [$result_file_prefix].<br>"
//               . "The full path+file prefix=[$file_path],<br>";
  }
  
  return $result; 
 }


/*
**  Return the result output format of final request
**
*/
function getOutputFormat($format) {
  switch ($format) {
    case "asn":
          return 11;
          break;
      
      case "json":
          return 12;
          break;
  }  
}
 
   
/*
 ** Used for tblastn
 */
function getGap_cost() {
   return array(
      'PAM30' => _get_gap_for_matrix( 'PAM30' ),
      'PAM70' => _get_gap_for_matrix( 'PAM70' ),
      'PAM250' => _get_gap_for_matrix( 'PAM250' ),
      'BLOSUM80' => _get_gap_for_matrix( 'BLOSUM80' ),
      'BLOSUM62' => _get_gap_for_matrix( 'BLOSUM62' ),
      'BLOSUM45' => _get_gap_for_matrix( 'BLOSUM45' ),
      'BLOSUM50' => _get_gap_for_matrix( 'BLOSUM50' ),
      'BLOSUM90' => _get_gap_for_matrix( 'BLOSUM90' )
   );
 }
