<?php
/**
 * Contains \Drupal\import_through_csv\Controller\ImportController
 */
 namespace Drupal\import_through_csv\Controller;

 use Drupal\Core\Form\FormBase;
 use Drupal\Core\Form\FormStateInterface;
 use Drupal\import_through_csv\Fetchfields;
 use Drupal\import_through_csv\EntityCreate;
 use Drupal\import_through_csv\ContentTypeFetch;


 class ImportController extends FormBase{

     public  function getFormId()
     {
         // TODO: Implement getFormId() method.
         return 'import_form';
     }
     public function buildForm(array $form, FormStateInterface $form_state)
     {
         // TODO: Implement buildForm() method.
         $contentTypeObject = new ContentTypeFetch();
         $contentType= $contentTypeObject->fetchEntity();
            $list[] = array();
         foreach($contentType as $key=>$value)
         {
                $list[$key]=$value;
         }
         $form['contentType'] = array(
             '#type' => 'radios',
             '#options' => $list,
             '#title' => t('Content Type'),
             '#description' =>t('Select a content type whose content you want to create. If it referes to any entity, the content of that entity will automatically be created once the csv file is uploaded.'),
         );
         $form['csv_file'] = array(
             '#type' => 'managed_file',
             '#upload_location' => 'public://csv',
             '#title' => 'CSV File',
             '#upload_validators' => array(
                 'file_validate_extensions' => array('csv')),
         );
         $form['actions']['#type'] = 'actions';
         $form['actions']['submit'] = array(
             '#type' => 'submit',
             '#value' => $this->t('Save'),
             '#button_type' => 'primary',
         );
         return $form;
     }
     public function validateForm(array &$form, FormStateInterface $form_state){
     }

     public function submitForm(array &$form, FormStateInterface $form_state)
     {
         // TODO: Implement submitForm() method.
         $contentType = $form_state->getValue('contentType');
         $csvFile = $form_state->getValue('csv_file');
         $entity_create_object = new EntityCreate();
         $entityCreate = $entity_create_object->csvParserList($csvFile[0],$contentType);
     }
 }