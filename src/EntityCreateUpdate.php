<?php

namespace Drupal\import_through_csv;

use Drupal\Core\Entity;
use Drupal\Core\Field;
use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;

 /**
  * Class EntityCreateUpdate
  * @package Drupal\import_through_csv.
  *         Fetches the imported csv file, parses it, prepares an associative array(containing each row in the file)
  *         and creates entity as well as references entity.
  */

class EntityCreateUpdate {

  /**
   * Checks whether a taxonomy term exist or not. If not then it creates
   * @param $targetEntity
   *    The name of the Taxonomy Vocabulary
   * @param $termName
   *    The name of the Taxonomy Term to be checked
   * @param $csvValue
   *    An array containing the csv file records
   * @return int
   *    tid of the term
   */
  public function termExistOrCreate($targetEntity, $termName, $csvValue) {
    $term = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties(['name' => $termName]);
    if (sizeof($term) == 0) {
      $termList['name'] = $termName;
      $termList['vid'] = $targetEntity[0];
      if (array_key_exists("parent", $csvValue)) {
        $parent = $csvValue['parent'];
        unset($csvValue['parent']);
        $termParent = $this->termExistOrCreate($targetEntity, $parent, $csvValue);
        $termList['parent'] = $termParent;
      }
      $term = Term::create($termList);
      $term->save();
      $term = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadByProperties(['name' => $termName]);
    }
    $termShift = array_shift($term);
    $tid = $termShift->get('tid')->value;
    return $tid;

  }

  /**
   * Checks whether user exist or not. If exists it creates and returns its uid.
   * @param $csvValue
   *    An array containing csv file records/row
   * @param $name
   *    Name of the user
   * @return int
   *    uid of the user.
   */
  public function userExistOrCreate($csvValue, $name) {
    $userRecord = user_load_by_name($name);
    if ($userRecord == FALSE) {
      $userField['name'] = $name;
      $userField['mail'] = $csvValue['mail'];
      $userField['pass'] = $csvValue['pass'];
      $userField['status'] = $csvValue['status'];
      $user = User::create($userField);
      $user->save();
      $userRecord = user_load_by_name($name);
      $uid = $userRecord->get('uid')->value;
    }
    else {
      $uid = $userRecord->get('uid')->value;
    }
    return $uid;
  }

  public function updateEntity($contentType, $csvValue, $title) {
    $emptyArray[]=array();
    $query = \Drupal::entityQuery('node')->condition('type', $contentType)->condition('title', $title, 'CONTAINS');
    $nid = $query->execute();
    if (sizeof($nid) != 0) {
      $fetchFieldObject = new Fetchfields();
      $fields = $fetchFieldObject->contentTypeFieldsFetch($contentType);
      $id = array_keys($nid);
      $loadNode = Node::load($id[0]);
      foreach($fields as $index => $field)
      {
        $fieldInfo = FieldConfig::loadByName('node', $contentType, $field);
        if ($fieldInfo != NULL) {
          $fieldType = $fieldInfo->getType();
          if ($fieldType == 'entity_reference') {
            $fieldSettings = $fieldInfo->getSettings();
            $targetEntityType = explode(':', $fieldSettings['handler']);
            if ($targetEntityType[1] == 'node') {
              $targetEntity = array_keys($fieldSettings['handler_settings']['target_bundles']);
              if (array_key_exists($field, $csvValue)) {
                if (strpos($csvValue[$field], '|') !== false) {
                  $explodeCsvField = explode('|', $csvValue[$field]);
                  $loadNode->$field->setValue();
                  foreach ($explodeCsvField as $multiValueKey => $multiValue) {
                    $fetchQuery = \Drupal::entityQuery('node')->condition('type', $targetEntity[0])->condition('title', $multiValue, 'CONTAINS');
                    $targetNid = $fetchQuery->execute();
                    $fetchNid = array_keys($targetNid);
                    //unset($loadNode->values[$field]['x-default']);
                    $loadNode->$field->appendItem($fetchNid[0]);
                  }
                }
                else {
                  $fetchQuery = \Drupal::entityQuery('node')->condition('type', $targetEntity[0])->condition('title', $csvValue[$field], 'CONTAINS');
                  $targetNid = $fetchQuery->execute();
                  $fetchNid = array_keys($targetNid);
                  $loadNode->set($field, $fetchNid[0]);
                }
              }
            }
            elseif ($targetEntityType[1] == 'taxonomy_term') {
              $targetEntity = array_keys($fieldSettings['handler_settings']['target_bundles']);
              $termName = $csvValue[$field];
              $tid = $this->termExistOrCreate($targetEntity, $termName, $csvValue);
              $loadNode->set($field, $tid);
            }
            else {
              $userId = $this->userExistOrCreate($csvValue, $csvValue[$field]);
              $loadNode->set($field, $userId);
            }
          }
          else {
            if(strpos($csvValue[$field], '|') !== false) {
              $explodeCsvField = explode('|',$csvValue[$field]);
              $loadNode->$field->setValue();
              foreach($explodeCsvField as $multiValueKey => $multiValue) {
                $loadNode->$field->appendItem($multiValue);
              }
            }
            else {
              $loadNode->set($field,$csvValue[$field]);
            }
          }
        }
      }
      $loadNode->save();
    }
  }
  /**
   * Creates entity and references entity.
   * @param $contentType
   *   The bundle whose content is required to be created.
   * @param $csvValue
   *   An array containing the csv file records
   * @param $title
   *   Value for the title field of the selected content Type
   *
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
            $targetEntity = array_keys($fieldSettings['handler_settings']['target_bundles']);
            if (array_key_exists($field, $csvValue)) {
              if (strpos($csvValue[$field], '|') !== false) {
                $explodeCsvField = explode('|', $csvValue[$field]);
                foreach ($explodeCsvField as $multiValueKey => $multiValue) {
                  $query = \Drupal::entityQuery('node')->condition('type', $targetEntity[0])->condition('title', $multiValue, 'CONTAINS');
                  $nid = $query->execute();
                  if (sizeof($nid) == 0) {
                    $targetIdRecords = array_keys($this->createEntity($targetEntity[0], $csvValue, $multiValue));
                    $list[$field][$multiValueKey]['target_id'] = $targetIdRecords[0];
                  } else {
                    $id = array_keys($nid);
                    $list[$field][$multiValueKey]['target_id'] = $id[0];
                  }
                }
              } else {
                $query = \Drupal::entityQuery('node')->condition('type', $targetEntity[0])->condition('title', $csvValue[$field], 'CONTAINS');
                $nid = $query->execute();
                if (sizeof($nid) == 0) {
                  $targetIdRecords = array_keys($this->createEntity($targetEntity[0], $csvValue, $csvValue[$field]));
                  $list[$field]['target_id'] = $targetIdRecords[0];
                } else {
                  $id = array_keys($nid);
                  $list[$field]['target_id'] = $id[0];
                }
              }
            }
          }
          elseif ($targetEntityType[1] == 'taxonomy_term') {
            $targetEntity = array_keys($fieldSettings['handler_settings']['target_bundles']);
            $termName = $csvValue[$field];
            $tid = $this->termExistOrCreate($targetEntity, $termName, $csvValue);
            $list[$field]['target_id'] = $tid;
          }
          else {
            $userId = $this->userExistOrCreate($csvValue, $csvValue[$field]);
            $list[$field]['target_id'] = $userId;
          }

        }
        else {
          if(strpos($csvValue[$field], '|') !== false) {
              $explodeCsvField = explode('|',$csvValue[$field]);
              foreach($explodeCsvField as $multiValueKey => $multiValue) {
                $list[$field][$multiValueKey] = $multiValue;
              }
          }
          else {
            $list[$field] = $csvValue[$field];
          }
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
   * @param $update
   *   The value of update
   */
  public function csvParserList($csvFileFid, $contentType, $update) {
    $file = \Drupal\file\Entity\File::load($csvFileFid);
    $path = $file->getFileUri();
    $csv = array_map('str_getcsv', file($path));
    foreach ($csv[0] as $headerId => $headerValue) {
      $headerTitle[] = $headerValue;
    }
    unset($csv[0]);
    $items = array();
    foreach ($csv as $id => $value) {
      foreach ($value as $key => $csvValue) {
        $items[$id][$headerTitle[$key]] = $csvValue;
      }
    }
    $entity[] = array();
    if($update == 0) {
      foreach ($items as $csvId => $csvValue) {
        $title = $csvValue['title'];
        $entity = $this->createEntity($contentType, $csvValue, $title);
      }
      drupal_set_message(t('Entities created'));
    }
    else {
      foreach ($items as $csvId => $csvValue) {
        $title = $csvValue['title'];
        $this->updateEntity($contentType,$csvValue,$title);
      }
      drupal_set_message(t('Entities updated'));
    }
  }

}
