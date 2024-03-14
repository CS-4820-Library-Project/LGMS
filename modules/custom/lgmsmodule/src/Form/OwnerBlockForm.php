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
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $user = $this->getCurrentUser();
    $destination = $this->getCurrentDestination();

    $this->addUserFields($form, $user);
    $this->addUserPictureField($form, $user);
    $this->addBodyField($form);
    $this->addHiddenDestinationField($form, $destination);
    $this->addActionButtons($form);

    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    return $form;
  }

  protected function getCurrentUser() {
    return User::load(\Drupal::currentUser()->id());
  }

  protected function getCurrentDestination() {
    return \Drupal::request()->get('destination');
  }

  protected function addUserFields(array &$form, $user): void {
    $fields = [
      'first_name' => 'First Name',
      'last_name' => 'Last Name',
      'email' => 'Email',
      'phone_number' => 'Phone Number',
    ];

    foreach ($fields as $fieldName => $fieldTitle) {
      $formFieldType = $fieldName === 'email' ? 'email' : 'textfield';
      $defaultValue = $fieldName === 'email' ? $user->getEmail() : $user->{'field_' . $fieldName}->value ?? '';
      $form[$fieldName] = [
        '#type' => $formFieldType,
        '#title' => $this->t($fieldTitle),
        '#default_value' => $defaultValue,
        '#required' => FALSE,
      ];
    }
  }

  protected function addUserPictureField(array &$form, $user): void {
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
  }

  protected function addBodyField(array &$form): void {
    $block = Block::load('lgmsguideownerblock');
    $body = $block ? $block->get('settings')['body'] : ['value' => '', 'format' => 'basic_html'];

    $form['body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Body'),
      '#default_value' => $body['value'],
      '#description' => $this->t('Add optional body content. This will be displayed in the block.'),
    ];
  }

  protected function addHiddenDestinationField(array &$form, $destination): void {
    $form['destination'] = [
      '#type' => 'hidden',
      '#value' => $destination,
    ];
  }

  protected function addActionButtons(array &$form): void {
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
  }

  public function closeModalForm(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    return $response;
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $user = $this->getCurrentUser();
    if (!$user) {
      return;
    }

    $this->updateUserFields($user, $form_state);
    $this->updateUserEmail($user, $form_state);
    $this->handleUserPicture($user, $form_state);
    $this->updateBlockConfiguration($form_state);

    $user->save();
    \Drupal::messenger()->addMessage($this->t('Information updated successfully.'));
    $this->redirectTo($form_state);
  }

  protected function updateUserFields(User $user, FormStateInterface $form_state): void {
    $fieldsToUpdate = ['field_first_name', 'field_last_name', 'field_phone_number'];
    foreach ($fieldsToUpdate as $field) {
      if ($user->hasField($field)) {
        $user->$field->value = $form_state->getValue(str_replace('field_', '', $field));
      }
    }
  }

  protected function updateUserEmail(User $user, FormStateInterface $form_state): void {
    $new_email = $form_state->getValue('email');
    if ($new_email && $new_email !== $user->getEmail()) {
      if ($this->isEmailInUse($new_email, $user->id())) {
        \Drupal::messenger()->addError($this->t('The email address is already in use.'));
      } else {
        $user->setEmail($new_email);
      }
    }
  }

  protected function isEmailInUse(string $email, int $excludeUserId = null): bool {
    $accounts_with_email = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['mail' => $email]);
    if ($excludeUserId) {
      unset($accounts_with_email[$excludeUserId]);
    }
    return !empty($accounts_with_email);
  }

  protected function handleUserPicture(User $user, FormStateInterface $form_state): void {
    $picture_fid = $form_state->getValue(['user_picture', 0]);
    if ($picture_fid) {
      $file = File::load($picture_fid);
      if ($file) {
        $file->setPermanent();
        $file->save();
        $user->user_picture->target_id = $picture_fid;
      }
    }
  }

  protected function updateBlockConfiguration(FormStateInterface $form_state): void {
    $block_id = 'lgmsguideownerblock';
    $block = Block::load($block_id);
    if ($block) {
      $body = $form_state->getValue('body');
      $block->set('settings', ['body' => $body] + $block->get('settings'));
      $block->save();
    }
  }

  protected function redirectTo(FormStateInterface $form_state): void {
    $destination = $form_state->getValue('destination');
    if ($destination) {
      $form_state->setRedirectUrl(Url::fromUri("internal:" . $destination));
    }
  }
}
