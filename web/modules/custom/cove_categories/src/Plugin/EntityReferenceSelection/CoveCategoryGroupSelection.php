<?php

declare(strict_types=1);

namespace Drupal\cove_categories\Plugin\EntityReferenceSelection;

use Drupal\node\Plugin\EntityReferenceSelection\NodeSelection;
use Drupal\og\MembershipManagerInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Restricts the course choice to the current user's OG-member courses.
 *
 * Used by the cove_category entity's `group` field, so that group leaders can
 * only create categories on courses they actually lead. Site admins
 * (administer cove_category) keep the full course list.
 *
 * @EntityReferenceSelection(
 *   id = "cove_category_group",
 *   label = @Translation("Cove category group: scope to user's OG courses"),
 *   group = "cove_category_group",
 *   entity_types = {"node"},
 *   weight = 0,
 * )
 */
final class CoveCategoryGroupSelection extends NodeSelection {

  private MembershipManagerInterface $membershipManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->membershipManager = $container->get('og.membership_manager');
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
    $course_ids = $this->userCourseIds();
    // No OG courses for the user -> nothing referenceable. [0] matches no row.
    $query->condition('nid', $course_ids ?: [0], 'IN');
    return $query;
  }

  /**
   * Returns the node ids of the courses the current user is OG-member of.
   */
  private function userCourseIds(): array {
    $user = User::load($this->currentUser->id());
    if ($user === NULL) {
      return [];
    }
    $course_ids = [];
    $groups = $this->membershipManager->getUserGroups($user);
    foreach ($groups['node'] ?? [] as $gid => $group) {
      if ($group->bundle() === 'course') {
        $course_ids[] = (int) $gid;
      }
    }
    return $course_ids;
  }

}
