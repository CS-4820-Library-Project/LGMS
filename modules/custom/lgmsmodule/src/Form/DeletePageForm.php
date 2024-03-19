<?php

namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

class DeletePageForm extends FormBase
{

  public function getFormId()
  {
    return 'delete_page_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $form['#prefix'] = '<div id="modal-form">';
    $form['#suffix'] = '</div>';
    $form['messages'] = [
      '#weight' => -9999,
      '#type' => 'status_messages',
    ];

    $current_page = \Drupal::request()->query->get('current_page');
    if ($current_page) {
      $current_page = Node::load($current_page);
    }



    if ($current_page) {
      $form['current_page'] = [
        '#type' => 'hidden',
        '#value' => $current_page->id(),
      ];

      $title = $this->t('<strong>Are you sure you want to delete this page?</strong>
                                Deleting this page will remove it permanently from the system!');
      $form['warning'] = [
        '#type' => 'markup',
        '#markup' => $title,
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      ];

      $form['confirm_delete'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Yes, I understand and want to delete this page.'),
        '#required' => true,
      ];

      $form['actions']['#type'] = 'actions';
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Delete'),
        '#button_type' => 'danger',
      ];

    } else {
      $form['error'] = [
        '#type' => 'markup',
        '#markup' => $this->t('The page could not be found.'),
      ];
    }

    return $form;
  }


  public function submitForm(array &$form, FormStateInterface $form_state)
  {

    if ($form_state->getValue('confirm_delete')) {

        $current_page_id = $form_state->getValue('current_page');
        $current_page = Node::load($current_page_id);
      $guide_field = $current_page->get('field_parent_guide')->getValue();
      $guide_id = $guide_field[0]['target_id'] ?? null;

        if ($current_page) {
          // Identify and delete all boxes associated with the current page
          $boxes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['field_parent_node' => $current_page_id]);
          foreach ($boxes as $box) {
            $box->delete();
          }

          // Identify and delete all subpages (and their associated boxes) of the current page
          $subpages = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['field_parent_guide' => $current_page_id]);
          foreach ($subpages as $subpage) {
            // Delete boxes associated with the subpage
            $subpageBoxes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['field_parent_node' => $subpage->id()]);
            foreach ($subpageBoxes as $box) {
              $box->delete();
            }
            // Now delete the subpage itself
            $subpage->delete();
          }

          // Finally, delete the main page
          $current_page->delete();
          \Drupal::messenger()->addMessage($this->t('The page, its subpages, and all related boxes have been deleted.'));
          if ($guide_id) {
            $form_state->setRedirect('entity.node.canonical', ['node' => $guide_id]);
          } else {
            $form_state->setRedirect('<front>');
          }
        }

      }
    }
}
