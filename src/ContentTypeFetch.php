<?php

namespace Drupal\import_through_csv;

use Drupal\Core\Config\Entity;

class ContentTypeFetch {

  /**
   * Fetch all the content type of your site.
   *
   * @return array
   *   An array of content type of the site
   */
  public function fetchEntity() {
    $contentTypes = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();
    $contentTypesList[] = array();
    foreach ($contentTypes as $contentType) {
      $contentTypesList[$contentType->id()] = $contentType->label();
    }
    return $contentTypesList;
  }

 }
