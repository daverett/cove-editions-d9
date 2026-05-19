<?php

declare(strict_types=1);

namespace Drupal\cove_categories;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for the Category entity.
 */
final class CoveCategoryListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    return [
      'label' => $this->t('Name'),
      'color' => $this->t('Color'),
      'group' => $this->t('Course'),
    ] + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\cove_categories\Entity\CoveCategoryInterface $entity */
    $group = $entity->get('group')->entity;
    return [
      'label' => $entity->label(),
      'color' => $entity->getColor(),
      'group' => $group ? $group->toLink() : '',
    ] + parent::buildRow($entity);
  }

}
