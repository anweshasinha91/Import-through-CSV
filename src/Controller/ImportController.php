<?php

/**
 * Contains \Drupal\import_through_csv\Controller\ImportController.
 */
namespace Drupal\import_through_csv\Controller;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\import_through_csv\EntityCreate;
use Drupal\import_through_csv\ContentTypeFetch;

class ImportController extends FormBase {

  public  function getFormId() {
    // TODO: Implement getFormId() method.
    return 'import_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    // TODO: Implement buildForm() method.
    $contentTypeObject = new ContentTypeFetch();
    $contentType = $contentTypeObject->fetchEntity();
    $contentTypeList = array();
    foreach ($contentType as $key=>$value) {
      $contentTypeList[$key]=$value;
    }
    $form['contentType'] = array(
      '#type' => 'radios',
      '#options' => $contentTypeList,
      '#title' => $this->t('Content Type'),
      '#description' => $this->t('Select a content type whose content you want to create. If it refers to any entity,
                                        the content of that entity will automatically be created once the csv file is uploaded.
                                        The title of the columns, of your csv file must match with the machine name of the fields. For eg:- If
                                        the machine name of the field is field_book, the title must also be field_book.
       '),
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

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitForm() method.
    $contentType = $form_state->getValue('contentType');
    $csvFile = $form_state->getValue('csv_file');
    $entity_create_object = new EntityCreate();
    $entity_create_object->csvParserList($csvFile[0], $contentType);
  }

 }
