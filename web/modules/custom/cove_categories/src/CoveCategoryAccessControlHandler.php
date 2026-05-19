<?php

declare(strict_types=1);

namespace Drupal\cove_categories;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler for the Category entity.
 *
 * @todo Restrict "manage own course" to categories whose course the user
 * actually leads (OG membership check). For now it relies on the permission.
 */
final class CoveCategoryAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    if ($account->hasPermission('administer cove_category')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    return match ($operation) {
      'view' => AccessResult::allowedIfHasPermission($account, 'view cove_category'),
      'update', 'delete' => AccessResult::allowedIfHasPermission($account, 'manage own course cove_category'),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResultInterface {
    if ($account->hasPermission('administer cove_category')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    return AccessResult::allowedIfHasPermission($account, 'manage own course cove_category');
  }

}
