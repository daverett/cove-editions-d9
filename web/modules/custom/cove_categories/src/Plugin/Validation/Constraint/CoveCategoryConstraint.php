<?php

declare(strict_types=1);

namespace Drupal\cove_categories\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Enforces the per-course palette rules on a category.
 *
 * @Constraint(
 *   id = "CoveCategory",
 *   label = @Translation("Cove category palette rules", context = "Validation"),
 *   type = "entity:cove_category"
 * )
 */
final class CoveCategoryConstraint extends Constraint {

  /**
   * Violation message when the course reached the maximum category count.
   */
  public string $tooManyMessage = 'This course already has the maximum of @max categories.';

  /**
   * Violation message when the color is already used in the same course.
   */
  public string $colorTakenMessage = 'The color %color is already used by another category of this course.';

}
