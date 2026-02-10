<?php

/**
 * @file
 * Deploy hooks for shentity.
 */

use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Add full_key field to shentity entity.
 */
function shentity_deploy_10000_fullcsv() {
  $entity_type_manager = \Drupal::entityTypeManager();
  $entity_definition_update_manager = \Drupal::entityDefinitionUpdateManager();

  // Get the field storage definition for full_key.
  $field_storage_definition = BaseFieldDefinition::create('string')
    ->setLabel(t('Full share CSV url'))
    ->setDescription(t('Newer sheets changed the url. Grab the full csv url and place it here'))
    ->setSettings([
      'max_length' => 255,
      'text_processing' => 0,
    ])
    ->setDefaultValue(NULL)
    ->setRequired(FALSE);

  // Install the new field.
  $entity_definition_update_manager->installFieldStorageDefinition('full_key', 'shentity', 'shentity', $field_storage_definition);

  // Entity query 'shentity' to get all entities of type 'shentity' with a null
  // 'full_key'.
  $query = $entity_type_manager->getStorage('shentity')->getQuery();
  $query->condition('full_key', NULL, 'IS NULL');
  $query->accessCheck(FALSE);
  $result = $query->execute();

  // Update each entity with the new full_key value.
  foreach ($result as $entity_id) {
    $entity = $entity_type_manager->getStorage('shentity')->load($entity_id);
    $key = $entity->get('key')->value;
    $gid = $entity->get('sheet_number')->value;
    $full_key = "https://docs.google.com/spreadsheets/d/$key/pub?gid=$gid&single=true&output=csv";
    $entity->set('full_key', $full_key);
    $entity->save();
  }
}
