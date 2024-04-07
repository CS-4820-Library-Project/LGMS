<?php
namespace Drupal\lgmsmodule\Form;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

class ReuseGuidePageForm extends FormBase {

  public function getFormId(): string
  {
    return 'lgmsmodule_reuse_guide_page_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $ids = null): array
  {
    // Set the prefix, suffix, and hidden fields
    $form_helper = new FormHelper();
    $form_helper->set_form_data($form,$ids, $this->getFormId());

    $form['select_page'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Page'),
      '#options' => $this->get_all_pages($ids->current_guide),
      '#empty_option' => $this->t('- Select a Page -'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::IncludeSubCallBack',
        'wrapper' => 'include-sub-wrapper',
        'event' => 'change',
      ],
    ];

    $form['include_sub_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'include-sub-wrapper'],
    ];

    $form['include_sub_wrapper']['include_sub'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include Subpages'),
      '#ajax' => [
        'callback' => '::position_callback',
        'wrapper' => 'position-wrapper',
        'event' => 'change',
      ],
    ];

    $form['include_sub_wrapper']['reference'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('<Strong>Link:</Strong> By selecting this, a link to the HTML item will be created.
                    it will be un-editable from this box'),
    ];

    $form['include_sub_wrapper']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#states' => [
        'visible' => [
          ':input[name="reference"]' => ['checked' => False],
        ],
        'required' => [
          ':input[name="reference"]' => ['checked' => False],
        ],
      ],
    ];

    $form['include_sub_wrapper']['position_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'position-wrapper'],
    ];

    $include_sub = $form_state->getValue('include_sub') == '1';

    $form['include_sub_wrapper']['position_wrapper']['position'] = [
      '#type' => 'select',
      '#title' => $this->t('Position'),
      '#options' => $form_helper->get_position_options($form_state, $ids->current_guide),
      '#required' => !$include_sub,
      '#default_value' => $include_sub? $form_state->getValue('current_guide'): null,
      '#disabled' => $include_sub
    ];

    $form['current_node'] = [
      '#type' => 'hidden',
      '#value' => NULL,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reuse Guide Page'),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => '::submitAjax',
        'event' => 'click',
      ],
    ];

    return $form;
  }

  public function position_callback(array &$form, FormStateInterface $form_state) {
    return $form['include_sub_wrapper']['position_wrapper'];
  }

  public function IncludeSubCallBack(array &$form, FormStateInterface $form_state) {
    $selected_page = $form_state->getValue('select_page');

    // Check if a page is selected and it's not the empty option.
    if (!empty($selected_page)) {
      // Load the selected page node to check its field_child_pages.
      $page_node = Node::load($selected_page);

      if ($page_node) {
        $child_pages = $page_node->get('field_child_pages')->getValue();

        // If there are no child pages, disable the "Include Subpages" checkbox.
        if (empty($child_pages)) {
          $form['include_sub_wrapper']['include_sub']['#checked'] = FALSE;
          $form['include_sub_wrapper']['include_sub']['#attributes']['disabled'] = 'disabled';

        } else {
          // Ensure it is not disabled if there are child pages.
          unset($form['include_sub_wrapper']['include_sub']['#checked']);
          unset($form['include_sub_wrapper']['include_sub']['#attributes']['disabled']);
        }
      }
    } else {
      // If no page is selected, ensure the "Include Subpages" checkbox is not disabled.
      unset($form['include_sub_wrapper']['include_sub']['#attributes']['disabled']);
    }

    return $form['include_sub_wrapper'];
  }

  /**
   * @throws EntityMalformedException
   */
  public function submitAjax(array &$form, FormStateInterface $form_state): AjaxResponse
  {
    // Create an array of AJAX commands.
    $ajaxHelper = new FormHelper();

    return $ajaxHelper->submitModalAjax($form, $form_state, 'Page created successfully.', '#'.$this->getFormId());
  }

  /**
   * @throws EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    $ajaxHelper = new FormHelper();

    // Load values
    $page = Node::load($form_state->getValue('select_page'));
    $parent = Node::load($form_state->getValue('position'));

    // The user is making a copy
    if(!$form_state->getValue('reference')){
      $new_page = $page->createDuplicate(); //$this->copyPage($page, $parent, $form_state->getValue('title'));
      $new_page->setOwnerId(\Drupal::currentUser()->id());
      $new_page->setTitle($form_state->getValue('title'));
      $new_page->set('field_child_pages', []);
      $new_page->save();

      $ajaxHelper->clone_boxes($page, $new_page);

      if($form_state->getValue('include_sub') == '1'){
        $ajaxHelper->clone_pages($page, $new_page);

      }
    } else { // The user is making a link to a page
      // Create a copy and update parent and reference fields
      $new_page = $page->createDuplicate();
      $new_page->set('field_parent_guide', $parent);
      $new_page->set('field_reference_node', $page);
      $new_page->set('field_child_pages', []);
      $new_page->save();

      if($form_state->getValue('include_sub') == '1'){
        $ajaxHelper->clone_pages($page, $new_page, true);
      }
    }
    $new_page->save();

    // Set redirection
    $form_state->setValue('current_node', $new_page->id());

    // Update child page list
    $ajaxHelper->add_child($parent, $new_page, 'field_child_pages');
  }

  private function get_all_pages($guide_id): array
  {
    $options = [];

    // Fetch all guides.
    $guides = Node::loadMultiple(
      \Drupal::entityQuery('node')
        ->condition('type', 'guide')
        ->sort('title', 'ASC')
        ->accessCheck(True)
        ->execute()
    );

    foreach ($guides as $guide) {
      if ($guide->hasField('field_child_pages')) {
        $child_pages_ids = array_column($guide->get('field_child_pages')->getValue(), 'target_id');
        $child_pages = !empty($child_pages_ids) ? Node::loadMultiple($child_pages_ids) : [];

        // Create an optgroup for the guide.
        $options[$guide->getTitle()] = [];

        foreach ($child_pages as $child_page) {
          // Add the child page under the guide.
          $options[$guide->getTitle()][$child_page->id()] = $child_page->getTitle();

          // Check if the child page has its own subpages.
          if ($child_page->hasField('field_child_pages')) {
            $subpages_ids = array_column($child_page->get('field_child_pages')->getValue(), 'target_id');
            $subpages = !empty($subpages_ids) ? Node::loadMultiple($subpages_ids) : [];

            // Label each subpage with the parent page title.
            foreach ($subpages as $subpage) {
              $label = '— ' . $child_page->getTitle() . ' subpage: ' . $subpage->getTitle();
              $options[$guide->getTitle()][$subpage->id()] = $label;
            }
          }
        }
      }
    }

    return $options;
  }
}
