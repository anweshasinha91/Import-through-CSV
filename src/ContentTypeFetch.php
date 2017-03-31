<?php
namespace Drupal\import_through_csv;

 use Drupal\Core\Config\Entity;
 class ContentTypeFetch{

     public function fetchEntity(){
         $contentTypes = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();

         $contentTypesList = [];
         foreach ($contentTypes as $contentType) {
             $contentTypesList[$contentType->id()] = $contentType->label();
         }

         return $contentTypesList;
     }
 }