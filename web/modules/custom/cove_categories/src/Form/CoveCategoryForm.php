<?php

declare(strict_types=1);

namespace Drupal\cove_categories\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Add and edit form for the Category entity.
 */
final class CoveCategoryForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $this->messenger()->addStatus($this->t('The category %label has been saved.', [
      '%label' => $this->entity->label(),
    ]));
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));

    return $result;
  }

}
