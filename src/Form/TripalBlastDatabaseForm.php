<?php
/**
 * @file 
 * This is the controller for Tripal BLAST Configuration form. 
 */

namespace Drupal\tripal_blast\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the Example add and edit forms.
 * @see and credits to: https://www.drupal.org/node/1809494
 */
class TripalBlastDatabaseForm extends EntityForm {
  /**
   * Constructs an ExampleForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entityTypeManager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $blast_db = $this->entity;
    
    //
    // # BLAST DATABASE NAME:
    $form['fld_text_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tripal BLAST database name'),
      '#description' => $this->t('The human-readable name of the BLAST database.'),
      '#required' => TRUE,
      '#default_value' => $blast_db->getName()
    ];

    //
    // # BLAST DATABASE PATH:
    $form['fld_text_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Database source path'),
      '#description' => $this->t('The full path and filename prefix of the BLAST database.'),
      '#required' => TRUE,
      '#default_value' => $blast_db->getPath()
    ];  

    //
    // # BLAST DATABASE TYPE:
    $form['fld_text_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Database type'),
      '#options' => ['n' => 'Nucleotide', 'p' => 'Protein'],
      '#description' => $this->t('Type of the blast database (Nucleotide or Protein).'),
      '#default_value' => $blast_db->getDbType()
    ];

    //
    // # REGULAR EXPRESSION AND DBXREF:
    $form['regular_expression'] = [
      '#type' => 'details',
      '#title' => $this->t('Regular Expression Key and Database Reference'),
      '#open' => TRUE
    ];
      
      //
      // # REGULAR EXPRESSION:
      $form['regular_expression']['fld_text_dbxref_id_regexp'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Extract Regular Expression'),
        '#description' => $this->t('The Regular Expression to use to extract the id from the FASTA header of the BLAST database hit.'),
        '#required' => TRUE,
        '#default_value' => $blast_db->getDbXrefRegExp()  
      ];

      //
      // # BLAST DATABASE REFERENCE:
      $form['regular_expression']['fld_text_dbxref_db_id'] = [
        '#type' => 'textfield',
        '#title' => $this->t('BLAST database reference'),
        '#description' => $this->t('The Database records from this BLAST Database reference.'),
        '#required' => TRUE,
        '#default_value' => $blast_db->getDbXref()    
      ];
    
      //
      // # BLAST DATABASE REFERENCE LINKOUT:
      $form['regular_expression']['fld_text_dbxref_linkout_type'] = [
        '#type' => 'textfield',
        '#title' => $this->t('BLAST database reference linkout type'),
        '#description' => $this->t('Type of linkout to be used for this database reference.'),
        '#required' => TRUE,
        '#default_value' => $blast_db->getDbXrefLinkout()    
      ];

    // # SUPPORT CVITJS:
    $form['fld_checkbox_cvitjs'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Visualize using CVITJS'),
      '#description' => $this->t('Indicate if CViTjs should be used to display hits on a whole genome.'),
      '#default_value' => $blast_db->getCvitjsEnabled()
    ]; 
          
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $blast_db = $this->entity;

    $blast_db->set('id', hexdec(uniqid()));
    // Database Name.
    $dbname = $form_state->getValue('fld_text_name');
    $dbname = trim($dbname);
    $blast_db->set('name', $dbname);
    // Database Path.
    $dbpath = $form_state->getValue('fld_text_path');
    $dbpath = trim($dbpath);
    $blast_db->set('path', $dbpath);
    // Database Type.
    $dbtype = $form_state->getValue('fld_text_type');
    $dbtype = trim($dbtype);
    $blast_db->set('dbtype', $dbtype);
    // Database REGEXP.
    $dbregexp = $form_state->getValue('fld_text_dbxref_id_regexp');
    $dbregexp = trim($dbregexp);
    $blast_db->set('dbxref_id_regexp', $dbregexp);
    // Database XRef.
    $dbxref = $form_state->getValue('fld_text_dbxref_db_id');
    $dbxref = trim($dbxref);
    $blast_db->set('dbxref_db_id', $dbxref);
    // Database Linkout.
    $dblinkout = $form_state->getValue('fld_text_dbxref_linkout_type');
    $dblinkout = trim($dblinkout);
    $blast_db->set('dbxref_linkout_type', $dblinkout);
    // CVITJS Support.
    $cvitjs_enabled = $form_state->getValue('fld_checkbox_cvitjs');
    $blast_db->set('cvitjs_enabled', $cvitjs_enabled);

    $blast_db->save();
    $form_state->setRedirect('entity.tripal_blast.blast_database');
  }
}