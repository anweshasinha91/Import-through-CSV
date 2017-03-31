<?php
 namespace Drupal\import_through_csv;

 use Drupal\Core\Config\Entity;

 class Fetchfields{
     public function contentTypeFields($contentType) {
         $entityManager = \Drupal::service('entity_field.manager');
         $fields= $entityManager->getFieldDefinitions('node', $contentType);
         foreach($fields as $key=>$value)
         {
             if (strpos($key, 'field_') === 0 || strpos($key, 'title') === 0) {
                 $fieldnames[]=$key;
             }
         }
         return $fieldnames;
     }
 }