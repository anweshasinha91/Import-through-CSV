<?php

namespace Drupal\import_through_csv;

use Drupal\Core\Entity;
use Drupal\Core\Field;
use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;

 /**
  * Class EntityCreate
  * @package Drupal\import_through_csv.
  *         Fetches the imported csv file, parses it, prepares an associative array(containing each row in the file)
  *         and creates entity as well as references entity.
  */

class EntityCreate {

  /**
   * Creates entity and references entity.
   * @param $contentType
   *   The bundle whose content is required to be created.
   * @param $csvValue
   *   An array containing the csv file records
   * @param $title
   *   Value for the title field of the selected content Type
   * @return int
   *   Id of the created entity
   */
  public function createEntity($contentType, $csvValue, $title) {
    $fetchFieldObject = new Fetchfields();
    $fields = $fetchFieldObject->contentTypeFieldsFetch($contentType);
    $list['type'] = $contentType;
    foreach ($fields as $fieldId => $field) {
      $fieldInfo = FieldConfig::loadByName('node', $contentType, $field);
      $list['title'] = $title;
      if ($fieldInfo != NULL) {
        $fieldType = $fieldInfo->getType();
        if ($fieldType == 'entity_reference') {
          $fieldSettings = $fieldInfo->getSettings();
          $targetEntityType = explode(':',$fieldSettings['handler']);
          if ($targetEntityType[1] == 'node') {
            $targetBundle = array_keys($fieldSettings['handler_settings']['target_bundles']);
            if (array_key_exists($field, $csvValue)) {
              $query = \Drupal::entityQuery('node')->condition('type', $targetBundle[0])->condition('title', $csvValue[$field], 'CONTAINS');
              $nid = $query->execute();
              if (sizeof($nid) == 0) {
                $targetIdRecords = array_keys($this->createEntity($targetBundle[0], $csvValue, $csvValue[$field]));
                $list[$field]['target_id'] = $targetIdRecords[0];
              }
              else {
                $id = array_keys($nid);
                $list[$field]['target_id'] = $id[0];
              }
            }
          }
          elseif ($targetEntityType[1] == 'taxonomy_term') {
            $targetBundle = array_keys($fieldSettings['handler_settings']['target_bundles']);
            $term_name = $csvValue[$field];
            $term = \Drupal::entityTypeManager()
              ->getStorage('taxonomy_term')
              ->loadByProperties(['name' => $term_name]);
            if (sizeof($term) == 0) {
              $termList['name'] = $csvValue[$field];
              $termList['vid'] = $targetBundle[0];
              $term = Term::create($termList);
              $term->save();
              $term = \Drupal::entityTypeManager()
                ->getStorage('taxonomy_term')
                ->loadByProperties(['name' => $csvValue[$field]]);
              $termShift = array_shift($term);
              $tid = $termShift->get('tid')->value;
              $list[$field]['target_id'] = $tid;
            }
            else{
              $termShift = array_shift($term);
              $tid = $termShift->get('tid')->value;
              $list[$field]['target_id'] = $tid;
            }
          }
          else{
            $userRecord = user_load_by_name($csvValue[$field]);
            if ($userRecord == FALSE) {
              $userField['name'] = $csvValue[$field];
              $userField['mail'] = $csvValue['mail'];
              $userField['pass'] = $csvValue['pass'];
              $userField['status'] = $csvValue['status'];
              $user = User::create($userField);
              $user->save();
              $userRecord = user_load_by_name($csvValue[$field]);
              $uid = $userRecord->get('uid')->value;
              $list[$field]['target_id'] = $uid;
            }
            else{
              $userRecord = user_load_by_name($csvValue[$field]);
              $uid = $userRecord->get('uid')->value;
              $list[$field]['target_id'] = $uid;
            }
          }

        }
        else{
          $list[$field] = $csvValue[$field];
        }

      }
    }
    $node = Node::create($list);
    $node->save();
    $query = \Drupal::entityQuery('node')->condition('type', $contentType)->condition('title', $title,'CONTAINS');
    $nid = $query->execute();
    return $nid;
  }

  /**
   * Fetches the csv file, parses it and prepares an associative array containing each row of the csv file.
   * @param $csvFileFid
   *   Fid of the csv file uploaded
   * @param $contentType
   *   The content type whose content is required to be created
   */
  public function csvParserList($csvFileFid,$contentType) {
     $file = \Drupal\file\Entity\File::load($csvFileFid);
     $path = $file->getFileUri();
     $csv = array_map('str_getcsv', file($path));
     foreach ($csv[0] as $headerId => $headerValue) {
       $headerTitle[]=$headerValue;
     }
     unset($csv[0]);
     foreach ($csv as $id => $value) {
       foreach ($value as $key => $csvValue) {
         $items[$id][$headerTitle[$key]] = $csvValue;
       }
     }
     $entity[] = array();
     foreach ($items as $csvId => $csvValue) {
       $title = $csvValue['title'];
       $entity = $this->createEntity($contentType, $csvValue, $title);
     }
     drupal_set_message(t('Entities created'));
  }

}
