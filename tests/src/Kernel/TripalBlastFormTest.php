<?php

namespace Drupal\Tests\tripal_blast\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\Tests\tripal_chado\Kernel\ChadoTestKernelBase;
use Drupal\tripal_blast\Form\TripalBlastForm;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Drupal\tripal_chado\Database\ChadoConnection;

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

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user', 'file', 'tripal', 'tripal_chado','tripal_blast'];

  /**
   * Class instance of the Tripal Blast Form.
   *
   * @var \Drupal\tripal_blast\Form\TripalBlastForm
   */
  protected $blast_form;

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

    $this->installConfig(['tripal_blast', 'system']);
    $this->installSchema('tripal_blast', ['blastjob']);

    $this->blast_form = TripalBlastForm::create($this->container);
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

    $this->assertSame('nucleotide', $build['query_type']['#value']);
    $this->assertSame('nucleotide', $build['db_type']['#value']);
    $this->assertSame('blastn', $build['blast_program']['#value']);
    $this->assertSame('details', $build['B']['#type']);
    $this->assertSame('details', $build['B']['query']['#type']);
    $this->assertSame('textarea', $build['B']['query']['FASTA']['#type']);
    $this->assertSame('select', $build['B']['db']['SELECT_DB']['#type']);
    $this->assertSame('submit', $build['B']['submit']['#type']);
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
    $this->assertArrayHasKey('query', $errors);
    $this->assertArrayHasKey('db', $errors);
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

    $this->assertSame([], $form_state->getErrors());
    $this->assertSame('seqQuery', $form_state->getValue('qFlag'));
    $this->assertSame('blastdb', $form_state->getValue('dbFlag'));
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

}
