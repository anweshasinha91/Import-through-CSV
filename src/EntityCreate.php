<?php
namespace Drupal\import_through_csv;

 use Drupal\Core\Entity;
 use Drupal\Core\Field;
 use Drupal\field\Entity\FieldConfig;
 use Drupal\import_through_csv\Fetchfields;
 use \Drupal\node\Entity\Node;
 use \Drupal\taxonomy\Entity\Term;

class EntityCreate{
    public function createEntity($contentType,$csvValue,$title)
    {
        $obj1 = new Fetchfields();
        $fields = $obj1->contentTypeFields($contentType);
        $list['type'] = $contentType;
        foreach($fields as $fieldId => $field) {
            $fieldInfo = FieldConfig::loadByName('node', $contentType, $field);
            $list['title'] = $title;
            if ($fieldInfo != null) {
                $fieldType = $fieldInfo->getType();
                if ($fieldType == 'entity_reference') {
                    $settings = $fieldInfo->getSettings();
                    $targetEntityType = explode(':',$settings['handler']);
                    if($targetEntityType[1] == 'node')
                    {
                        $targetBundle = $settings['handler_settings']['target_bundles'];
                        foreach($targetBundle as $target => $bundle)
                        {
                            if(array_key_exists($field, $csvValue)) {
                                $query = \Drupal::entityQuery('node')->condition('type', $target)->condition('title', $csvValue[$field], 'CONTAINS');
                                $nid = $query->execute();
                                if (sizeof($nid) == 0) {
                                    $value = $this->createEntity($target, $csvValue, $csvValue[$field]);
                                    foreach ($value as $key => $value3)
                                        $list[$field]['target_id'] = $key;
                                } else {
                                    foreach ($nid as $id => $value1)
                                        $list[$field]['target_id'] = $id;
                                }
                            }
                        }
                    }
                   elseif($targetEntityType[1] == 'taxonomy_term')
                   {
                       $targetBundle = $settings['handler_settings']['target_bundles'];
                       foreach($targetBundle as $target => $bundle)
                       {
                           $term_name = $csvValue[$field];
                           $term = \Drupal::entityTypeManager()
                               ->getStorage('taxonomy_term')
                               ->loadByProperties(['name' => $term_name]);
                           if(sizeof($term) == 0)
                           {
                               $termList['name'] = $csvValue[$field];
                               $termList['vid'] = $target;
                               $term = Term::create($termList);
                               $term->save();
                               $term_name = $csvValue[$field];
                               $term = \Drupal::entityTypeManager()
                                   ->getStorage('taxonomy_term')
                                   ->loadByProperties(['name' => $term_name]);
                               $termShift = array_shift($term);
                               $tid = $termShift->values['tid']['x-default'];
                               $list[$field]['tid'] = $tid;
                           }
                           else{
                               $termShift = array_shift($term);
                               $tid = $termShift->values['tid']['x-default'];
                               $list[$field]['tid'] = $tid;
                           }
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
    public function prepareList($csvFile,$contentType)
    {
        $csv_file_fid = $csvFile[0];
        $file = \Drupal\file\Entity\File::load($csv_file_fid);
        $path = $file->getFileUri();
        $csv = array_map('str_getcsv', file($path));
        foreach($csv[0] as $headerId=>$headerValue) {
            $headerTitle[]=$headerValue;
        }
        unset($csv[0]);
        foreach($csv as $id=>$value)
        {
            foreach($value as $key=>$csvValue)
            {
                $items[$id][$headerTitle[$key]]=$csvValue;
            }
        }
        $entity[] = array();
        $i = 0;
        foreach($items as $csvId=>$csvValue)
        {
            $title = $csvValue['title'];
            //$obj = new EntityCreate();
            $entity = $this->createEntity($contentType,$csvValue,$title);
            $i++;
        }
        drupal_set_message($i.' entities created');
    }
}