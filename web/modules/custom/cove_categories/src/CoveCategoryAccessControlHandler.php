<?php

declare(strict_types=1);

namespace Drupal\cove_categories;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\og\Og;

/**
 * Access control handler for the Category entity.
 *
 * Site admins go through the entity's admin_permission upstream of this
 * handler. For other users, view is open with the view permission. Update and
 * delete additionally require OG membership of the category's course, so that
 * "manage own course cove_category" really means own courses only.
 */
final class CoveCategoryAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    // Admin bypass. Overriding checkAccess() skips the parent's default
    // admin_permission shortcut, so we restore it explicitly.
    $admin_permission = $this->entityType->getAdminPermission();
    if ($admin_permission && $account->hasPermission($admin_permission)) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    if ($operation === 'view') {
      return AccessResult::allowedIfHasPermission($account, 'view cove_category');
    }
    if ($operation !== 'update' && $operation !== 'delete') {
      return AccessResult::neutral();
    }

    if (!$account->hasPermission('manage own course cove_category')) {
      return AccessResult::neutral()->cachePerPermissions();
    }

    /** @var \Drupal\cove_categories\Entity\CoveCategoryInterface $entity */
    $course = $entity->get('group')->entity;
    if (!$course instanceof NodeInterface) {
      return AccessResult::forbidden()->addCacheableDependency($entity);
    }

    return AccessResult::allowedIf(Og::isMember($course, $account))
      ->cachePerPermissions()
      ->cachePerUser()
      ->addCacheableDependency($entity)
      ->addCacheableDependency($course);
  }

  /**
   * {@inheritdoc}
   *
   * The actual per-course scoping happens on the resulting entity (checkAccess)
   * and through the per-course tab UI, which pre-fills and locks the course at
   * creation time. The site-wide add form is reserved for admins in practice.
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResultInterface {
    return AccessResult::allowedIfHasPermission($account, 'manage own course cove_category');
  }

}
