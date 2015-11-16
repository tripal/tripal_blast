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
            'create' => array(
                'help' => 'Creates a blast_ui',
                'callback' => '_blast_ui_create',
                'access arguments' => array(
                    'access content'
                ),
                'access arguments append' => false,
                'args' => array(
                    array(
                        'name' => 'param',
                        'type' => 'array',
                        'description' => 'The information needed',
                        'source' => 'data',
                        'optional' => FALSE
                    )
                )
            ),
            'update' => array(
                'help' => 'Updates a blast_ui',
                'callback' => '_blast_ui_update',
                'access callback' => 'user_access',
                'access arguments' => array(
                    'access user profiles'
                ),
                'args' => array(
                    array(
                        'name' => 'id',
                        'type' => 'int',
                        'description' => 'The information needed',
                        'source' => array(
                            'path' => '0'
                        ),
                        'optional' => FALSE
                    ),
                    array(
                        'name' => 'data',
                        'type' => 'struct',
                        'description' => 'The information needed',
                        'source' => 'data',
                        'optional' => FALSE
                    )
                )
            ),
            'delete' => array(
                'help' => 'Deletes a blast_ui',
                'callback' => '_blast_ui_delete',
                'access callback' => 'user_access',
                'access arguments' => array(
                    'access content'
                ),
                'access arguments append' => TRUE,
                'args' => array(
                    array(
                        'name' => 'nid',
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
                'args' => array()
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





function _blast_ui_create($param)
{
    
    return services_error('Missing _blast_ui_create', 406);
}


/**
 * Callback for updating blast services.
 *
 * @param int $id
 * @param object $data
 * @return object
 */
function _blast_ui_update($id, $data)
{
    return services_error('Missing _blast_ui_update', 406);
}
/**
 * Callback for retrieving blast services.
 *
 * @param int $id
 * @return object
 */
function _blast_ui_retrieve($id)
{
    return getBlastData($id);
}

/**
 * Callback for deleting blast services.
 *
 * @param int $id
 * @return object
 */
function _blast_ui_delete($id)
{
    return services_error('Missing _blast_ui_delete', 406);
}

/**
 * Callback for Index blast services.
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



function _blast_ui_getDatabaseOption($data)
{
    
    if (!isset($data[ query_type ]))
    {
        return services_error('Missing blast attribute $query_type [ set necleotide or protein in post request of query_type ]', 406);
    }
    if (!isset($data[ db_type ]))
    {
        return services_error('Missing blast attribute $db_type [ set necleotide or protein in post request of db_type ]', 406);
    }
    if (!isset($data[ blast_program ]))
    {
        return services_error('Missing blast attribute $blast_program [ set blastn, blastx, tblastn, blastp in post request of blast_program ]', 406);
    }
    
    $query_type       = $data[ db_type ];
    $db_type          = $data[ db_type ];
    $blast_program    = $data[ blast_program ];
    $options_database = get_blast_database_options($db_type);
    
    
    
    if ($blast_program == 'blastn')
    {
        return array(
            'Select_Database' => $options_database,
            'Max_target_sequences' => _get_max_target($blast_program),
            'Word_size' => _get_word_size($blast_program),
            'Match_Mismatch_Scores' => _get_match_mismatch($blast_program),
            'Gap_Costs' => _get_gap($blast_program),
            'Default_Values' => _get_default_values('')
        );
    }
    elseif ($blast_program == 'blastx')
    {
        return array(
            'Select_Database' => $options_database,
            'Max_target_sequences' => _get_max_target($blast_program),
            'Word_size' => _get_word_size($blast_program),
            'Matrix_options' => _get_matrix_options()
            
        );
    }
}


function blast_validation($query_type, $db_type, $data)
{
    
    if ($query_type == 'nucleotide')
    {
        if ($db_type == 'nucleotide')
        {
            
            if (!isset($data[ Select_Database ]))
            {
                return services_error('Missing blast attribute $Select_Database [ query blast/getDatabaseOptions for options :- Add database you need to use]', 406);
            }
            if (!isset($data[ Max_target_sequences ]))
            {
                return services_error('Missing blast attribute $Max_target_sequences [ query blast/getDatabaseOptions for options ]', 406);
            }
            if (!isset($data[ Word_size ]))
            {
                return services_error('Missing blast attribute $Word_size [ query blast/getDatabaseOptions for options ]', 406);
            }
            if (!isset($data[ Match_Mismatch_Scores ]))
            {
                return services_error('Missing blast attribute $Match_Mismatch_Scores [ query blast/getDatabaseOptions for options]', 406);
            }
            if (!isset($data[ Gap_Costs ]))
            {
                return services_error('Missing blast attribute $Gap_Costs [ query blast/getDatabaseOptions for options ]', 406);
            }
            if (!isset($data[ Default_Values ]))
            {
                return services_error('Missing blast attribute $Default_Values [ query blast/getDatabaseOptions for options]', 406);
            }
            if (!isset($data[ seqQuery ]))
            {
                return services_error('Missing blast attribute  $seqQuery [ Add sequence query for blast]', 406);
            }
            
        }
        elseif ($db_type == 'protein')
        {
            
            if (!isset($data[ Select_Database ]))
            {
                return services_error('Missing blast attribute $Select_Database [ query blast/getDatabaseOptions for options :- Add database used by you]', 406);
            }
            if (!isset($data[ Max_target_sequences ]))
            {
                return services_error('Missing blast attribute $Max_target_sequences [ query blast/getDatabaseOptions for options ]', 406);
            }
            if (!isset($data[ Word_size ]))
            {
                return services_error('Missing blast attribute $Word_size [ query blast/getDatabaseOptions for options ]', 406);
            }
            if (!isset($data[ Matrix_options ]))
            {
                return services_error('Missing blast attribute $Matrix_options [ query blast/getDatabaseOptions for options ]', 406);
            }
            if (!isset($data[ seqQuery ]))
            {
                return services_error('Missing blast attribute  $seqQuery [ Add sequence query for blast]', 406);
            }
            
        }
    }
    /*
    elseif ($query_type == 'protein') {
    if ($db_type == 'nucleotide') {
    $blast_program = 'tblastn';
    }
    elseif ($db_type == 'protein') {
    $blast_program = 'blastp';
    }
    }
    
    
    */
    
}




function _blast_ui_getJobId($data)
{
    
    $query_type;
    $db_type;
    $eVal   = 0.001;
    $qRange = 0;
    
    
    
    
    /* check values in post query */
    
    if (!isset($data[ query_type ]))
    {
        return services_error('Missing blast attribute $query_type [ set necleotide or protein in post request of query_type ]', 406);
    }
    if (!isset($data[ db_type ]))
    {
        return services_error('Missing blast attribute $db_type [ set necleotide or protein in post request of db_type ]', 406);
    }
    
    $query_type = $data[ query_type ];
    $db_type    = $data[ db_type ];
    
    blast_validation($query_type, $db_type, $data);
    
    if (isset($data[ $eVal ]))
    {
        $eVal = $data[ $eVal ];
    }
    if (isset($data[ $culling_limit ]))
    {
        $qRange = $data[ $culling_limit ];
    }
    
    if ($query_type == 'nucleotide')
    {
        if ($db_type == 'nucleotide')
        {
            $blast_program    = 'blastn';
            // code not working for wordvalue = 7 ?
            $wordvalue        = 11;
            //     $wordvalue = intval($data[Word_size]);
            $numAlign         = intval($data[ Max_target_sequences ]);
            $options_database = get_blast_database_options($data[ db_type ]);
            $Databasekey      = array_search($data[ Select_Database ], $options_database);
            $advanced_options = advanced_options($blast_program, $data[ Gap_Costs ], $data[ Match_Mismatch_Scores ], $wordvalue, $numAlign, $eVal, $qRange);
            
        }
        elseif ($db_type == 'protein')
        {
            $blast_program = 'blastx';
            return 'working';
        }
    }
    elseif ($query_type == 'protein')
    {
        if ($db_type == 'nucleotide')
        {
            $blast_program = 'tblastn';
        }
        elseif ($db_type == 'protein')
        {
            $blast_program = 'blastp';
        }
    }
    
    
    return blast_ui_getblastJobId($data[ query_type ], $data[ db_type ], $data[ seqQuery ], $Databasekey, $advanced_options);
    
    
}

// Define the advanced Options for Blast 


function advanced_options($blast_program, $gapCost, $Match_Mismatch_Scores, $wordvalue, $numAlign, $eVal, $qRange)
{
    
    $Data_GapCost = _get_gap($blast_program);
    $gapkey       = array_search($gapCost, $Data_GapCost);
    $gap          = _set_gap($gapkey);
    
    
    $Data_Mismatch_Scores = _get_match_mismatch($blast_program);
    $Mismatch_Scores_key  = array_search($Match_Mismatch_Scores, $Data_Mismatch_Scores);
    $m_m                  = _set_match_mismatch($Mismatch_Scores_key);
    
    
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







function blast_ui_getblastJobId($query_type, $db_type, $seqQuery, $Databasekey, $adv_options)
{
    
    
    
    if ($query_type == 'nucleotide')
    {
        if ($db_type == 'nucleotide')
        {
            $blast_program = 'blastn';
        }
        elseif ($db_type == 'protein')
        {
            $blast_program = 'blastx';
        }
    }
    elseif ($query_type == 'protein')
    {
        if ($db_type == 'nucleotide')
        {
            $blast_program = 'tblastn';
        }
        elseif ($db_type == 'protein')
        {
            $blast_program = 'blastp';
        }
    }
    
    $query = '/tmp/' . date('YMd_His') . '_query.fasta';
    file_put_contents($query, $seqQuery);
    $blastdb_node      = node_load($Databasekey);
    $blastdb_name      = $blastdb_node->db_name;
    $blastdb_with_path = $blastdb_node->db_path;
    
    // Now let each program process its own advanced options.
    
    
    $advanced_options = $adv_options;
    global $user;
    
    $output_filestub = date('YMd_His');
    $job_args        = array(
        'program' => $blast_program,
        'query' => $query,
        'database' => $blastdb_with_path,
        'output_filename' => $output_filestub,
        'options' => $advanced_options
    );
    
    $job_id = tripal_add_job(t('BLAST (@program): @query', array(
        '@program' => $blast_program,
        '@query' => $query
    )), 'blast_job', 'run_BLAST_tripal_job', $job_args, $user->uid);
    
    ob_start();
    tripal_launch_job(1, $job_id);
    ob_end_clean();
    return $job_id;
    // return getBlastData($job_id);
}











function getBlaststatus($job_id)
{
    /* $sql =  "SELECT status FROM tripal_jobs WHERE job_id = :job_id ";
    $job_res = db_query($sql, array(':job_id' => $job_id))->fetchField();
    return $job_res;  */
}



function getBlastData($job_id)
{
    $result;
    $sql       = "SELECT job_name FROM tripal_jobs WHERE job_id = :job_id ";
    $job_res   = db_query($sql, array(
        ':job_id' => $job_id
    ))->fetchField();
    $file_path = str_replace('_query.fasta', '.blast.asn', $job_res);
    $file_path = str_replace('BLAST (blastn): /', '/', $file_path);
    
    if (file_exists($file_path))
    {
        $result = file_get_contents($file_path);
    }
    else
    {
        $result = "The file  $file_path does not exist";
    }
    return $result;
}





