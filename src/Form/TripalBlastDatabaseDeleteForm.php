<?php
/**
 * @file 
 * This is the the form to handle BLAST database deletion. 
 */

namespace Drupal\tripal_blast\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

/**
 * Construct for to delete BLAST database.
 */
class TripalBlastDatabaseDeleteForm extends EntityConfirmFormBase {
  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', ['%name' => $this->entity->getName()]);
  }  

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.tripal_blast.blast_database');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();
    $this->messenger()->addMessage($this->t('BLAST database %name has been deleted.', ['%name' => $this->entity->getName()]));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }
}