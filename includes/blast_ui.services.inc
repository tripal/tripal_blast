<?php
/**
 * Implements hook_services_resources().
 * Hooks provided by Services for the definition of new services,
 */

function blast_ui_services_resources()
 {
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
             'getJobId' => array(
                'help' => 'Retrieves a listing of database',
               'callback' => '_blast_ui_getJobId',
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
function _blast_ui_retrieve( $id )
 {
   return getBlastData( $id );
 }

/**
 * Callback for Index blast services.
 * @return object
 */

function _blast_ui_index()
 {
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

function _blast_ui_getDatabaseOption( $data )
 {

   if ( !isset( $data[ query_type ] ) )
    {
      return services_error( 'Missing blast attribute $query_type [ set necleotide or protein in post request of query_type ]', 406 );
    }
   if ( !isset( $data[ db_type ] ) )
    {
      return services_error( 'Missing blast attribute $db_type [ set necleotide or protein in post request of db_type ]', 406 );
    }
   if ( !isset( $data[ blast_program ] ) )
    {
      return services_error( 'Missing blast attribute $blast_program [ set blastn, blastx, tblastn, blastp in post request of blast_program ]', 406 );
    }

   $query_type       = $data[ db_type ];
   $db_type          = $data[ db_type ];
   $blast_program    = $data[ blast_program ];
   $options_database = get_blast_database_options( $db_type );

   // Data Set send to user for selection of Blastn
   if ( $blast_program == 'blastn' )
    {
      return array(
          'Select_Database' => $options_database,
         'Max_target_sequences' => _get_max_target( $blast_program ),
         'Word_size' => _get_word_size( $blast_program ),
         'Match_Mismatch_Scores' => _get_match_mismatch( $blast_program ),
         'Gap_Costs' => _get_gap( $blast_program )
      );
    }
   // Data Set send to user for selection of Blastx
   elseif ( $blast_program == 'blastx' )
    {
      return array(
          'Select_Database' => $options_database,
         'Max_target_sequences' => _get_max_target( $blast_program ),
         'Word_size' => _get_word_size( $blast_program ),
         'Matrix_options' => _get_matrix_options()

      );
    }
   //Data Set send to user for selection of tblastn
   elseif ( $blast_program == 'tblastn' )
    {
      return array(
          'Select_Database' => $options_database,
         'Max_target_sequences' => _get_max_target( $blast_program ),
         'Word_size' => _get_word_size( $blast_program ),
         'Matrix_options' => _get_matrix_options(),
         'Gap_Costs' => getGap_cost()
      );
    }
   //Data Set send to user for selection of blastp
   elseif ( $blast_program == 'blastp' )
    {
      return array(
          'Select_Database' => $options_database,
         'Max_target_sequences' => _get_max_target( $blast_program ),
         'Word_size' => _get_word_size( $blast_program ),
         'Matrix_options' => _get_matrix_options(),
         'Gap_Costs' => getGap_cost()
      );
    }
 }

/*
 ** validate the data
 */

function blast_validation( $query_type, $db_type, $data )
 {
   if ( $query_type == 'nucleotide' )
    {
      if ( $db_type == 'nucleotide' )
       {

         if ( !isset( $data[ Select_Database ] ) )
          {
            return services_error( 'Missing blast attribute $Select_Database [ query blast/getDatabaseOptions for options :- Add database you need to use]', 406 );
          }
         if ( !isset( $data[ Max_target_sequences ] ) )
          {
            return services_error( 'Missing blast attribute $Max_target_sequences [ query blast/getDatabaseOptions for options ]', 406 );
          }
         if ( !isset( $data[ Word_size ] ) )
          {
            return services_error( 'Missing blast attribute $Word_size [ query blast/getDatabaseOptions for options ]', 406 );
          }
         if ( !isset( $data[ Match_Mismatch_Scores ] ) )
          {
            return services_error( 'Missing blast attribute $Match_Mismatch_Scores [ query blast/getDatabaseOptions for options]', 406 );
          }
         if ( !isset( $data[ Gap_costs ] ) )
          {
            return services_error( 'Missing blast attribute $Gap_Costs [ query blast/getDatabaseOptions for options ]', 406 );
          }
         if ( isset( $data[ seqQuery ] ) )
          {
            if ( validate_fasta_sequence( $query_type, $data[ seqQuery ] ) == 1 )
             {
               return services_error( 'Please enter validate_fasta_sequence', 406 );
             }
          }
         else
          {
            return services_error( 'Missing blast attribute data[seqQuery]', 406 );
          }
       }
      else
       {

         if ( !isset( $data[ Select_Database ] ) )
          {
            return services_error( 'Missing blast attribute $Select_Database [ query blast/getDatabaseOptions for options :- Add database used by you]', 406 );
          }
         if ( !isset( $data[ Max_target_sequences ] ) )
          {
            return services_error( 'Missing blast attribute $Max_target_sequences [ query blast/getDatabaseOptions for options ]', 406 );
          }
         if ( !isset( $data[ Word_size ] ) )
          {
            return services_error( 'Missing blast attribute $Word_size [ query blast/getDatabaseOptions for options ]', 406 );
          }
         if ( !isset( $data[ Matrix_options ] ) )
          {
            return services_error( 'Missing blast attribute $Matrix_options [ query blast/getDatabaseOptions for options ]', 406 );
          }
         if ( isset( $data[ seqQuery ] ) )
          {
            if ( validate_fasta_sequence( $query_type, $data[ seqQuery ] ) == 1 )
             {
               return services_error( 'Please enter validate_fasta_sequence', 406 );
             }
          }
         else
          {
            return services_error( 'Missing blast attribute data[seqQuery]', 406 );
          }

       }
    }
   else
    {
      if ( $db_type == 'nucleotide' )
       {
         if ( !isset( $data[ Select_Database ] ) )
          {
            return services_error( 'Missing blast attribute $Select_Database [ query blast/getDatabaseOptions for options :- Add database you need to use]', 406 );
          }
         if ( !isset( $data[ Max_target_sequences ] ) )
          {
            return services_error( 'Missing blast attribute $Max_target_sequences [ query blast/getDatabaseOptions for options ]', 406 );
          }
         if ( !isset( $data[ Word_size ] ) )
          {
            return services_error( 'Missing blast attribute $Word_size [ query blast/getDatabaseOptions for options ]', 406 );
          }
         if ( !isset( $data[ Matrix_options ] ) )
          {
            return services_error( 'Missing blast attribute $Matrix_options [ query blast/getDatabaseOptions for options ]', 406 );
          }
         if ( !isset( $data[ Gap_costs ] ) )
          {
            return services_error( 'Missing blast attribute [ query blast/getDatabaseOptions for options ]', 406 );
          }

         if ( isset( $data[ seqQuery ] ) )
          {
            if ( validate_fasta_sequence( $query_type, $data[ seqQuery ] ) == 1 )
             {
               return services_error( 'Please enter validate_fasta_sequence', 406 );
             }
          }
         else
          {
            return services_error( 'Missing blast attribute data[seqQuery]', 406 );
          }
       }
      else
       {

         //return $data;

         if ( !isset( $data[ Select_Database ] ) )
          {
            return services_error( 'Missing blast attribute $Select_Database [ query blast/getDatabaseOptions for options :- Add database used by you]', 406 );
          }
         if ( !isset( $data[ Max_target_sequences ] ) )
          {
            return services_error( 'Missing blast attribute $Max_target_sequences [ query blast/getDatabaseOptions for options ]', 406 );
          }
         if ( !isset( $data[ Word_size ] ) )
          {
            return services_error( 'Missing blast attribute $Word_size [ query blast/getDatabaseOptions for options ]', 406 );
          }
         if ( !isset( $data[ Matrix_options ] ) )
          {
            return services_error( 'Missing blast attribute $Matrix_options [ query blast/getDatabaseOptions for options ]', 406 );
          }
         if ( isset( $data[ seqQuery ] ) )
          {
            if ( validate_fasta_sequence( $query_type, $data[ seqQuery ] ) == 1 )
             {
               return services_error( 'Please enter validate_fasta_sequence', 406 );
             }
          }
         else
          {
            return services_error( 'Missing blast attribute data[seqQuery]', 406 );
          }
       }
    }
 }

/*
 ** Method call the blast_ui_getblastJobId by setting all the needed
 ** parameters
 ** web service url :  http://localhost:8080/restapi/blast/getJobId.json
 */


function _blast_ui_getJobId( $data )
 {

   $query_type;
   $db_type;
   // Setting the default value of qRange and eValue from blast_ui.admin
   $eVal   = variable_get( 'eVal', '' );
   $qRange = variable_get( 'qRange', '' );

   /* check values in post query */

   if ( !isset( $data[ query_type ] ) )
    {
      return services_error( 'Missing blast attribute $query_type [ set necleotide or protein in post request of query_type ]', 406 );
    }
   if ( !isset( $data[ db_type ] ) )
    {
      return services_error( 'Missing blast attribute $db_type [ set necleotide or protein in post request of db_type ]', 406 );
    }

   $query_type = $data[ query_type ];
   $db_type    = $data[ db_type ];

   // check the user input in $data
   blast_validation( $query_type, $db_type, $data );


   if ( isset( $data[ $eVal ] ) )
    {
      $eVal = $data[ $eVal ];
    }
   if ( isset( $data[ $culling_limit ] ) )
    {
      $qRange = $data[ $culling_limit ];
    }

   if ( $query_type == 'nucleotide' )
    {
      if ( $db_type == 'nucleotide' )
       {
         $blast_program    = 'blastn';
         // code not working for wordvalue = 7 ?
         $options_database = get_blast_database_options( $data[ db_type ] );
         $Databasekey      = array_search( $data[ Select_Database ], $options_database );
         $advanced_options = advanced_options( $blast_program, $eVal, $qRange, $data );

       }
      elseif ( $db_type == 'protein' )
       {
         $blast_program    = 'blastx';
         // how to get gap cost - question
         $options_database = get_blast_database_options( $data[ db_type ] );
         $Databasekey      = array_search( $data[ Select_Database ], $options_database );
         $advanced_options = advanced_options( $blast_program, $eVal, $qRange, $data );
       }
    }
   elseif ( $query_type == 'protein' )
    {
      if ( $db_type == 'nucleotide' )
       {
         $blast_program    = 'tblastn';
         $options_database = get_blast_database_options( $data[ db_type ] );
         $Databasekey      = array_search( $data[ Select_Database ], $options_database );
         $advanced_options = advanced_options( $blast_program, $eVal, $qRange, $data );

       }
      elseif ( $db_type == 'protein' )
       {
         $blast_program    = 'blastp';
         $options_database = get_blast_database_options( $data[ db_type ] );
         $Databasekey      = array_search( $data[ Select_Database ], $options_database );
         $advanced_options = advanced_options( $blast_program, $eVal, $qRange, $data );
       }
    }


   return blast_ui_getblastJobId( $data[ query_type ], $data[ db_type ], $data[ seqQuery ], $Databasekey, $advanced_options );


 }
/*
 ** Define the advanced Options for Blast
 ** @return : advanced_options according to blast_program
 */
function advanced_options( $blast_program, $eVal, $qRange, $data )
 {

   if ( $blast_program == 'blastn' )
    {
      $wordvalue = intval( $data[ Word_size ] );
      $numAlign  = intval( $data[ Max_target_sequences ] );

      $Data_GapCost = _get_gap( $blast_program );
      $gapkey       = array_search( $data[ Gap_Costs ], $Data_GapCost );

      $gap                  = _set_gap( $gapkey );
      $Data_Mismatch_Scores = _get_match_mismatch( $blast_program );

      $Mismatch_Scores_key = array_search( $data[ Match_Mismatch_Scores ], $Data_Mismatch_Scores );
      $m_m                 = _set_match_mismatch( $Mismatch_Scores_key );

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
   elseif ( $blast_program == 'blastx' )
    {
      $wordSize = intval( $data[ Word_size ] );
      $numAlign = intval( $data[ Max_target_sequences ] );
      $matrix   = $data[ Matrix_options ];
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
   elseif ( $blast_program == 'tblastn' )
    {
      $wordSize = intval( $data[ Word_size ] );
      $numAlign = intval( $data[ Max_target_sequences ] );
      $matrix   = $data[ Matrix_options ];
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
   elseif ( $blast_program == 'blastp' )
    {
      $wordSize = intval( $data[ Word_size ] );
      $numAlign = intval( $data[ Max_target_sequences ] );
      $matrix   = $data[ Matrix_options ];
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

function blast_ui_getblastJobId( $query_type, $db_type, $seqQuery, $Databasekey, $adv_options )
 {
   if ( $query_type == 'nucleotide' )
    {
      if ( $db_type == 'nucleotide' )
       {
         $blast_program = 'blastn';
       }
      elseif ( $db_type == 'protein' )
       {
         $blast_program = 'blastx';
       }
    }
   elseif ( $query_type == 'protein' )
    {
      if ( $db_type == 'nucleotide' )
       {
         $blast_program = 'tblastn';
       }
      elseif ( $db_type == 'protein' )
       {
         $blast_program = 'blastp';
       }
    }

   $query = '/tmp/' . date( 'YMd_His' ) . '_query.fasta';
   file_put_contents( $query, $seqQuery );
   $blastdb_node      = node_load( $Databasekey );
   $blastdb_name      = $blastdb_node->db_name;
   $blastdb_with_path = $blastdb_node->db_path;

   // Now let each program process its own advanced options.


   $advanced_options = $adv_options;
   global $user;

   $output_filestub = date( 'YMd_His' );
   $job_args        = array(
       'program' => $blast_program,
      'query' => $query,
      'database' => $blastdb_with_path,
      'output_filename' => $output_filestub,
      'options' => $advanced_options
   );

   $job_id = tripal_add_job( t( 'BLAST (@program): @query', array(
       '@program' => $blast_program,
      '@query' => $query
   ) ), 'blast_job', 'run_BLAST_tripal_job', $job_args, $user->uid );

   ob_start();
   tripal_launch_job( 1, $job_id );
   ob_end_clean();
   return $job_id;
   // return getBlastData($job_id);
 }







function getBlaststatus( $job_id )
 {
   /* $sql =  "SELECT status FROM tripal_jobs WHERE job_id = :job_id ";
   $job_res = db_query($sql, array(':job_id' => $job_id))->fetchField();
   return $job_res;  */
 }


/*
 **Method returns file output data
 **  @param int $job_id
 **  web service url :  http://localhost:8080/restapi/blast/job_id
 */

function getBlastData( $job_id )
 {
   $result;
   $sql     = "SELECT job_name FROM tripal_jobs WHERE job_id = :job_id ";
   $job_res = db_query( $sql, array(
       ':job_id' => $job_id
   ) )->fetchField();

   $file_path = str_replace( '_query.fasta', '.blast.asn', $job_res );


   if ( stristr( $file_path, "tblastn" ) )
    {
      $file_path = str_replace( 'BLAST (tblastn): /', '/', $file_path );
    }
   elseif ( stristr( $file_path, "blastx" ) )
    {
      $file_path = str_replace( 'BLAST (blastx): /', '/', $file_path );
    }
   elseif ( stristr( $file_path, "blastn" ) )
    {
      $file_path = str_replace( 'BLAST (blastn): /', '/', $file_path );
    }
   elseif ( stristr( $file_path, "blastp" ) )
    {
      $file_path = str_replace( 'BLAST (blastp): /', '/', $file_path );
    }


   if ( file_exists( $file_path ) )
    {
      $result = file_get_contents( $file_path );
    }
   else
    {
      $result = "The file  $file_path does not exist";
    }
   return $result;
 }

/*
 **Method Used for tblastn and blastx
 **  @return  int $gapOpen and $gapExtend
 */

function getGap( $matrix, $gapkey )
 {
   switch ( $matrix )
   {
      case 'PAM30':
         switch ( $gapKey )
         {
            case 0:
               $gapOpen   = 7;
               $gapExtend = 2;
               break;
            case 1:
               $gapOpen   = 6;
               $gapExtend = 2;
               break;
            case 2:
               $gapOpen   = 5;
               $gapExtend = 2;
               break;
            case 3:
               $gapOpen   = 10;
               $gapExtend = 1;
               break;
            case 4:
               $gapOpen   = 9;
               $gapExtend = 1;
               break;
            case 5:
               $gapOpen   = 8;
               $gapExtend = 1;
               break;
         }
         break;
      case 'PAM70':
         switch ( $gapKey )
         {
            case 0:
               $gapOpen   = 8;
               $gapExtend = 2;
               break;
            case 1:
               $gapOpen   = 7;
               $gapExtend = 2;
               break;
            case 2:
               $gapOpen   = 6;
               $gapExtend = 2;
               break;
            case 3:
               $gapOpen   = 11;
               $gapExtend = 1;
               break;
            case 4:
               $gapOpen   = 10;
               $gapExtend = 1;
               break;
            case 5:
               $gapOpen   = 9;
               $gapExtend = 1;
               break;
         }
         break;
      case 'PAM250':
         switch ( $gapKey )
         {
            case 0:
               $gapOpen   = 15;
               $gapExtend = 3;
               break;
            case 1:
               $gapOpen   = 14;
               $gapExtend = 3;
               break;
            case 2:
               $gapOpen   = 13;
               $gapExtend = 3;
               break;
            case 3:
               $gapOpen   = 12;
               $gapExtend = 3;
               break;
            case 4:
               $gapOpen   = 11;
               $gapExtend = 3;
               break;
            case 5:
               $gapOpen   = 17;
               $gapExtend = 2;
               break;
            case 6:
               $gapOpen   = 16;
               $gapExtend = 2;
               break;
            case 7:
               $gapOpen   = 15;
               $gapExtend = 2;
               break;
            case 8:
               $gapOpen   = 14;
               $gapExtend = 2;
               break;
            case 9:
               $gapOpen   = 13;
               $gapExtend = 2;
               break;
            case 10:
               $gapOpen   = 21;
               $gapExtend = 1;
               break;
            case 11:
               $gapOpen   = 20;
               $gapExtend = 1;
               break;
            case 12:
               $gapOpen   = 19;
               $gapExtend = 1;
               break;
            case 13:
               $gapOpen   = 18;
               $gapExtend = 1;
               break;
            case 14:
               $gapOpen   = 17;
               $gapExtend = 1;
               break;
         }
         break;
      case 'BLOSUM80':
         switch ( $gapKey )
         {
            case 0:
               $gapOpen   = 8;
               $gapExtend = 2;
               break;
            case 1:
               $gapOpen   = 7;
               $gapExtend = 2;
               break;
            case 2:
               $gapOpen   = 6;
               $gapExtend = 2;
               break;
            case 3:
               $gapOpen   = 11;
               $gapExtend = 1;
               break;
            case 4:
               $gapOpen   = 10;
               $gapExtend = 1;
               break;
            case 5:
               $gapOpen   = 9;
               $gapExtend = 1;
               break;
         }
         break;
      case 'BLOSUM62':
         switch ( $gapKey )
         {
            case 0:
               $gapOpen   = 11;
               $gapExtend = 2;
               break;
            case 1:
               $gapOpen   = 10;
               $gapExtend = 2;
               break;
            case 2:
               $gapOpen   = 9;
               $gapExtend = 2;
               break;
            case 3:
               $gapOpen   = 8;
               $gapExtend = 2;
               break;
            case 4:
               $gapOpen   = 7;
               $gapExtend = 2;
               break;
            case 5:
               $gapOpen   = 6;
               $gapExtend = 2;
               break;
            case 6:
               $gapOpen   = 13;
               $gapExtend = 1;
               break;
            case 7:
               $gapOpen   = 12;
               $gapExtend = 1;
               break;
            case 8:
               $gapOpen   = 11;
               $gapExtend = 1;
               break;
            case 9:
               $gapOpen   = 10;
               $gapExtend = 1;
               break;
            case 10:
               $gapOpen   = 9;
               $gapExtend = 1;
               break;
         }
         break;
      case 'BLOSUM45':
         switch ( $gapKey )
         {
            case 0:
               $gapOpen   = 13;
               $gapExtend = 3;
               break;
            case 1:
               $gapOpen   = 12;
               $gapExtend = 3;
               break;
            case 2:
               $gapOpen   = 11;
               $gapExtend = 3;
               break;
            case 3:
               $gapOpen   = 10;
               $gapExtend = 3;
               break;
            case 4:
               $gapOpen   = 15;
               $gapExtend = 2;
               break;
            case 5:
               $gapOpen   = 14;
               $gapExtend = 2;
               break;
            case 6:
               $gapOpen   = 13;
               $gapExtend = 2;
               break;
            case 7:
               $gapOpen   = 12;
               $gapExtend = 2;
               break;
            case 8:
               $gapOpen   = 19;
               $gapExtend = 1;
               break;
            case 9:
               $gapOpen   = 18;
               $gapExtend = 1;
               break;
            case 10:
               $gapOpen   = 17;
               $gapExtend = 1;
               break;
            case 11:
               $gapOpen   = 16;
               $gapExtend = 1;
               break;
         }
         break;
      case 'BLOSUM50':
         switch ( $gapKey )
         {
            case 0:
               $gapOpen   = 13;
               $gapExtend = 3;
               break;
            case 1:
               $gapOpen   = 12;
               $gapExtend = 3;
               break;
            case 2:
               $gapOpen   = 11;
               $gapExtend = 3;
               break;
            case 3:
               $gapOpen   = 10;
               $gapExtend = 3;
               break;
            case 4:
               $gapOpen   = 9;
               $gapExtend = 3;
               break;
            case 5:
               $gapOpen   = 16;
               $gapExtend = 2;
               break;
            case 6:
               $gapOpen   = 15;
               $gapExtend = 2;
               break;
            case 7:
               $gapOpen   = 14;
               $gapExtend = 2;
               break;
            case 8:
               $gapOpen   = 13;
               $gapExtend = 2;
               break;
            case 9:
               $gapOpen   = 12;
               $gapExtend = 2;
               break;
            case 10:
               $gapOpen   = 19;
               $gapExtend = 1;
               break;
            case 11:
               $gapOpen   = 18;
               $gapExtend = 1;
               break;
            case 12:
               $gapOpen   = 17;
               $gapExtend = 1;
               break;
            case 13:
               $gapOpen   = 16;
               $gapExtend = 1;
               break;
            case 14:
               $gapOpen   = 15;
               $gapExtend = 1;
               break;
         }
         break;
      case 'BLOSUM90':
         switch ( $gapKey )
         {
            case 0:
               $gapOpen   = 9;
               $gapExtend = 2;
               break;
            case 1:
               $gapOpen   = 8;
               $gapExtend = 2;
               break;
            case 2:
               $gapOpen   = 7;
               $gapExtend = 2;
               break;
            case 3:
               $gapOpen   = 6;
               $gapExtend = 2;
               break;
            case 4:
               $gapOpen   = 11;
               $gapExtend = 1;
               break;
            case 5:
               $gapOpen   = 10;
               $gapExtend = 1;
               break;
            case 6:
               $gapOpen   = 9;
               $gapExtend = 1;
               break;
         }
         break;

   }
   return array(
       'gapOpen' => $gapOpen,
      'gapExtend' => $gapExtend
   );
 }

/*
 ** Used for tblastn
 */
function getGap_cost()
 {
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
