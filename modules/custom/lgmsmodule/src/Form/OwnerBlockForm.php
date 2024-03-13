<?php

namespace Drupal\lgmsmodule\Form;

use Drupal\block\Entity\Block;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\file\Entity\File;

class OwnerBlockForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string
  {
    return 'lgmsmodule_owner_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array
  {
    $user = User::load(\Drupal::currentUser()->id());
    $destination = \Drupal::request()->get('destination');

    $form['first_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First Name'),
      '#default_value' => $user->field_first_name->value ?? '',
      '#required' => FALSE,
    ];

    $form['last_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Last Name'),
      '#default_value' => $user->field_last_name->value ?? '',
      '#required' => FALSE,
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#default_value' => $user->getEmail(),
      '#required' => FALSE,
    ];

    // Include other fields as needed, similar to the phone number field
    $form['phone_number'] = [
      '#type' => 'tel',
      '#title' => $this->t('Phone Number'),
      '#default_value' => $user->field_phone_number->value ?? '',
      '#required' => FALSE, // Set to FALSE if not all users have a phone number
    ];

    // For user picture, use a managed_file type field
    $form['user_picture'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('User Picture'),
      '#upload_location' => 'public://profile_pictures/',
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg'],
      ],
      '#default_value' => $user->user_picture->target_id ? [$user->user_picture->target_id] : NULL,
      '#required' => FALSE,
      '#description' => $this->t('Allowed extensions: png jpg jpeg'),
    ];

    $form['destination'] = [
      '#type' => 'hidden',
      '#value' => $destination,
    ];

    // Load the block configuration
    $block = Block::load('lgmsguideownerblock'); // Replace with your actual block ID
    $body = $block ? $block->get('settings')['body'] : ['value' => '', 'format' => 'basic_html'];

    // Pre-populate the body field with the content from the block configuration
    $form['body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Body'),
      '#default_value' => $body['value'],
      '#description' => $this->t('Add optional body content. This will be displayed in the block.'),
    ];

    // Action buttons
    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update'),
    ];

    $form['actions']['cancel'] = [
      '#type' => 'button',
      '#value' => $this->t('Cancel'),
      '#attributes' => ['class' => ['dialog-cancel']],
      '#ajax' => [
        'callback' => '::closeModalForm',
      ],
      '#limit_validation_errors' => [],
    ];

    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    return $form;
  }

  public function closeModalForm(array &$form, FormStateInterface $form_state): AjaxResponse
  {
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    $user = User::load(\Drupal::currentUser()->id());
    $current_email = $user->getEmail();
    $block_id = 'lgmsguideownerblock';

    if ($user) {
      // Update fields based on the form inputs
      $user->field_first_name->value = $form_state->getValue('first_name');
      $user->field_last_name->value = $form_state->getValue('last_name');
      $user->field_phone_number->value = $form_state->getValue('phone_number');
      $new_email = $form_state->getValue('email');
      // Check if the email is already taken by another user
      if ($new_email !== $current_email) {
        // If the email has changed, check if the new email is already in use.
        $accounts_with_email = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['mail' => $new_email]);

        // Since the current user already has this email, ensure we're not counting them as a duplicate.
        if ($accounts_with_email && !isset($accounts_with_email[$user->id()])) {
          \Drupal::messenger()->addError($this->t('The email address is already in use.'));
          return;
        } else {
          // If the email isn't used by another account, update the user's email.
          $user->setEmail($new_email);
        }
      }

      // Handle user picture file
      $picture_fid = $form_state->getValue(['user_picture', 0]);
      if ($picture_fid) {
        // Set file as permanent and save
        $file = File::load($picture_fid);
        $file->setPermanent();
        $file->save();

        $user->user_picture->target_id = $picture_fid;
      }


      // Load the block configuration.
      $block = Block::load($block_id);
      if ($block) {
        // Get the submitted body value and format
        $body = $form_state->getValue('body');

        // Update the block configuration with the new body content
        $block->set('settings', ['body' => $body] + $block->get('settings'));

        // Save the block configuration
        $block->save();
      }

      $user->save();
      \Drupal::messenger()->addMessage($this->t('Information updated successfully.'));

      // Page refresh
      $destination = $form_state->getValue('destination');
      if ($destination) {
        $form_state->setRedirectUrl(Url::fromUri("internal:" . $destination));
      }
    }
  }
}
