<?php

declare(strict_types=1);

namespace Drupal\cove_categories\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the Category content entity.
 *
 * A category belongs to one course and carries one color from the fixed
 * 20-color palette. Course content (timeline entries, places, gallery images)
 * references a category from its parent's origin course.
 *
 * @ContentEntityType(
 *   id = "cove_category",
 *   label = @Translation("Category"),
 *   label_collection = @Translation("Categories"),
 *   label_singular = @Translation("category"),
 *   label_plural = @Translation("categories"),
 *   label_count = @PluralTranslation(
 *     singular = "@count category",
 *     plural = "@count categories",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\cove_categories\CoveCategoryListBuilder",
 *     "access" = "Drupal\cove_categories\CoveCategoryAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\cove_categories\Form\CoveCategoryForm",
 *       "edit" = "Drupal\cove_categories\Form\CoveCategoryForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "cove_category",
 *   admin_permission = "administer cove_category",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "label",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/categories",
 *     "add-form" = "/admin/content/categories/add",
 *     "canonical" = "/cove-category/{cove_category}",
 *     "edit-form" = "/cove-category/{cove_category}/edit",
 *     "delete-form" = "/cove-category/{cove_category}/delete",
 *   },
 *   constraints = {
 *     "CoveCategory" = {},
 *   },
 * )
 */
class CoveCategory extends ContentEntityBase implements CoveCategoryInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function getColor(): ?string {
    $value = $this->get('color')->value;
    return $value !== NULL ? (string) $value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getColorHex(): ?string {
    $key = $this->getColor();
    if ($key === NULL || !isset(\COVE_CATEGORIES_PIN_PALETTE[$key])) {
      return NULL;
    }
    [$r, $g, $b] = \COVE_CATEGORIES_PIN_PALETTE[$key];
    return sprintf('#%02x%02x%02x', $r, $g, $b);
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupId(): ?int {
    $value = $this->get('group')->target_id;
    return $value !== NULL ? (int) $value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['color'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Color'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values_function', 'cove_categories_category_color_allowed_values')
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['group'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Course'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'node')
      ->setSetting('handler', 'cove_category_group')
      ->setSetting('handler_settings', [
        'target_bundles' => ['course' => 'course'],
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    return $fields;
  }

}
