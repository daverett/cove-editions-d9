<?php

declare(strict_types=1);

namespace Drupal\cove_categories\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\og\OgAccessInterface;
use Drupal\og\OgMembershipInterface;

/**
 * Decides who may manage the categories of a course.
 *
 * A user manages a course's categories when they are a site-wide category
 * admin, or when they may administer that course as an OG group. The OG check
 * covers both the course owner (via og.settings:group_manager_full_access) and
 * members holding the OG "administrator" role, and excludes blocked members.
 */
final class CoveCategoryAccess {

  public function __construct(
    private readonly OgAccessInterface $ogAccess,
  ) {}

  /**
   * Access result for managing (create/update/delete) a course's categories.
   *
   * Returns neutral when the course is missing or not a course node, so the
   * caller can decide whether that means forbidden in its own context.
   */
  public function managerAccess(?NodeInterface $course, AccountInterface $account): AccessResultInterface {
    $admin = AccessResult::allowedIfHasPermission($account, 'administer cove_category');
    if ($admin->isAllowed()) {
      return $admin;
    }

    if (!$course instanceof NodeInterface || $course->bundle() !== 'course') {
      return $admin;
    }

    $result = $admin->orIf($this->ogAccess->userAccess($course, 'administer group', $account));

    // OgAccess only adds the user context, so add the group's membership-list
    // tag too: granting or revoking the OG administrator role then flushes this
    // decision right away.
    $tags = Cache::buildTags(OgMembershipInterface::GROUP_MEMBERSHIP_LIST_CACHE_TAG_PREFIX, $course->getCacheTagsToInvalidate());
    return $result->addCacheTags($tags);
  }

  /**
   * Whether the account may manage the given course's categories.
   */
  public function isCourseManager(?NodeInterface $course, AccountInterface $account): bool {
    return $this->managerAccess($course, $account)->isAllowed();
  }

}
