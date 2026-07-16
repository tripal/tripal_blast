<?php

namespace Drupal\Tests\tripal_blast\Kernel;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Form\FormState;
use Drupal\file\Entity\File;
use Drupal\Tests\tripal_chado\Kernel\ChadoTestKernelBase;
use Drupal\Tests\tripal_blast\Traits\TripalBlastTestTrait;
use Drupal\tripal_blast\Form\TripalBlastForm;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Drupal\tripal_chado\Database\ChadoConnection;
use Drupal\Tests\tripal\Traits\TripalTestTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests the Tripal BLAST form.
 *
 * @group Tripal
 * @group TripalBlast
 * @group TripalBlastForm
 */
#[Group('tripal-blast')]
#[RunTestsInSeparateProcesses]
class TripalBlastFormTest extends ChadoTestKernelBase {
  use TripalBlastTestTrait;
  use UserCreationTrait;
  use TripalTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'path',
    'path_alias',
    'views',
    'file',
    'tripal',
    'tripal_chado',
    'tripal_blast',
  ];

  /**
   * Class instance of the Tripal Blast Form.
   *
   * @var \Drupal\tripal_blast\Form\TripalBlastForm
   */
  protected $blast_form;

  /**
   * The path to tripal_blast module.
   *
   * @var string
   */
  private $module_path;

  /**
   * A Database query interface for querying Chado using Tripal DBX.
   *
   * @var \Drupal\tripal_chado\Database\ChadoConnection
   */
  protected ChadoConnection $chado_connection;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    \Drupal::state()->set('is_a_test_environment', TRUE);

    // Create a test chado instance as needed by our service.
    $this->chado_connection = $this->createTestSchema(ChadoTestKernelBase::PREPARE_TEST_CHADO);
    $this->container->set('tripal_chado.database', $this->chado_connection);

    $this->installConfig(['tripal_blast', 'system', 'tripal_chado']);
    $this->installEntitySchema('path_alias');
    $this->installSchema('tripal', ['tripal_jobs']);
    $this->installSchema('tripal_blast', ['blastjob']);
    $this->installSchema('tripal_chado', ['tripal_custom_tables']);
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);
    $this->installEntitySchema('user');

    // Create and log-in a user.
    $this->setUpCurrentUser();

    $this->blast_form = TripalBlastForm::create($this->container);

    $this->module_path = $this->container->get('module_handler')
      ->getModule('tripal_blast')
      ->getPath();
  }

  /**
   * Tests that the form id is the expected value.
   */
  public function testGetFormId(): void {
    $this->assertSame('tripalblastform', $this->blast_form->getFormId());
  }

  /**
   * Tests that the build form contains the expected BLAST fields.
   */
  public function testBuildFormContainsExpectedFields(): void {
    $form = [];
    $form_state = new FormState();

    $build = $this->blast_form->buildForm($form, $form_state, 'nucleotide', 'nucleotide');

    $this->assertSame('nucleotide', $build['query_type']['#value'], 'We expect the query type to be nucleotide but it is not.');
    $this->assertSame('nucleotide', $build['db_type']['#value'], 'We expect the database type to be nucleotide but it is not.');
    $this->assertSame('blastn', $build['blast_program']['#value'], 'We expect the BLAST program to be blastn but it is not.');
    $this->assertSame('details', $build['B']['#type'], 'We expect the main container to be a details element but it is not.');
    $this->assertSame('details', $build['B']['query']['#type'], 'We expect the query container to be a details element but it is not.');
    $this->assertSame('textarea', $build['B']['query']['FASTA']['#type'], 'We expect the FASTA input to be a textarea but it is not.');
    $this->assertSame('select', $build['B']['db']['SELECT_DB']['#type'], 'We expect the database selection to be a select element but it is not.');
    $this->assertSame('submit', $build['B']['submit']['#type'], 'We expect the submit button to be a submit element but it is not.');
  }

  /**
   * Tests that each BLAST program builds the expected hidden values.
   */
  public function testBuildFormGeneratesAllBlastPrograms(): void {
    $programs = [
      ['query' => 'nucleotide', 'db' => 'nucleotide', 'expected' => 'blastn'],
      ['query' => 'nucleotide', 'db' => 'protein', 'expected' => 'blastx'],
      ['query' => 'protein', 'db' => 'nucleotide', 'expected' => 'tblastn'],
      ['query' => 'protein', 'db' => 'protein', 'expected' => 'blastp'],
    ];

    foreach ($programs as $program) {
      $form = [];
      $form_state = new FormState();
      $build = $this->blast_form->buildForm($form, $form_state, $program['query'], $program['db']);

      $this->assertSame($program['query'], $build['query_type']['#value'], 'Unexpected query type for ' . $program['expected']);
      $this->assertSame($program['db'], $build['db_type']['#value'], 'Unexpected database type for ' . $program['expected']);
      $this->assertSame($program['expected'], $build['blast_program']['#value'], 'Unexpected BLAST program for ' . $program['expected']);
      $this->assertArrayHasKey('B', $build, 'The main details container is missing for ' . $program['expected']);
      $this->assertArrayHasKey('submit', $build['B'], 'The submit button is missing for ' . $program['expected']);
    }
  }

  /**
   * Tests validation fails when the query and database are missing.
   */
  public function testValidateFormRejectsMissingQueryAndDatabase(): void {
    $form = [];
    $form_state = new FormState();
    $form_state->setValues([
      'blast_program' => 'blastn',
      'query_type' => 'nucleotide',
      'db_type' => 'nucleotide',
      'maxTarget' => '50',
      'eVal' => '1e-5',
      'wordSize' => '11',
      'M&MScores' => '1,-2',
      'gapCost' => '5,2',
    ]);

    $this->blast_form->validateForm($form, $form_state);

    $errors = $form_state->getErrors();
    $this->assertArrayHasKey('query', $errors, 'Expected an error for missing query but it was not found.');
    $this->assertArrayHasKey('db', $errors, 'Expected an error for missing database but it was not found.');
  }

  /**
   * Tests validation accepts a valid FASTA sequence and database selection.
   */
  public function testValidateFormAcceptsValidFastaAndDatabaseSelection(): void {
    $form = [];
    $form_state = new FormState();
    $form_state->setValues([
      'blast_program' => 'blastn',
      'query_type' => 'nucleotide',
      'db_type' => 'nucleotide',
      'FASTA' => ">seq\nACGT",
      'SELECT_DB' => '1',
      'maxTarget' => '500',
      'eVal' => '1e-5',
      'wordSize' => '11',
      'M&MScores' => '1,-2',
      'gapCost' => '5,2',
    ]);

    $this->blast_form->validateForm($form, $form_state);

    $this->assertSame([], $form_state->getErrors(), 'Unexpected validation errors found.');
    $this->assertSame('seqQuery', $form_state->getValue('qFlag'), 'Query flag was not set correctly.');
    $this->assertSame('blastdb', $form_state->getValue('dbFlag'), 'Database flag was not set correctly.');
  }

  /**
   * Tests validation succeeds for all BLAST programs.
   */
  public function testValidateFormAcceptsAllBlastPrograms(): void {
    $programs = [
      ['query' => 'nucleotide', 'db' => 'nucleotide', 'program' => 'blastn'],
      ['query' => 'nucleotide', 'db' => 'protein', 'program' => 'blastx'],
      ['query' => 'protein', 'db' => 'nucleotide', 'program' => 'tblastn'],
      ['query' => 'protein', 'db' => 'protein', 'program' => 'blastp'],
    ];

    foreach ($programs as $program) {
      $form = [];
      $form_state = new FormState();
      if ($program['program'] == 'blastn') {
        $form_state->setValues([
          'blast_program' => $program['program'],
          'query_type' => $program['query'],
          'db_type' => $program['db'],
          'FASTA' => ">seq\nACGT",
          'SELECT_DB' => '1',
          'maxTarget' => '500',
          'eVal' => '1e-5',
          'wordSize' => '11',
          'M&MScores' => '1,-2',
          'gapCost' => '5,2',
        ]);
      }
      else {
        $form_state->setValues([
          'blast_program' => $program['program'],
          'query_type' => $program['query'],
          'db_type' => $program['db'],
          'FASTA' => ">seq\nACGT",
          'SELECT_DB' => '1',
        ]);
      }

      $this->blast_form->validateForm($form, $form_state);

      $this->assertSame([], $form_state->getErrors(), 'Unexpected validation errors for ' . $program['program']);
      $this->assertSame('seqQuery', $form_state->getValue('qFlag'), 'Query flag was not set for ' . $program['program']);
      $this->assertSame('blastdb', $form_state->getValue('dbFlag'), 'Database flag was not set for ' . $program['program']);
    }
  }

  /**
   * Tests that form submission creates a BLAST job record for each program.
   */
  public function testSubmitFormCreatesJobRecordForAllBlastPrograms(): void {
    $programs = [
      ['query' => 'nucleotide', 'db' => 'nucleotide', 'program' => 'blastn'],
      ['query' => 'nucleotide', 'db' => 'protein', 'program' => 'blastx'],
      ['query' => 'protein', 'db' => 'nucleotide', 'program' => 'tblastn'],
      ['query' => 'protein', 'db' => 'protein', 'program' => 'blastp'],
    ];

    $editable_config = \Drupal::service('config.factory')->getEditable('tripal_blast.settings');
    $editable_config->set('tripal_blast_config_general.path', 'tmp/true');
    $editable_config->save();

    $fixture_dir = $this->module_path . '/tests/fixtures/Chlamydomonas_reinhardtii_v5.6';
    $nucleotide_db = $this->createBlastDatabase([
      'id' => 123450,
      'name' => 'Fixture nucleotide BLAST DB',
      'path' => $fixture_dir . '/Chlamydomonas_reinhardtii_v5.6.nin',
      'dbtype' => 'n',
    ]);
    $protein_db = $this->createBlastDatabase([
      'id' => 67890,
      'name' => 'Fixture protein BLAST DB',
      'path' => $fixture_dir . '/Chlamydomonas_reinhardtii_v5.6_protein.nin',
      'dbtype' => 'p',
    ]);

    foreach ($programs as $program) {
      $form = [];
      $form_state = new FormState();
      $selected_db_id = ($program['db'] === 'protein') ? $protein_db->getId() : $nucleotide_db->getId();

      if ($program['program'] == 'blastn') {
        $form_state->setValues([
          'blast_program' => $program['program'],
          'query_type' => $program['query'],
          'db_type' => $program['db'],
          'FASTA' => ">seq\nACGT",
          'SELECT_DB' => (string) $selected_db_id,
          'maxTarget' => '500',
          'eVal' => '1e-5',
          'wordSize' => '11',
          'M&MScores' => '1,-2',
          'gapCost' => '5,2',
        ]);
      } else {
        $form_state->setValues([
          'blast_program' => $program['program'],
          'query_type' => $program['query'],
          'db_type' => $program['db'],
          'FASTA' => ">seq\nACGT",
          'SELECT_DB' => (string) $selected_db_id,
        ]);
      }

      $this->blast_form->validateForm($form, $form_state);

      $before = (int) $this->chado_connection->select('blastjob')
        ->condition('blast_program', $program['program'])
        ->countQuery()
        ->execute()
        ->fetchField();

      $this->blast_form->submitForm($form, $form_state);

      $after = (int) \Drupal::database()->select('blastjob')
        ->condition('blast_program', $program['program'])
        ->countQuery()
        ->execute()
        ->fetchField();

      $this->assertSame($before + 1, $after, 'Submission did not create a job record for ' . $program['program']);
    }
  }

  /**
   * Tests buildForm covers warning, recent-job, and resubmit defaults.
   */
  public function testBuildFormCoversWarningsRecentJobsAndResubmitDefaults(): void {
    $editable_config = \Drupal::service('config.factory')->getEditable('tripal_blast.settings');
    $editable_config->set('tripal_blast_config_notification.warning_text', 'Temporary maintenance warning');
    $editable_config->set('tripal_blast_config_sequence.nucleotide', '>example\nACGT');
    $editable_config->save();

    $query_file = $this->container->get('file_system')->getTempDirectory() . '/resubmit_query.fasta';
    file_put_contents($query_file, ">query\nACGT");

    $job_service = $this->getMockBuilder(\Drupal\tripal_blast\Services\TripalBlastJobService::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['jobsCountRecentJobs', 'jobsCreateTable', 'jobsBlastRevealSecret', 'jobsGetJobByJobId'])
      ->getMock();
    $job_service->method('jobsCountRecentJobs')->willReturn(1);
    $job_service->method('jobsCreateTable')->willReturn(['#type' => 'table']);
    $job_service->method('jobsBlastRevealSecret')->willReturn(42);
    $job_service->method('jobsGetJobByJobId')->willReturn((object) [
      'blastdb' => (object) ['nid' => 99],
      'files' => (object) ['query' => $query_file],
    ]);
    $this->container->set('tripal_blast.job_service', $job_service);

    \Drupal::request()->query->set('resubmit', 'secret-id');

    $form = [];
    $form_state = new FormState();
    $build = $this->blast_form->buildForm($form, $form_state, 'nucleotide', 'nucleotide');

    $this->assertArrayHasKey('config_warning', $build, 'Expected a warning message in the form build but it was not found.');
    $this->assertArrayHasKey('A', $build, 'Expected a recent job container in the form build but it was not found.');
    $this->assertSame('table', $build['A']['recent_job']['#type'], 'Expected the recent job container to be a table but it is not.');
    $this->assertSame(99, $build['B']['db']['SELECT_DB']['#default_value'], 'Expected the default database value to be 99 but it is not.');
    $this->assertSame(">query\nACGT", $build['B']['query']['FASTA']['#default_value'], 'Expected the default query value to be the correct sequence but it is not.');
  }

  /**
   * Tests validation handles uploaded files and invalid advanced values.
   */
  public function testValidateFormHandlesUploadedFilesAndInvalidAdvancedValues(): void {

    $query = ">seq\nACGT";
    $query_file_uri = 'temporary://tripal-blast-query.fasta';

    file_put_contents(
      \Drupal::service('file_system')->realpath($query_file_uri),
      $query
    );
    $query_file = File::create([
      'uri' => $query_file_uri,
    ]);
    $query_file->save();

    $db_data = ">db\nACGT";
    $db_file_uri = 'temporary://tripal-blast-db.fasta';

    file_put_contents(
      \Drupal::service('file_system')->realpath($db_file_uri),
      $db_data
    );
    $db_file = File::create([
      'uri' => $db_file_uri,
    ]);
    $db_file->save();

    $query_file_id = $query_file->id();
    $db_file_id = $db_file->id();

    $form = [];
    $form_state = new FormState();
    $form_state->setValues([
      'blast_program' => 'blastn',
      'query_type' => 'nucleotide',
      'db_type' => 'nucleotide',
      'UPLOAD' => $query_file_id,
      'DBUPLOAD' => $db_file_id,
      'maxTarget' => '500',
      'eVal' => 'not-a-number',
      'wordSize' => '11',
      'M&MScores' => '1,-2',
      'gapCost' => '5,2',
    ]);

    $this->blast_form->validateForm($form, $form_state);

    $this->assertSame('upQuery', $form_state->getValue('qFlag'), 'Expected the query flag to be set to upQuery but it is not.');
    $this->assertSame('upDB', $form_state->getValue('dbFlag'), 'Expected the database flag to be set to upDB but it is not.');
    $this->assertNotEmpty($form_state->getErrors(), 'Expected validation errors but none were found.');
    $this->assertArrayHasKey('eVal', $form_state->getErrors(), 'Expected an error for invalid eVal but it was not found.');
  }

  /**
   * Tests submitForm reports a missing database selection.
   */
  public function testSubmitFormReportsMissingDatabaseSelection(): void {
    $form = [];
    $form_state = new FormState();
    $form_state->setValues([
      'blast_program' => 'blastn',
      'query_type' => 'nucleotide',
      'db_type' => 'nucleotide',
      'qFlag' => 'seqQuery',
      'FASTA' => ">seq\nACGT",
      'maxTarget' => '500',
      'eVal' => '1e-5',
      'wordSize' => '11',
      'M&MScores' => '1,-2',
      'gapCost' => '5,2',
    ]);

    $this->blast_form->submitForm($form, $form_state);

    $messages = \Drupal::messenger()->all();
    $this->assertNotEmpty($messages['error'], 'Expected an error message but none were found.');
  }

  /**
   * Tests submitForm uses an uploaded database and creates a job submission.
   */
  public function testSubmitFormUsesUploadedDatabaseAndCreatesJob(): void {
    $temp_dir = $this->container->get('file_system')->getTempDirectory() . '/tripal-blast-makeblastdb';
    if (!is_dir($temp_dir)) {
      mkdir($temp_dir, 0777, TRUE);
    }

    $makeblastdb_path = $temp_dir . '/makeblastdb';
    file_put_contents($makeblastdb_path, "#!/bin/sh\nexit 0\n");
    chmod($makeblastdb_path, 0755);

    $editable_config = \Drupal::service('config.factory')->getEditable('tripal_blast.settings');
    $editable_config->set('tripal_blast_config_general.path', $temp_dir . '/');
    $editable_config->save();

    $job_service = $this->getMockBuilder(\Drupal\tripal_blast\Services\TripalBlastJobService::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['createBlastJob', 'jobsBlastMakeSecret'])
      ->getMock();
    $job_service->expects($this->once())
      ->method('createBlastJob')
      ->willReturn(2024);
    $job_service->method('jobsBlastMakeSecret')->willReturn('encoded-job');
    $this->container->set('tripal_blast.job_service', $job_service);

    $form = [];
    $form_state = new FormState();
    $form_state->setValues([
      'blast_program' => 'blastn',
      'query_type' => 'nucleotide',
      'db_type' => 'nucleotide',
      'qFlag' => 'seqQuery',
      'FASTA' => ">seq\nACGT",
      'dbFlag' => 'upDB',
      'upDB_path' => $temp_dir . '/uploaded.fasta',
      'maxTarget' => '500',
      'eVal' => '1e-5',
      'wordSize' => '11',
      'M&MScores' => '1,-2',
      'gapCost' => '5,2',
    ]);

    $this->blast_form->submitForm($form, $form_state);

    $this->assertNotNull($form_state->getRedirect());
  }

  /**
   * Tests submitForm catches job creation errors.
   */
  public function testSubmitFormCatchesJobCreationErrors(): void {
    $job_service = $this->getMockBuilder(\Drupal\tripal_blast\Services\TripalBlastJobService::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['createBlastJob'])
      ->getMock();
    $job_service->expects($this->once())
      ->method('createBlastJob')
      ->willThrowException(new \RuntimeException('boom'));
    $this->container->set('tripal_blast.job_service', $job_service);

    $form = [];
    $form_state = new FormState();
    $form_state->setValues([
      'blast_program' => 'blastn',
      'query_type' => 'nucleotide',
      'db_type' => 'nucleotide',
      'qFlag' => 'seqQuery',
      'FASTA' => ">seq\nACGT",
      'SELECT_DB' => '1',
      'maxTarget' => '500',
      'eVal' => '1e-5',
      'wordSize' => '11',
      'M&MScores' => '1,-2',
      'gapCost' => '5,2',
    ]);

    $this->blast_form->submitForm($form, $form_state);

    $messages = \Drupal::messenger()->all();
    $this->assertNotEmpty($messages['error'], 'Expected an error message but none were found.');
  }

  /**
   * Tests AJAX callbacks update the form state.
   */
  public function testAjaxCallbacksUpdateTheForm(): void {
    $editable_config = \Drupal::service('config.factory')->getEditable('tripal_blast.settings');
    $editable_config->set('tripal_blast_config_sequence.nucleotide', '>example\nACGT');
    $editable_config->save();

    $form = [
      'B' => [
        'query' => [
          'FASTA' => ['#value' => ''],
        ],
      ],
    ];
    $form_state = new FormState();
    $form_state->setValues([
      'query_type' => 'nucleotide',
      'example_sequence' => TRUE,
      'blast_program' => 'blastn',
      'M&MScores' => '1,-2',
    ]);

    $updated_form = $this->blast_form->ajaxShowExampleSequenceCallback($form, $form_state);
    $this->assertSame('>example\nACGT', $updated_form['#value'], 'Expected the FASTA field to be updated with the example sequence but it was not.');
    $this->assertStringContainsString('tripal-blast-tip', $updated_form['#suffix'], 'Expected the FASTA field to have a tip suffix but it does not.');

    $response = $this->blast_form->ajaxFieldUpdateCallback($form, $form_state);
    $this->assertInstanceOf(AjaxResponse::class, $response);
    $this->assertNotEmpty($response->getCommands(), 'Expected the AJAX response to contain commands but it does not.');
  }

  /**
   * Provides data for testing validation errors for missing database and query.
   *
   * @return array
   *   An array of test cases, each containing form values and the expected error key.
   */
  public static function provideDataForValidateDatabaseAndQueryErrors(): array {
    return [
      'invalid fasta' => [
        [
          'blast_program' => 'blastn',
          'query_type' => 'nucleotide',
          'db_type' => 'nucleotide',
          'qFlag' => 'seqQuery',
          'FASTA' => '1234',
          'SELECT_DB' => '1',
          'maxTarget' => '500',
          'eVal' => '1e-5',
          'wordSize' => '11',
          'M&MScores' => '1,-2',
          'gapCost' => '5,2',
        ],
        'query',
      ],
      'invalid DBUPLOAD and SELECT_DB' => [
        [
          'blast_program' => 'blastn',
          'query_type' => 'nucleotide',
          'db_type' => 'nucleotide',
          'UPLOAD' => '1',
          'DBUPLOAD' => '500',
          'SELECT_DB' => '500',
          'maxTarget' => '500',
          'eVal' => '1e-5',
          'wordSize' => '11',
          'M&MScores' => '1,-2',
          'gapCost' => '5,2',
        ],
        'db',
      ],
    ];
  }

  /**
   * Tests validation fails for missing database and query.
   *
   * @param array $values
   *  The form values to validate.
   * @param string $key
   *  The key of the expected error.
   *
   * @dataProvider provideDataForValidateDatabaseAndQueryErrors
   */
  #[DataProvider('provideDataForValidateDatabaseAndQueryErrors')]
  public function testValidateDatabaseAndQueryErrors(array $values, string $key): void {
    $form = [];
    $form_state = new FormState();
    $form_state->setValues($values);

    $this->blast_form->validateForm($form, $form_state);

    $this->assertArrayHasKey($key, $form_state->getErrors(), 'Expected an error for invalid ' . $key . ' but it was not found.');
  }

  /**
   * Tests validation fails for an invalid database upload selection.
   */
  public function testValidateFormInvalidDBUploadSelection(): void {
    $form = [];
    $form_state = new FormState();
    $form_state->setValues([
      'blast_program' => 'blastn',
      'query_type' => 'nucleotide',
      'db_type' => 'nucleotide',
      'qFlag' => 'seqQuery',
      'FASTA' => ">seq\nACGT",
      'UPLOAD' => '1',
      'DBUPLOAD' => '500',
      'SELECT_DB' => '',
      'maxTarget' => '500',
      'eVal' => '1e-5',
      'wordSize' => '11',
      'M&MScores' => '1,-2',
      'gapCost' => '5,2',
    ]);

    $this->blast_form->validateForm($form, $form_state);

    $this->assertSame('blastdb', $form_state->getValue('dbFlag'), 'Expected the database flag to be set to blastdb but it is not.');
  }

  /**
   * Tests that submitForm correctly handles the query flag.
   */
  public function testSubmitFormQueryFlag(): void {
    $editable_config = \Drupal::service('config.factory')->getEditable('tripal_blast.settings');
    $editable_config->set('tripal_blast_config_general.path', 'tmp/true');
    $editable_config->save();

    $fixture_dir = $this->module_path . '/tests/fixtures/Chlamydomonas_reinhardtii_v5.6';
    $nucleotide_db = $this->createBlastDatabase([
      'id' => 123450,
      'name' => 'Fixture nucleotide BLAST DB',
      'path' => $fixture_dir . '/Chlamydomonas_reinhardtii_v5.6.nin',
      'dbtype' => 'n',
    ]);
    $form = [];
    $form_state = new FormState();
    $form_state->setValues([
      'blast_program' => 'blastn',
      'query_type' => 'nucleotide',
      'db_type' => 'nucleotide',
      'qFlag' => 'upQuery',
      'upQuery_path' => $nucleotide_db->getPath(),
      'FASTA' => ">seq\nACGT",
      'SELECT_DB' => (string) $nucleotide_db->getId(),
      'maxTarget' => '500',
      'eVal' => '1e-5',
      'wordSize' => '11',
      'M&MScores' => '1,-2',
      'gapCost' => '5,2',
    ]);
    $this->blast_form->submitForm($form, $form_state);

    $this->assertNotNull($form_state->getRedirect(), 'Expected a redirect after form submission but none was found.');
  }

}
