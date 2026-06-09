<?php

declare(strict_types=1);

namespace Drupal\cove_categories;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\cove_categories\Access\CoveCategoryAccess;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Access control handler for the Category entity.
 *
 * Site admins go through the entity's admin_permission upstream of this
 * handler. For other users, view is open with the view permission. Create,
 * update and delete require being a manager of the category's course, i.e. its
 * OG group administrator or owner (see CoveCategoryAccess).
 */
final class CoveCategoryAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  public function __construct(
    EntityTypeInterface $entity_type,
    private readonly CoveCategoryAccess $access,
  ) {
    parent::__construct($entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type): self {
    return new self($entity_type, $container->get('cove_categories.access'));
  }

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

    /** @var \Drupal\cove_categories\Entity\CoveCategoryInterface $entity */
    $course = $entity->get('group')->entity;
    if (!$course instanceof NodeInterface) {
      return AccessResult::forbidden()->addCacheableDependency($entity);
    }

    return $this->access->managerAccess($course, $account)
      ->addCacheableDependency($entity);
  }

  /**
   * {@inheritdoc}
   *
   * Coarse on purpose: there is no course context here. The real per-course
   * gate is the cove_category_group selection handler, which through core's
   * ValidReference constraint blocks saving a category on a course the user
   * does not manage.
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResultInterface {
    return AccessResult::allowedIfHasPermission($account, 'view cove_category');
  }

}
