<?php

namespace Drupal\tripal_blast\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tripal_blast\TripalBlastLinkoutManager;
use Drupal\tripal_chado\Database\ChadoConnection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the Example add and edit forms.
 *
 * @see and credits to: https://www.drupal.org/node/1809494
 */
class TripalBlastDatabaseForm extends EntityForm {

  /**
   * A Database query interface for querying Chado using Tripal DBX.
   *
   * @var Drupal\tripal_chado\Database\ChadoConnection
   */
  protected ChadoConnection $chado_connection;

  /**
   * Manager for the linkout service for hit sequences.
   *
   * @var Drupal\tripal_blast\TripalBlastLinkoutManager
   */
  protected TripalBlastLinkoutManager $linkout_manager;

  /**
   * Constructs an ExampleForm object.
   *
   * @param Drupal\tripal_chado\Database\ChadoConnection $chado_connection
   *   The chado connection used to query chado.
   * @param Drupal\tripal_blast\TripalBlastLinkoutManager $linkout_manager
   *   The manager used to generate linkout URLs for blast hits.
   */
  public function __construct(
    ChadoConnection $chado_connection,
    TripalBlastLinkoutManager $linkout_manager,
  ) {
    $this->chado_connection = $chado_connection;
    $this->linkout_manager = $linkout_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tripal_chado.database'),
      $container->get('plugin.manager.tripal_blast_linkout'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $blast_db = $this->entity;

    // Retrieve the list of available linkout types as a select list.
    $linkout_list = $this->getLinkoutTypes();

    //
    // # BLAST DATABASE NAME:
    $form['fld_text_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tripal BLAST database name'),
      '#description' => $this->t('The human-readable name of the BLAST database.'),
      '#required' => TRUE,
      '#default_value' => $blast_db->getName(),
    ];

    //
    // # BLAST DATABASE ENTITY:
    $form['fld_entity_id'] = [
      '#type' => 'number',
      '#min' => 1,
      '#title' => $this->t('Site Entity ID'),
      '#description' => $this->t('An optional numeric entity ID of a page on this site describing this database.'),
      '#required' => FALSE,
      '#default_value' => $blast_db->getEntityId(),
    ];

    //
    // # BLAST DATABASE PATH:
    $form['fld_text_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Database source path'),
      '#description' => $this->t('The full path and filename prefix of the BLAST database.'),
      '#required' => TRUE,
      '#default_value' => $blast_db->getPath(),
      '#maxlength' => 255,
    ];

    //
    // # BLAST DATABASE TYPE:
    $form['fld_text_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Database type'),
      '#options' => ['n' => $this->t('Nucleotide'), 'p' => $this->t('Protein')],
      '#description' => $this->t('Type of the blast database (Nucleotide or Protein).'),
      '#default_value' => $blast_db->getDbType(),
    ];

    //
    // # REGULAR EXPRESSION AND DB REFERENCE:
    $form['regular_expression'] = [
      '#type' => 'details',
      '#title' => $this->t('Regular Expression Key and Database Reference'),
      '#open' => TRUE,
    ];

    //
    // # REGULAR EXPRESSION:
    $form['regular_expression']['fld_text_db_regexp'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Extract Regular Expression'),
      '#description' => $this->t('The Regular Expression to use to extract the id from the FASTA header of the BLAST database hit. For example, to capture the first word, use @example',
        ['@example' => '/^(\S+)/']),
      '#required' => FALSE,
      '#default_value' => $blast_db->getDbRegExp(),
    ];

    //
    // # BLAST DATABASE REFERENCE:
    $form['regular_expression']['fld_text_db_id'] = [
      '#type' => 'select',
      '#options' => $this->getDatabases(),
      '#title' => $this->t('BLAST database reference'),
      '#description' => $this->t('The external Database reference for this BLAST database. Only databases with a defined urlprefix are shown.'),
      '#required' => FALSE,
      '#default_value' => $blast_db->getDbId(),
    ];

    //
    // # BLAST DATABASE REFERENCE LINKOUT:
    $default_linkout = $blast_db->getDbLinkout();
    if (!$default_linkout) {
      $default_linkout = 'tripal_blast.linkout_service:none';
    }
    $form['regular_expression']['fld_text_db_linkout_type'] = [
      '#type' => 'select',
      '#title' => $this->t('BLAST database reference linkout type'),
      '#description' => $this->t('Type of linkout to be used for this database reference. The module defining the linkout type is shown in parentheses.'),
      '#required' => FALSE,
      '#options' => $linkout_list,
      '#default_value' => $default_linkout,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $blast_db = $this->entity;

    // Only set the id on the initial save.
    if (!$blast_db->id) {
      $blast_db->set('id', hexdec(uniqid()));
    }
    // Database Name.
    $dbname = $form_state->getValue('fld_text_name');
    $dbname = trim($dbname);
    $blast_db->set('name', $dbname);
    // Entity ID.
    $entity_id = $form_state->getValue('fld_entity_id');
    $blast_db->set('entity_id', $entity_id);
    // Database Path.
    $dbpath = $form_state->getValue('fld_text_path');
    $dbpath = trim($dbpath);
    $blast_db->set('path', $dbpath);
    // Database Type.
    $dbtype = $form_state->getValue('fld_text_type');
    $dbtype = trim($dbtype);
    $blast_db->set('dbtype', $dbtype);
    // Database REGEXP.
    $dbregexp = $form_state->getValue('fld_text_db_regexp');
    $dbregexp = trim($dbregexp);
    $blast_db->set('db_regexp', $dbregexp);
    // Database Reference.
    $db_id = $form_state->getValue('fld_text_db_id');
    $db_id = trim($db_id);
    $blast_db->set('db_id', $db_id);
    // Database Linkout.
    $db_linkout_type = $form_state->getValue('fld_text_db_linkout_type');
    $db_linkout_type = trim($db_linkout_type);
    $blast_db->set('db_linkout_type', $db_linkout_type);

    $blast_db->save();
    $form_state->setRedirect('entity.tripal_blast.blast_database');
  }

  /**
   * Retrieves a list of available linkout types from all plugins.
   *
   * @return array
   *   The list of linkout types suitable to use as values for a form select.
   */
  protected function getLinkoutTypes(): array {
    // Retrieve the list of available linkout types as a select list.
    $linkout_list = ['' => '- Select -'];
    $presort = [];
    foreach ($this->linkout_manager->getDefinitions() as $definition) {
      // Provider is the module providing the plugin.
      $provider = $definition['provider'];
      $id = $definition['id'];
      $label = $definition['label'];
      $description = $definition['description'];
      $weight = $definition['weight'];
      $presort[$weight][$id] = $label . ': ' . $description . ' (' . $provider . ')';
    }
    ksort($presort, SORT_NUMERIC);
    foreach ($presort as $item) {
      $linkout_list += $item;
    }
    return $linkout_list;
  }

  /**
   * Generates a list of chado databases suitable for a select element.
   *
   * @return array
   *   The list of databases suitable to use as values for a form select.
   */
  protected function getDatabases(): array {
    $query = $this->chado_connection->select('1:db', 'db');
    $query->fields('db', ['db_id', 'name']);
    $query->condition('[db].urlprefix', '', '!=');
    $query->isNotNull('[db].urlprefix');
    $query->orderBy('db.name');
    $results = $query->execute();
    $select_arr = ['' => '- Select -'];
    foreach ($results as $result) {
      $name_with_id = $result->name . ' (' . $result->db_id . ')';
      $select_arr[$name_with_id] = $name_with_id;
    }
    return $select_arr;
  }

}
