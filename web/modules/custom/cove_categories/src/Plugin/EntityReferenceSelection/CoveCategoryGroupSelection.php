<?php

declare(strict_types=1);

namespace Drupal\cove_categories\Plugin\EntityReferenceSelection;

use Drupal\cove_categories\Access\CoveCategoryAccess;
use Drupal\node\NodeInterface;
use Drupal\node\Plugin\EntityReferenceSelection\NodeSelection;
use Drupal\og\MembershipManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Restricts the course choice to courses the current user manages.
 *
 * Used by the cove_category entity's `group` field, so that a category can only
 * be created on a course the user administers (OG group administrator or owner).
 * Site admins (administer cove_category) keep the full course list. As the field
 * selection handler this also backs core's ValidReference constraint, so it is
 * the save-time gate, not just an autocomplete filter.
 *
 * @EntityReferenceSelection(
 *   id = "cove_category_group",
 *   label = @Translation("Cove category group: scope to courses the user manages"),
 *   group = "cove_category_group",
 *   entity_types = {"node"},
 *   weight = 0,
 * )
 */
final class CoveCategoryGroupSelection extends NodeSelection {

  private MembershipManagerInterface $membershipManager;

  private CoveCategoryAccess $access;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->membershipManager = $container->get('og.membership_manager');
    $instance->access = $container->get('cove_categories.access');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);
    // Admins keep the unrestricted course list.
    if ($this->currentUser->hasPermission('administer cove_category')) {
      return $query;
    }
    $course_ids = $this->managedCourseIds();
    // No managed courses for the user -> nothing referenceable. [0] matches no row.
    $query->condition('nid', $course_ids ?: [0], 'IN');
    return $query;
  }

  /**
   * Returns the node ids of the courses the current user manages.
   *
   * Candidates are the user's owned courses (an owner may have no membership
   * row, so getUserGroups misses them) plus their OG course groups, each run
   * through the same manager check the access handler uses.
   */
  private function managedCourseIds(): array {
    $account = $this->currentUser;
    $node_storage = $this->entityTypeManager->getStorage('node');

    $candidate_ids = $node_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'course')
      ->condition('uid', $account->id())
      ->execute();
    $candidate_ids = array_map('intval', $candidate_ids);

    foreach ($this->membershipManager->getUserGroups($account->id())['node'] ?? [] as $gid => $group) {
      if ($group instanceof NodeInterface && $group->bundle() === 'course') {
        $candidate_ids[] = (int) $gid;
      }
    }

    $candidate_ids = array_unique($candidate_ids);
    if (!$candidate_ids) {
      return [];
    }

    $managed = [];
    foreach ($node_storage->loadMultiple($candidate_ids) as $course) {
      if ($this->access->isCourseManager($course, $account)) {
        $managed[] = (int) $course->id();
      }
    }
    return $managed;
  }

}
