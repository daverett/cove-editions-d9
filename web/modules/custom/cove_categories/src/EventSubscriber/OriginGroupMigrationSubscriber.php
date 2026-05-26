<?php

declare(strict_types=1);

namespace Drupal\cove_categories\EventSubscriber;

use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigImporterEvent;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Populates field_origin_group on existing parents at the end of a config
 * import.
 *
 * Reads STATE_FLAG from state; when set, copies og_group_ref into
 * field_origin_group on chronology, map and galler_exhibit nodes, then clears
 * the flag.
 */
class OriginGroupMigrationSubscriber implements EventSubscriberInterface {

  public const STATE_FLAG = 'cove_categories.origin_group_migration_pending';

  private const CHUNK_SIZE = 50;

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly StateInterface $state,
    private readonly LoggerChannelInterface $logger,
  ) {}

  public static function getSubscribedEvents(): array {
    return [ConfigEvents::IMPORT => 'onConfigImport'];
  }

  public function onConfigImport(ConfigImporterEvent $event): void {
    if (!$this->state->get(self::STATE_FLAG)) {
      return;
    }

    $parent_bundles = array_keys(_cove_categories_parent_children_map());
    $storage = $this->entityTypeManager->getStorage('node');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', $parent_bundles, 'IN')
      ->exists('og_group_ref')
      ->execute();

    $count = 0;
    foreach (array_chunk($ids, self::CHUNK_SIZE) as $chunk) {
      foreach ($storage->loadMultiple($chunk) as $node) {
        if (!$node->hasField('field_origin_group') || !$node->get('field_origin_group')->isEmpty()) {
          continue;
        }
        $course = $node->get('og_group_ref')->target_id;
        if ($course !== NULL) {
          $node->set('field_origin_group', $course);
          $node->save();
          $count++;
        }
      }
      $storage->resetCache($chunk);
    }

    $this->state->delete(self::STATE_FLAG);
    $this->logger->notice('Populated field_origin_group on @count parent nodes.', ['@count' => $count]);
  }

}
