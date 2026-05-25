<?php

declare(strict_types=1);

namespace Drupal\cove_categories\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\og\Og;

/**
 * Per-course category management page.
 */
final class CoveCategoryCourseController extends ControllerBase {

  /**
   * Access for the categories tab: course bundle + admin or OG-member leader.
   */
  public function access(NodeInterface $node, AccountInterface $account): AccessResultInterface {
    if ($node->bundle() !== 'course') {
      return AccessResult::forbidden()->addCacheableDependency($node);
    }
    if ($account->hasPermission('administer cove_category')) {
      return AccessResult::allowed()
        ->cachePerPermissions()
        ->addCacheableDependency($node);
    }
    $allowed = $account->hasPermission('manage own course cove_category')
      && Og::isMember($node, $account);
    return AccessResult::allowedIf($allowed)
      ->cachePerPermissions()
      ->cachePerUser()
      ->addCacheableDependency($node);
  }

  /**
   * Lists the categories of a course.
   */
  public function listCategories(NodeInterface $node): array {
    $destination = Url::fromRoute('cove_categories.course_categories', ['node' => $node->id()])->toString();
    $storage = $this->entityTypeManager()->getStorage('cove_category');
    $ids = $storage->getQuery()
      ->condition('group', $node->id())
      ->accessCheck(TRUE)
      ->sort('label')
      ->execute();

    $rows = [];
    foreach ($storage->loadMultiple($ids) as $category) {
      /** @var \Drupal\cove_categories\Entity\CoveCategoryInterface $category */
      $rows[] = [
        'label' => $category->label(),
        'color' => $this->colorCell($category->getColor()),
        'operations' => [
          'data' => [
            '#type' => 'operations',
            '#links' => [
              'edit' => [
                'title' => $this->t('Edit'),
                'url' => $category->toUrl('edit-form', ['query' => ['destination' => $destination]]),
              ],
              'delete' => [
                'title' => $this->t('Delete'),
                'url' => $category->toUrl('delete-form', ['query' => ['destination' => $destination]]),
              ],
            ],
          ],
        ],
      ];
    }

    return [
      '#cache' => [
        'tags' => ['cove_category_list'],
      ],
      'add' => [
        '#type' => 'link',
        '#title' => $this->t('Add category'),
        '#url' => Url::fromRoute('entity.cove_category.add_form', [], [
          'query' => ['group' => $node->id(), 'destination' => $destination],
        ]),
        '#attributes' => ['class' => ['button', 'button--action', 'button--primary']],
      ],
      'table' => [
        '#type' => 'table',
        '#header' => [$this->t('Name'), $this->t('Color'), $this->t('Operations')],
        '#rows' => $rows,
        '#empty' => $this->t('No category for this course yet.'),
      ],
    ];
  }

  /**
   * Builds a table cell with a color swatch for a palette color key.
   */
  private function colorCell(?string $color): array {
    if ($color === NULL || !isset(COVE_CATEGORIES_PIN_PALETTE[$color])) {
      return ['data' => ''];
    }
    [$r, $g, $b] = COVE_CATEGORIES_PIN_PALETTE[$color];
    return [
      'data' => [
        '#type' => 'inline_template',
        '#template' => '<span style="display:inline-block;width:14px;height:14px;border-radius:3px;background:rgb({{ r }},{{ g }},{{ b }});vertical-align:middle;margin-right:6px;"></span>{{ name }}',
        '#context' => ['r' => $r, 'g' => $g, 'b' => $b, 'name' => ucfirst($color)],
      ],
    ];
  }

}
