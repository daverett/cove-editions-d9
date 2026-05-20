<?php

declare(strict_types=1);

namespace Drupal\cove_categories\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\cove_categories\Entity\CoveCategoryInterface;

/**
 * Renders a category reference as a colored name chip.
 *
 * @FieldFormatter(
 *   id = "cove_category_chip",
 *   label = @Translation("Category color chip"),
 *   field_types = {"entity_reference"},
 * )
 */
final class CoveCategoryChipFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition): bool {
    return $field_definition->getSetting('target_type') === 'cove_category';
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];
    foreach ($items as $delta => $item) {
      $category = $item->entity;
      if (!$category instanceof CoveCategoryInterface) {
        continue;
      }
      $key = $category->getColor();
      // The color drives a CSS class rather than an inline style: Views field
      // rewrites strip style attributes, classes survive.
      if ($key === NULL || !isset(\COVE_CATEGORIES_PIN_PALETTE[$key])) {
        continue;
      }
      $elements[$delta] = [
        '#type' => 'inline_template',
        '#template' => '<span class="cove-category-chip cove-category-chip--{{ key }}">{{ name }}</span>',
        '#context' => [
          'key' => $key,
          'name' => $category->label(),
        ],
        '#attached' => ['library' => ['cove_categories/category-chip']],
      ];
    }
    return $elements;
  }

}
