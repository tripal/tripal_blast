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
   * Provides data for testBuildForm.
   *
   * @return array
   *   An array of test scenarios, each containing query type, database type, expected program and expected title.
   *   - query_type: The type of the query (nucleotide or protein).
   *   - db_type: The type of the database (nucleotide or protein).
   *   - expected_program: The expected BLAST program based on the query and database types.
   *   - expected_title: The expected title of the current BLAST program based on the query and database types.
   */
  public static function provideDataForTestBuildForm(): array {
    return [
      'nucleotide blastn' => [
        [
          'query_type' => 'nucleotide',
          'db_type' => 'nucleotide',
          'expected_program' => 'blastn',
          'expected_title' => 'Nucleotide to Nucleotide BLAST (blastn)',
        ],
      ],
      'nucleotide blastx' => [
        [
          'query_type' => 'nucleotide',
          'db_type' => 'protein',
          'expected_program' => 'blastx',
          'expected_title' => 'Nucleotide to Protein BLAST (blastx)',
        ],
      ],
      'protein tblastn' => [
        [
          'query_type' => 'protein',
          'db_type' => 'nucleotide',
          'expected_program' => 'tblastn',
          'expected_title' => 'Protein to Nucleotide BLAST (tblastn)',
        ],
      ],
      'protein blastp' => [
        [
          'query_type' => 'protein',
          'db_type' => 'protein',
          'expected_program' => 'blastp',
          'expected_title' => 'Protein to Protein BLAST (blastp)',
        ],
      ],
    ];
  }

  /**
   * Tests that the build form contains the expected BLAST fields.
   *
   * @param array $scenario
   *  The scenario data containing query type, database type, and expected program.
   *
   * @dataProvider provideDataForTestBuildForm
   */
  #[DataProvider('provideDataForTestBuildForm')]
  public function testBuildForm(array $scenario): void {
    $form = [];
    $form_state = new FormState();

    $build = $this->blast_form->buildForm($form, $form_state, $scenario['query_type'], $scenario['db_type']);

    // Test if the correct title is created.
    $page_title = [
      '@query' => ucfirst($scenario['query_type']),
      '@program' => ucfirst($scenario['db_type']),
      '@name' => $scenario['expected_program'],
    ];
    $title = $this->container->get('string_translation')->translate('@query to @program BLAST (@name)', $page_title);
    $this->assertEquals($scenario['expected_title'],(string) $title);

    // Test if correct hidden elements are present.
    $this->assertSame($scenario['query_type'], $build['query_type']['#value'], 'We expect the query type to be ' . $scenario['query_type'] . ' but it is not.');
    $this->assertSame($scenario['db_type'], $build['db_type']['#value'], 'We expect the database type to be ' . $scenario['db_type'] . ' but it is not.');
    $this->assertSame($scenario['expected_program'], $build['blast_program']['#value'], 'We expect the BLAST program to be ' . $scenario['expected_program'] . ' but it is not.');

    // Test if the expected form elements are present.
    $this->assertSame('details', $build['B']['#type'], 'We expect the main container to be a details element but it is not.');
    $this->assertSame('details', $build['B']['query']['#type'], 'We expect the query container to be a details element but it is not.');
    $this->assertSame('textarea', $build['B']['query']['FASTA']['#type'], 'We expect the FASTA input to be a textarea but it is not.');
    $this->assertSame('select', $build['B']['db']['SELECT_DB']['#type'], 'We expect the database selection to be a select element but it is not.');
    $this->assertSame('submit', $build['B']['submit']['#type'], 'We expect the submit button to be a submit element but it is not.');
  }

  /**
   * Provides data for testValidateForm.
   *
   * @return array
   *   An array of test scenarios, each containing form values and expected results.
   */
  public static function provideDataForTestValidateForm(): array {
    return [
      'missing query and database' => [
        [
          'blast_program' => 'blastn',
          'query_type' => 'nucleotide',
          'db_type' => 'nucleotide',
          'maxTarget' => '50',
          'eVal' => '1e-5',
          'wordSize' => '11',
          'M&MScores' => 0,
          'gapCost' => '5,2',
        ],
        [
          'expected_error_keys' => ['query', 'db'],
          'expected_values' => [],
        ],
      ],
      'valid blastn' => [
        [
          'blast_program' => 'blastn',
          'query_type' => 'nucleotide',
          'db_type' => 'nucleotide',
          'FASTA' => ">seq\nACGT",
          'SELECT_DB' => '1',
          'maxTarget' => '500',
          'eVal' => '1e-5',
          'wordSize' => '11',
          'M&MScores' => 0,
          'gapCost' => '5,2',
        ],
        [
          'expected_error_keys' => [],
          'expected_values' => [
            'qFlag' => 'seqQuery',
            'dbFlag' => 'blastdb',
          ],
        ],
      ],
      'valid blastx' => [
        [
          'blast_program' => 'blastx',
          'query_type' => 'nucleotide',
          'db_type' => 'protein',
          'FASTA' => ">seq\nACGT",
          'SELECT_DB' => '1',
        ],
        [
          'expected_error_keys' => [],
          'expected_values' => [
            'qFlag' => 'seqQuery',
            'dbFlag' => 'blastdb',
          ],
        ],
      ],
      'valid tblastn' => [
        [
          'blast_program' => 'tblastn',
          'query_type' => 'protein',
          'db_type' => 'nucleotide',
          'FASTA' => ">seq\nACGT",
          'SELECT_DB' => '1',
        ],
        [
          'expected_error_keys' => [],
          'expected_values' => [
            'qFlag' => 'seqQuery',
            'dbFlag' => 'blastdb',
          ],
        ],
      ],
      'valid blastp' => [
        [
          'blast_program' => 'blastp',
          'query_type' => 'protein',
          'db_type' => 'protein',
          'FASTA' => ">seq\nACGT",
          'SELECT_DB' => '1',
        ],
        [
          'expected_error_keys' => [],
          'expected_values' => [
            'qFlag' => 'seqQuery',
            'dbFlag' => 'blastdb',
          ],
        ],
      ],
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
          'M&MScores' => 0,
          'gapCost' => '5,2',
        ],
        [
          'expected_error_keys' => ['query'],
          'expected_values' => [],
        ],
      ],
      'valid uploaded query and database' => [
        [
          'blast_program' => 'blastn',
          'query_type' => 'nucleotide',
          'db_type' => 'nucleotide',
          'UPLOAD' => '1',
          'DBUPLOAD' => '2',
          'maxTarget' => '500',
          'eVal' => '1e-5',
          'wordSize' => '11',
          'M&MScores' => 0,
          'gapCost' => '5,2',
        ],
        [
          'expected_error_keys' => [],
          'expected_values' => [
            'qFlag' => 'upQuery',
            'dbFlag' => 'upDB',
          ],
        ],
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
          'M&MScores' => 0,
          'gapCost' => '5,2',
        ],
        [
          'expected_error_keys' => ['db'],
          'expected_values' => [],
        ],
      ],
      'invalid DBUPLOAD and missing SELECT_DB' => [
        [
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
          'M&MScores' => 0,
          'gapCost' => '5,2',
        ],
        [
          'expected_error_keys' => [],
          'expected_values' => [
            'dbFlag' => 'blastdb',
          ],
        ],
      ],
      'invalid eVal' => [
        [
          'blast_program' => 'blastn',
          'query_type' => 'nucleotide',
          'db_type' => 'nucleotide',
          'FASTA' => ">seq\nACGT",
          'SELECT_DB' => '1',
          'maxTarget' => '500',
          'eVal' => 'not-a-number',
          'wordSize' => '11',
          'M&MScores' => 0,
          'gapCost' => '5,2',
        ],
        [
          'expected_error_keys' => ['eVal'],
          'expected_values' => [],
        ],
      ],
    ];
  }

  /**
   * Tests that the form validation correctly identifies missing query and database.
   *
   * @param array $values
   *  The form values to validate.
   * @param array $expected
   *  The expected results, including expected error keys.
   *
   * @dataProvider provideDataForTestValidateForm
   */
  #[DataProvider('provideDataForTestValidateForm')]
  public function testValidateForm(array $values, array $expected): void {
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
    $form = [];
    $form_state = new FormState();
    $form_state->setValues($values);

    $this->blast_form->validateForm($form, $form_state);

    $errors = $form_state->getErrors();
    foreach ($expected['expected_error_keys'] as $key) {
      $this->assertArrayHasKey($key, $errors, 'Expected an error for missing ' . $key . ' but it was not found.');
    }
    foreach ($expected['expected_values'] as $key => $expected_value) {
      $this->assertSame($expected_value, $form_state->getValue($key), 'Expected the value for ' . $key . ' to be ' . $expected_value . ' but it is not.');
    }
  }

  /**
   * Provides data for testSubmitForm.
   *
   * @return array
   *   An array of test scenarios, each containing form values to submit.
   */
  public static function provideDataForTestSubmitForm(): array {
    return [
      'valid blastn' => [
        [
          'blast_program' => 'blastn',
          'query_type' => 'nucleotide',
          'db_type' => 'nucleotide',
          'FASTA' => ">seq\nACGT",
          'SELECT_DB' => '123450',
          'maxTarget' => '500',
          'eVal' => '1e-5',
          'wordSize' => '11',
          'M&MScores' => 0,
          'gapCost' => '5,2',
        ],
      ],
      'valid blastx' => [
        [
          'blast_program' => 'blastx',
          'query_type' => 'nucleotide',
          'db_type' => 'protein',
          'FASTA' => ">seq\nACGT",
          'SELECT_DB' => '67890',
        ],
      ],
      'valid tblastn' => [
        [
          'blast_program' => 'tblastn',
          'query_type' => 'protein',
          'db_type' => 'nucleotide',
          'FASTA' => ">seq\nACGT",
          'SELECT_DB' => '123450',
        ],
      ],
      'valid blastp' => [
        [
          'blast_program' => 'blastp',
          'query_type' => 'protein',
          'db_type' => 'protein',
          'FASTA' => ">seq\nACGT",
          'SELECT_DB' => '67890',
        ],
      ],
      'valid blastn with uploaded query' => [
        [
          'blast_program' => 'blastn',
          'query_type' => 'nucleotide',
          'db_type' => 'nucleotide',
          'UPLOAD' => '1',
          'SELECT_DB' => '123450',
          'maxTarget' => '500',
          'eVal' => '1e-5',
          'wordSize' => '11',
          'M&MScores' => 1,
          'gapCost' => '5,2',
        ],
      ],
    ];
  }

  /**
   * Tests that form submission creates a BLAST job record for each program.
   *
   * @param array $values
   *  The form values to submit.
   *
   * @dataProvider provideDataForTestSubmitForm
   */
  #[DataProvider('provideDataForTestSubmitForm')]
  public function testSubmitForm(array $values): void {
    $editable_config = \Drupal::service('config.factory')->getEditable('tripal_blast.settings');
    $editable_config->set('tripal_blast_config_general.path', 'tmp/true');
    $editable_config->save();

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
    $form = [];
    $form_state = new FormState();
    $form_state->setValues($values);

    $this->blast_form->validateForm($form, $form_state);

    $before = (int) $this->chado_connection->select('blastjob')
      ->condition('blast_program', $values['blast_program'])
      ->countQuery()
      ->execute()
      ->fetchField();

    $this->blast_form->submitForm($form, $form_state);

    $after = (int) $this->chado_connection->select('blastjob')
      ->condition('blast_program', $values['blast_program'])
      ->countQuery()
      ->execute()
      ->fetchField();

    $this->assertSame($before + 1, $after, 'Submission did not create a job record for ' . $values['blast_program']);
    $this->assertNotNull($form_state->getRedirect(), 'Expected a redirect after form submission but none was found.');
    $this->assertNotEmpty($_SESSION['blast_jobs'], "Expected a session variable for blast_jobs to be set after form submission but it was not found.");
  }

  /**
   * Tests the buildForm related warning messages.
   */
  public function testBuildFormDisplaysWarningMessage(): void {
    $editable_config = $this->config('tripal_blast.settings');
    $editable_config
      ->set(
        'tripal_blast_config_notification.warning_text',
        'Temporary maintenance warning'
      )
      ->save();

    $build = $this->blast_form->buildForm([], new FormState(), 'nucleotide', 'nucleotide');

    $this->assertArrayHasKey('config_warning', $build, 'Expected a warning message in the form build but it was not found.');
  }

  /**
   * Tests buildForm recent job display.
   */
  public function testBuildFormDisplaysRecentJobs(): void {
    $job_service = $this->getMockBuilder(\Drupal\tripal_blast\Services\TripalBlastJobService::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['jobsCountRecentJobs', 'jobsCreateTable'])
      ->getMock();

    $job_service->method('jobsCountRecentJobs')->willReturn(1);
    $job_service->method('jobsCreateTable')->willReturn(['#type' => 'table']);

    $this->container->set('tripal_blast.job_service', $job_service);

    $build = $this->blast_form->buildForm([], new FormState(), 'nucleotide', 'nucleotide');

    $this->assertArrayHasKey('A', $build, 'Expected a recent job container in the form build but it was not found.');
    $this->assertSame('table', $build['A']['recent_job']['#type'], 'Expected the recent job container to be a table but it is not.');
  }

  /**
   * Tests if the buildForm resubmits correct defaults.
   */
  public function testBuildFormResubmitPopulatesDefaultValues(): void {
    $query_file = $this->container->get('file_system')->getTempDirectory() . '/resubmit_query.fasta';
    file_put_contents($query_file, ">query\nACGT");

    $job_service = $this->getMockBuilder(\Drupal\tripal_blast\Services\TripalBlastJobService::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['jobsBlastRevealSecret', 'jobsGetJobByJobId'])
      ->getMock();

    $job_service->method('jobsBlastRevealSecret')->willReturn(42);
    $job_service->method('jobsGetJobByJobId')->willReturn((object) [
      'blastdb' => (object) ['nid' => 99],
      'files' => (object) ['query' => $query_file],
    ]);
    $this->container->set('tripal_blast.job_service', $job_service);

    \Drupal::request()->query->set('resubmit', 'secret-id');

    $build = $this->blast_form->buildForm([], new FormState(), 'nucleotide', 'nucleotide');

    $this->assertSame(99, $build['B']['db']['SELECT_DB']['#default_value'], 'Expected the default database value to be 99 but it is not.');
    $this->assertSame(">query\nACGT", $build['B']['query']['FASTA']['#default_value'], 'Expected the default query value to be the correct sequence but it is not.');
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
      'M&MScores' => 0,
      'gapCost' => '5,2',
    ]);

    $this->blast_form->submitForm($form, $form_state);

    $messages = \Drupal::messenger()->all();
    $this->assertNotEmpty($messages['error'], 'Expected an error message but none were found.');
    $this->assertStringContainsString("No BLAST database selected.", reset($messages['error']), 'Expected error message about missing database selection but it was not found.');
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
      'M&MScores' => 0,
      'gapCost' => '5,2',
    ]);
    $this->blast_form->submitForm($form, $form_state);
    $this->assertNotNull($form_state->getRedirect(), "Expeccted a redirect object, but it was NULL.");
    $redirect_path = $form_state->getRedirect()->getInternalPath();
    $this->assertSame(
      'blast/report/encoded-job',
      $redirect_path,
      "We expected the redirect route to be 'blast/report/encoded-job' but it was $redirect_path."
    );
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
      ->willThrowException(new \RuntimeException('Unable to create tripal job'));
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
      'M&MScores' => 0,
      'gapCost' => '5,2',
    ]);

    $this->blast_form->submitForm($form, $form_state);

    $messages = \Drupal::messenger()->all();
    $this->assertNotEmpty($messages['error'], 'Expected an error message but none were found.');
    $this->assertStringContainsString("Unable to submit the BLAST job. The error was: Unable to create tripal job", reset($messages['error']), 'Expected error message about job creation but it was not found.');
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
      'M&MScores' => 0,
    ]);

    $updated_form = $this->blast_form->ajaxShowExampleSequenceCallback($form, $form_state);
    $this->assertSame('>example\nACGT', $updated_form['#value'], 'Expected the FASTA field to be updated with the example sequence but it was not.');
    $this->assertStringContainsString('tripal-blast-tip', $updated_form['#suffix'], 'Expected the FASTA field to have a tip suffix but it does not.');

    $response = $this->blast_form->ajaxFieldUpdateCallback($form, $form_state);
    $this->assertInstanceOf(AjaxResponse::class, $response);
    $this->assertNotEmpty($response->getCommands(), 'Expected the AJAX response to contain commands but it does not.');
  }

  /**
   * Provides data for testing the gap cost retrieval in the AJAX callback.
   *
   * @return array
   */
  public static function provideDataForTestProgramGetGapCostInAjaxCallback(): array {
    return [
      'blastn with m&m score 0' => [
        [
          'blast_program' => 'blastn',
          'query_type' => 'nucleotide',
          'db_type' => 'nucleotide',
          'M&MScores' => 0,
        ],
        [
          'gap_keys' => ['5_2', '2_2', '1_2', '0_2', '3_1', '2_1', '1_1'],
        ],
      ],
      'blastn with m&m score 1' => [
        [
          'blast_program' => 'blastn',
          'query_type' => 'nucleotide',
          'db_type' => 'nucleotide',
          'M&MScores' => 1,
        ],
        [
          'gap_keys' => ['5_2', '2_2', '1_2', '1_2', '0_2', '2_1', '1_1'],
        ],
      ],
      'blastn with m&m score 2' => [
        [
          'blast_program' => 'blastn',
          'query_type' => 'nucleotide',
          'db_type' => 'nucleotide',
          'M&MScores' => 2,
        ],
        [
          'gap_keys' => ['5_2', '1_2', '0_2', '2_1', '1_1'],
        ],
      ],
      'blastn with m&m score 3' => [
        [
          'blast_program' => 'blastn',
          'query_type' => 'nucleotide',
          'db_type' => 'nucleotide',
          'M&MScores' => 3,
        ],
        [
          'gap_keys' => ['4_4', '2_4', '0_4', '3_3', '6_2', '5_2', '4_2', '2_2'],
        ],
      ],
      'blastn with m&m score 4' => [
        [
          'blast_program' => 'blastn',
          'query_type' => 'nucleotide',
          'db_type' => 'nucleotide',
          'M&MScores' => 4,
        ],
        [
          'gap_keys' => ['12_8', '6_5', '5_5', '4_5', '3_5'],
        ],
      ],
      'blastn with m&m score 5' => [
        [
          'blast_program' => 'blastn',
          'query_type' => 'nucleotide',
          'db_type' => 'nucleotide',
          'M&MScores' => 5,
        ],
        [
          'gap_keys' => ['5_2', '3_2', '2_2', '1_2', '0_2', '4_1', '3_1', '2_1']
        ],
      ],
    ];
  }

  /**
   * Tests that the AJAX callback correctly retrieves the gap cost based on the program and M&M score.
   *
   * @param array $data
   *   The form values to test the AJAX callback with.
   *
   * @dataProvider provideDataForTestProgramGetGapCostInAjaxCallback
   */
  #[DataProvider('provideDataForTestProgramGetGapCostInAjaxCallback')]
  public function testProgramGetGapCostInAjaxCallback(array $data, array $result): void {
    $form = [];
    $form_state = new FormState();
    $form_state->setValues($data);

    $response = $this->blast_form->ajaxFieldUpdateCallback($form, $form_state);

    $this->assertInstanceOf(AjaxResponse::class, $response);
    $this->assertNotEmpty($response->getCommands(), 'Expected the AJAX response to contain commands but it does not.');

    $gaps = $response->getCommands()[0]['args'][0];

    foreach ($result['gap_keys'] as $expected_gap) {
      $this->assertArrayHasKey($expected_gap, $gaps, 'Expected gap cost ' . $expected_gap . ' to be present in the AJAX response but it is not.');
    }
  }

  /**
   * Provides data for testBlastProgramHelperProgramSetMatchMiss.
   *
   * @return array
   */
  public static function provideDataForTestBlastProgramHelperProgramSetMatchMiss(): array {
    return [
      'blastn with m&m score 0' => [
        [
          'blast_program' => 'blastn',
          'query_type' => 'nucleotide',
          'db_type' => 'nucleotide',
          'FASTA' => ">seq\nACGT",
          'SELECT_DB' => '123450',
          'maxTarget' => '500',
          'eVal' => '1e-5',
          'wordSize' => '11',
          'M&MScores' => 0,
        ],
        [
          'match_miss' => [1, -2],
        ],
      ],
      'blastn with m&m score 1' => [
        [
          'blast_program' => 'blastn',
          'query_type' => 'nucleotide',
          'db_type' => 'nucleotide',
          'FASTA' => ">seq\nACGT",
          'SELECT_DB' => '123450',
          'maxTarget' => '500',
          'eVal' => '1e-5',
          'wordSize' => '11',
          'M&MScores' => 1,
        ],
        [
          'match_miss' => [1, -3],
        ],
      ],
      'blastn with m&m score 2' => [
        [
          'blast_program' => 'blastn',
          'query_type' => 'nucleotide',
          'db_type' => 'nucleotide',
          'FASTA' => ">seq\nACGT",
          'SELECT_DB' => '123450',
          'maxTarget' => '500',
          'eVal' => '1e-5',
          'wordSize' => '11',
          'M&MScores' => 2,
        ],
        [
          'match_miss' => [1, -4],
        ],
      ],
      'blastn with m&m score 3' => [
        [
          'blast_program' => 'blastn',
          'query_type' => 'nucleotide',
          'db_type' => 'nucleotide',
          'FASTA' => ">seq\nACGT",
          'SELECT_DB' => '123450',
          'maxTarget' => '500',
          'eVal' => '1e-5',
          'wordSize' => '11',
          'M&MScores' => 3,
        ],
        [
          'match_miss' => [2, -3],
        ],
      ],
      'blastn with m&m score 4' => [
        [
          'blast_program' => 'blastn',
          'query_type' => 'nucleotide',
          'db_type' => 'nucleotide',
          'FASTA' => ">seq\nACGT",
          'SELECT_DB' => '123450',
          'maxTarget' => '500',
          'eVal' => '1e-5',
          'wordSize' => '11',
          'M&MScores' => 4,
        ],
        [
          'match_miss' => [4, -5],
        ],
      ],
      'blastn with m&m score 5' => [
        [
          'blast_program' => 'blastn',
          'query_type' => 'nucleotide',
          'db_type' => 'nucleotide',
          'FASTA' => ">seq\nACGT",
          'SELECT_DB' => '123450',
          'maxTarget' => '500',
          'eVal' => '1e-5',
          'wordSize' => '11',
          'M&MScores' => 5,
        ],
        [
          'match_miss' => [1, -1],
        ],
      ],
    ];
  }

  /**
   * Tests that the BLAST program helper correctly sets match and mismatch scores based on the M&M score.
   *
   * @param array $data
   *   The form values to test the BLAST program helper with.
   * @param array $result
   *   The expected match and mismatch scores.
   *
   * @dataProvider provideDataForTestBlastProgramHelperProgramSetMatchMiss
   */
  #[DataProvider('provideDataForTestBlastProgramHelperProgramSetMatchMiss')]
  public function testBlastProgramHelperProgramSetMatchMiss(array $data, array $result): void {
    $captured_submission = NULL;

    $job_service = $this->getMockBuilder(\Drupal\tripal_blast\Services\TripalBlastJobService::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['createBlastJob', 'jobsBlastMakeSecret'])
      ->getMock();

    $job_service->expects($this->once())
      ->method('createBlastJob')
      ->willReturnCallback(function (array $submission) use (&$captured_submission): int {
        $captured_submission = $submission;
        return 2024;
      });

    $job_service->method('jobsBlastMakeSecret')->willReturn('encoded-job');
    $this->container->set('tripal_blast.job_service', $job_service);

    $form = [];
    $form_state = new FormState();
    $form_state->setValues($data);

    $this->blast_form->submitForm($form, $form_state);

    $this->assertIsArray($captured_submission['options']);
    $this->assertSame($result['match_miss'][1], $captured_submission['options']['penalty'], "The penalty should be {$result['match_miss'][1]} but it was {$captured_submission['options']['penalty']}");
    $this->assertSame($result['match_miss'][0], $captured_submission['options']['reward'], "The penalty should be {$result['match_miss'][0]} but it was {$captured_submission['options']['reward']}");
  }

  /**
   * Provide data for testProgramValidateFastaSequence method.
   *
   * @return array
   */
  public static function provideDataForTestProgramValidateFastaSequence() {
    return [
      'not a definition line' => [
        [
          'blast_program' => 'blastn',
          'query_type' => 'nucleotide',
          'db_type' => 'nucleotide',
          'FASTA' => "> seq\nACGT",
          'SELECT_DB' => '1',
          'maxTarget' => '500',
          'eVal' => 'not-a-number',
          'wordSize' => '11',
          'M&MScores' => 0,
          'gapCost' => '5,2',
        ],
      ],
      'not a sequence line' => [
        [
          'blast_program' => 'blastn',
          'query_type' => 'nucleotide',
          'db_type' => 'nucleotide',
          'FASTA' => "seq",
          'SELECT_DB' => '1',
          'maxTarget' => '500',
          'eVal' => 'not-a-number',
          'wordSize' => '11',
          'M&MScores' => 0,
          'gapCost' => '5,2',
        ],
      ],
    ];
  }

  /**
   * Tests the programValidateFastaSequence method in the Helper.
   *
   * @param array $values
   *   Values to be set in the form state.
   *
   * @dataProvider provideDataForTestProgramValidateFastaSequence
   */
  #[DataProvider('provideDataForTestProgramValidateFastaSequence')]
  public function testProgramValidateFastaSequence(array $values): void {
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
    $form = [];
    $form_state = new FormState();
    $form_state->setValues($values);

    $this->blast_form->validateForm($form, $form_state);

    $errors = $form_state->getErrors();
    $this->assertNotEmpty($errors, 'Expected an error message but none were found.');
    $this->assertArrayHasKey('query', $errors, 'Expected an error for query but it was not found.');
    $this->assertStringContainsString("The file should be a plain-text FASTA
          (.fasta, .fna, .fa, .fas) file. In other words, it cannot have formatting as is the
          case with MS Word (.doc, .docx) or Rich Text Format (.rtf).", $errors['query'], 'Expected error message about job creation but it was not found.');
  }

}
