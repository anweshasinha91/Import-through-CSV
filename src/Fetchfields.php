<?php

namespace Drupal\import_through_csv;
use Drupal\Core\Config\Entity;
class Fetchfields {

  /**
   * Fetches the  fields of a content type.
   *
   * @param $contentType
   *   The selected content type whose fields are required to be
   *   fetched.
   *
   * @return array
   *   An array of fields of the selected content type.
   */
  public function contentTypeFieldsFetch($contentType) {
    $entityManager = \Drupal::service('entity_field.manager');
    $fields = $entityManager->getFieldDefinitions('node', $contentType);
    foreach ($fields as $key => $value) {
      if (strpos($key, 'field_') === 0 || strpos($key, 'title') === 0) {
        $fieldNames[] = $key;
      }
    }
    return $fieldNames;
  }

}
