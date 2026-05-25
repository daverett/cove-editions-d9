<?php

declare(strict_types=1);

namespace Drupal\cove_categories\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Defines the interface for the Category entity.
 */
interface CoveCategoryInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Returns the color key, one of the COVE_CATEGORIES_PIN_PALETTE keys.
   */
  public function getColor(): ?string;

  /**
   * Returns the color as a #rrggbb hex string, or NULL when unset.
   */
  public function getColorHex(): ?string;

  /**
   * Returns the node id of the owning course.
   */
  public function getGroupId(): ?int;

}
