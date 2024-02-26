<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

class AddHTMLForm extends FormBase {

  public function getFormId() {
    return 'add_html_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $current_box = \Drupal::request()->query->get('current_box');
    $form['current_box'] = [
      '#type' => 'hidden',
      '#value' => $current_box,
    ];

    $current_node = \Drupal::request()->query->get('current_node');
    $form['current_node'] = [
      '#type' => 'hidden',
      '#value' => $current_node,
    ];

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Item Title:'),
      '#required' => TRUE,
    ];


    // Body field
    $form['body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Body'),
      '#after_build' => [[get_class($this), 'hideTextFormatHelpText'],],
      '#required' => TRUE,
    ];


    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  public static function hideTextFormatHelpText(array $element, FormStateInterface $form_state) {
    if (isset($element['format']['help'])) {
      $element['format']['help']['#access'] = FALSE;
    }
    if (isset($element['format']['guidelines'])) {
      $element['format']['guidelines']['#access'] = FALSE;
    }
    if (isset($element['format']['#attributes']['class'])) {
      unset($element['format']['#attributes']['class']);
    }
    return $element;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_node = $form_state->getValue('current_node');
    $current_node = Node::load($current_node);

    $current_box = $form_state->getValue('current_box');
    $current_box = Node::load($current_box);

    $new_node = Node::create([
      'type' => 'guide_item',
      'title' => $form_state->getValue('title'),
      'field_text_box_item' => [
        'value' => $form_state->getValue('body')['value'],
        'format' => 'full_html',
      ],
    ]);

    $new_node->save();

    $boxList = $current_box->get('field_box_items')->getValue();
    $boxList[] = ['target_id' => $new_node->id()];

    $current_box->set('field_box_items', $boxList);
    $current_box->save();

    $curr_node_url = $current_node->toUrl()->toString();
    $curr_node_url = str_replace('LGMS/', '', $curr_node_url);

    $node_path = str_replace('LGMS/', '', $curr_node_url);

    $form_state->setRedirectUrl(Url::fromUri('internal:' . $node_path));

    \Drupal::messenger()->addMessage($this->t('a box item has been added.'));
  }
}
