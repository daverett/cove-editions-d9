<?php

declare(strict_types=1);

namespace Drupal\cove_categories\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\node\NodeInterface;

/**
 * Scopes referenceable categories to the host content's origin course(s).
 *
 * A timeline entry, place or gallery image may only reference categories that
 * belong to the origin course of its parent timeline / map / exhibit. This is
 * what enforces the per-course palette at the data level (the form widget
 * also narrows the visible options, see cove_categories_form_node_form_alter()).
 *
 * @EntityReferenceSelection(
 *   id = "cove_category_by_course",
 *   label = @Translation("Category: scoped to the host content's course(s)"),
 *   entity_types = {"cove_category"},
 *   group = "cove_category_by_course",
 *   weight = 0,
 * )
 */
final class CoveCategorySelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);

    $host = $this->configuration['entity'] ?? NULL;
    $course_ids = $host instanceof NodeInterface ? _cove_categories_origin_course_ids($host) : [];

    // With no course in scope nothing is referenceable; [0] matches no row.
    $query->condition('group', $course_ids ?: [0], 'IN');

    return $query;
  }

}
