<?php

namespace Drupal\lgmsmodule\Form;

use Drupal\block\Entity\Block;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Entity\EntityStorageException;
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
    // First Name
    if ($user->hasField('field_lgms_first_name')) {
      $form['field_lgms_first_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('First Name'),
        '#default_value' => $user->get('field_lgms_first_name')->value ?? '',
        '#required' => FALSE,
      ];
    }

    // Last Name
    if ($user->hasField('field_lgms_last_name')) {
      $form['field_lgms_last_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Last Name'),
        '#default_value' => $user->get('field_lgms_last_name')->value ?? '',
        '#required' => FALSE,
      ];
    }

    // Email
    $form['mail'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#default_value' => $user->getEmail(),
      '#required' => FALSE,
    ];

    // Phone Number
    if ($user->hasField('field_lgms_phone_number')) {
      $form['field_lgms_phone_number'] = [
        '#type' => 'tel',
        '#title' => $this->t('Phone Number'),
        '#default_value' => $user->get('field_lgms_phone_number')->value ?? '',
        '#required' => FALSE,
      ];
    }
  }

  protected function addUserPictureField(array &$form, $user): void {
    // Assuming 'field_lgms_user_picture' is your custom user image field.
    // Debugging line; use kint() or dpm() if Devel module is installed or check Drupal logs.
    \Drupal::logger('lgmsmodule')->notice('Current user picture target_id: @id', ['@id' => $user->field_lgms_user_picture->target_id]);


    $form['field_lgms_user_picture'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('User Picture'),
      '#upload_location' => 'public://profile_pictures/',
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg'],
      ],
      '#default_value' => $user->field_lgms_user_picture->target_id ? [$user->field_lgms_user_picture->target_id] : NULL,
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
   * @throws EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $user = $this->getCurrentUser();
    if (!$user) {
      return;
    }

    // Handle standard fields first, including the email update, assuming validation has passed.
    $fieldsToUpdate = [
      'field_lgms_first_name' => $form_state->getValue('field_lgms_first_name'),
      'field_lgms_last_name' => $form_state->getValue('field_lgms_last_name'),
      'field_lgms_phone_number' => $form_state->getValue('field_lgms_phone_number'),
      'mail' => $form_state->getValue('mail'), // Directly update as validation ensures uniqueness.
    ];

    foreach ($fieldsToUpdate as $fieldName => $value) {
      if ($user->hasField($fieldName)) {
        $user->set($fieldName, $value);
      }
    }

    $this->handleUserPicture($user, $form_state);
    $this->updateBlockConfiguration($form_state);
    $user->save();
    \Drupal::messenger()->addMessage($this->t('User information updated successfully.'));
  }


  /**
   * Validates the form before submission.
   *
   * @param array $form
   *   The form definition array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void
  {
    parent::validateForm($form, $form_state);

    // Validate the email field.
    $newEmail = $form_state->getValue('mail');
    $user = $this->getCurrentUser();

    // Check if the email is different from the current user's email and if it's already in use.
    if ($newEmail && $newEmail !== $user->getEmail() && $this->isEmailInUse($newEmail, $user->id())) {
      // Set an error on the 'mail' form element if the email is already in use.
      $form_state->setErrorByName('mail', $this->t('The email address is already in use.'));
    }
  }



  protected function updateUserFields(User $user, FormStateInterface $form_state): void {
    $fieldsToUpdate = ['field_lgms_first_name', 'field_lgms_last_name', 'field_lgms_phone_number'];
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
    $picture_fid = $form_state->getValue(['field_lgms_user_picture', 0]);
    if (!empty($picture_fid)) {
      $file = File::load($picture_fid);
      if ($file) {
        $file->setPermanent();
        $file->save();
        // Ensure file usage is recorded to prevent the file from being deleted.
        \Drupal::service('file.usage')->add($file, 'lgmsmodule', 'user', $user->id());
        $user->set('field_lgms_user_picture', $picture_fid);
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
