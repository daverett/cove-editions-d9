<?php

declare(strict_types=1);

namespace Drupal\cove_categories\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\cove_categories\Entity\CoveCategoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the CoveCategory constraint.
 */
final class CoveCategoryConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Maximum number of categories a single course may hold.
   */
  private const MAX_PER_COURSE = 20;

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self($container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   *
   * The $entity parameter is typed mixed to satisfy the parent contract; it is
   * narrowed to CoveCategoryInterface immediately below.
   */
  public function validate(mixed $entity, Constraint $constraint): void {
    if (!$entity instanceof CoveCategoryInterface || !$constraint instanceof CoveCategoryConstraint) {
      return;
    }
    $group_id = $entity->getGroupId();
    if ($group_id === NULL) {
      return;
    }

    $storage = $this->entityTypeManager->getStorage('cove_category');

    // Palette size: at most MAX_PER_COURSE categories per course.
    $count_query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('group', $group_id);
    if (!$entity->isNew()) {
      $count_query->condition('id', $entity->id(), '<>');
    }
    if ((int) $count_query->count()->execute() >= self::MAX_PER_COURSE) {
      $this->context->buildViolation($constraint->tooManyMessage)
        ->setParameter('@max', (string) self::MAX_PER_COURSE)
        ->atPath('group')
        ->addViolation();
    }

    // One color = one category within a course.
    $color = $entity->getColor();
    if ($color !== NULL) {
      $color_query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('group', $group_id)
        ->condition('color', $color);
      if (!$entity->isNew()) {
        $color_query->condition('id', $entity->id(), '<>');
      }
      if ((int) $color_query->count()->execute() > 0) {
        $this->context->buildViolation($constraint->colorTakenMessage)
          ->setParameter('%color', ucfirst($color))
          ->atPath('color')
          ->addViolation();
      }
    }
  }

}
