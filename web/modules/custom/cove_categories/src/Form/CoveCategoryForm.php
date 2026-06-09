<?php

declare(strict_types=1);

namespace Drupal\cove_categories\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\cove_categories\Access\CoveCategoryAccess;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Add and edit form for the Category entity.
 */
final class CoveCategoryForm extends ContentEntityForm {

  private CoveCategoryAccess $access;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    $instance = parent::create($container);
    $instance->access = $container->get('cove_categories.access');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    // When the form is opened from a course (?group={nid}), pre-scope the
    // category to that course and hide the field so it cannot be changed.
    // The pre-scope is only honored when the user can actually manage
    // categories of that course; otherwise we fall through to the regular
    // form, where the autocomplete filters to the user's own courses.
    $prescoped = FALSE;
    $group_id = $this->getRequest()->query->get('group');
    if ($this->entity->isNew() && is_numeric($group_id)) {
      $node = $this->entityTypeManager->getStorage('node')->load($group_id);
      if ($node instanceof NodeInterface
        && $node->bundle() === 'course'
        && $this->userCanManageCourse($node)) {
        $this->entity->set('group', $node->id());
        $prescoped = TRUE;
      }
    }

    $form = parent::form($form, $form_state);

    if (isset($form['group'])) {
      if ($prescoped) {
        // Creating from a course: the course is fixed and not shown.
        $form['group']['#access'] = FALSE;
      }
      elseif (!$this->entity->isNew()) {
        // Editing: a category belongs to one course for good.
        $form['group']['#disabled'] = TRUE;
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $this->messenger()->addStatus($this->t('The category %label has been saved.', [
      '%label' => $this->entity->label(),
    ]));

    // The global category collection is admin-only; group leaders are sent
    // back to the categories tab of the entity's course, which they can see.
    if ($this->currentUser()->hasPermission('administer cove_category')) {
      $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    }
    else {
      /** @var \Drupal\cove_categories\Entity\CoveCategoryInterface $cat */
      $cat = $this->entity;
      $course_id = $cat->getGroupId();
      if ($course_id !== NULL) {
        $form_state->setRedirect('cove_categories.course_categories', ['node' => $course_id]);
      }
    }

    return $result;
  }

  /**
   * Whether the current user can manage categories of the given course.
   */
  private function userCanManageCourse(NodeInterface $course): bool {
    return $this->access->isCourseManager($course, $this->currentUser());
  }

}
