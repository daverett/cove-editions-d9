<?php

/**
 * @file
 * Copies field_geocode into field_geocoder for a course group's places.
 *
 * The custom_map view plots field_geocoder, which was never filled on old
 * content, while the coordinates already live in field_geocode. Both are
 * geofields, so copying the stored value is exact (no WKT/GeoJSON conversion,
 * that is only the widget format). One-shot data repair.
 * Dry-run by default; pass `-- live` to write. Back up the DB first.
 */

declare(strict_types=1);

$group_nid = 12722;
$chunk_size = 50;

// Dry-run unless `-- live` is passed. Drush php:script exposes post-`--`
// arguments in $extra.
$dry_run = !(isset($extra) && is_array($extra) && in_array('live', $extra, TRUE));

$node_storage = \Drupal::entityTypeManager()->getStorage('node');

$map_ids = $node_storage->getQuery()
  ->accessCheck(FALSE)
  ->condition('type', 'map')
  ->condition('og_group_ref', $group_nid)
  ->execute();

if (!$map_ids) {
  echo "No maps found for group {$group_nid}. Nothing to do.\n";
  return;
}

// Places under those maps that have coordinates in field_geocode but an empty
// field_geocoder (the field the map reads).
$place_ids = $node_storage->getQuery()
  ->accessCheck(FALSE)
  ->condition('type', 'place')
  ->condition('field_parent_map', $map_ids, 'IN')
  ->exists('field_geocode')
  ->notExists('field_geocoder')
  ->execute();

$total = count($place_ids);
echo ($dry_run ? '[DRY-RUN] ' : '[LIVE] ')
  . "Group {$group_nid}: {$total} place(s) to fix (geocode present, geocoder empty).\n";

$done = 0;
$skipped = 0;
$failed = 0;
foreach (array_chunk($place_ids, $chunk_size) as $ids) {
  foreach ($node_storage->loadMultiple($ids) as $place) {
    $source = $place->get('field_geocode')->getValue();
    // Never write an empty geometry over anything.
    if (empty($source) || empty($source[0]['value'])) {
      $skipped++;
      echo "  skip node/{$place->id()} ({$place->label()}): empty source geometry\n";
      continue;
    }
    if ($dry_run) {
      $done++;
      echo "  would fix node/{$place->id()} - {$place->label()}\n";
      continue;
    }
    try {
      $place->set('field_geocoder', $source);
      $place->setNewRevision(FALSE);
      // Pure data backfill: setSyncing keeps the "changed" date and skips the
      // CER parent-map re-sync, since no reference is actually being changed.
      $place->setSyncing(TRUE);
      $place->save();
      $done++;
      echo "  fixed node/{$place->id()} - {$place->label()}\n";
    }
    catch (\Throwable $e) {
      $failed++;
      echo "  ERROR node/{$place->id()} - {$place->label()}: {$e->getMessage()}\n";
    }
  }
  $node_storage->resetCache($ids);
}

echo "\nDone. " . ($dry_run ? 'would fix' : 'fixed')
  . ": {$done}, skipped: {$skipped}, failed: {$failed}, total targeted: {$total}.\n";
if (!$dry_run) {
  echo "Now run `drush cr` so the map views refresh.\n";
}
