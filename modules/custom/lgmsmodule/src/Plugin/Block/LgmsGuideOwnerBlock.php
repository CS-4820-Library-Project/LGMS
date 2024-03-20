<?php

namespace Drupal\lgmsmodule\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Component\Serialization\Json;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\Core\Form\FormStateInterface;

/**
 *
 *
 * @Block(
 *   id = "lgms_guide_owner_block",
 *   admin_label = @Translation("Contact information"),
 *   category = @Translation("LGMS"),
 * )
 */
class LgmsGuideOwnerBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array
  {
    $build = [];
    $node = \Drupal::routeMatch()->getParameter('node');
    $current_user = \Drupal::currentUser();

    // Fetch the author's information.
    $author = $node->getOwner();
    $first_name = '';
    $last_name = '';
    $phone_number_clickable = '';
    $email = $author->getEmail();

    // Check for first name, last name, and phone number fields and format accordingly.
    if($author->hasField('field_lgms_first_name')){
      $first_name = $author->get('field_lgms_first_name')->value;
      $last_name = $author->get('field_lgms_last_name')->value;
      $phone_number_raw = $author->get('field_lgms_phone_number')->value;

      // Initialize variables to avoid undefined variable errors.
      $phone_number_formatted = '';
      $phone_number_clickable = '';

      // Only format and make the phone number clickable if it's not empty.
      if (!empty($phone_number_raw)) {
        $phone_number_formatted = $this->formatPhoneNumber($phone_number_raw);
        $phone_number_clickable = $this->makePhoneNumberClickable($phone_number_raw, $phone_number_formatted);
      }
    }


    // Set the title using the first name.
    $build['#title'] = $first_name . "'s Contact Information";

    // Initialize the author_info container with profile picture if available.
    $build['author_info'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['author-info']],
    ];
    if ($this->configuration['show_user_picture'] && $author->hasField('field_lgms_user_picture') && !$author->get('field_lgms_user_picture')->isEmpty()) {
      $user_picture_file = $author->get('field_lgms_user_picture')->entity;
      if ($user_picture_file) {
        $user_picture_uri = $user_picture_file->getFileUri();
        $build['author_info']['picture'] = [
          '#theme' => 'image',
          '#uri' => $user_picture_uri,
          '#attributes' => ['alt' => $this->t("@name's profile picture", ['@name' => $author->getDisplayName()])],
        ];
      }
    }

    // Construct the author details markup.
    $author_details_markup = "<p><strong>Name:</strong> {$first_name} {$last_name}</p>";
    if ($this->configuration['show_email']) {
      $author_details_markup .= "<p><strong>Email:</strong> <a href='mailto:{$email}'>{$email}</a></p>";
    }
    if ($this->configuration['show_phone']) {
      $author_details_markup .= "<p><strong>Phone:</strong> {$phone_number_clickable}</p>";
    }

    // Add subjects to the author details if configured to show.
    if ($this->configuration['show_subjects']) {
      $subjectsMarkup = $this->fetchSubjectsMarkup($node);
      $author_details_markup .= $subjectsMarkup;
    }

    $build['author_info']['details'] = ['#markup' => $author_details_markup];

    // Include body content if configured.
    if (!empty($this->configuration['body']['value'])) {
      $build['body'] = [
        '#type' => 'processed_text',
        '#text' => $this->configuration['body']['value'],
        '#format' => $this->configuration['body']['format'],
      ];
    }

    // Provide a link to edit profile information for authenticated users.
    if ($current_user->isAuthenticated()) {
      $build += $this->addEditProfileLink();
    }

    $build['#attached']['library'][] = 'core/drupal.dialog.ajax';

    return $build;
  }

  private function fetchSubjectsMarkup($node): string
  {
    $subjects = [];
    // Fetch subjects based on node type and bundle.
    if ($node->bundle() === 'guide') {
      $subjects = $this->extractSubjects($node, 'field_lgms_guide_subject');
    } elseif ($node->bundle() === 'guide_page') {
      $parent_guide = $node->get('field_parent_guide')->entity;

      if($parent_guide->bundle() == 'guide_page')
        $parent_guide = $parent_guide->get('field_parent_guide')->entity;

      if ($parent_guide) {
        $subjects = $this->extractSubjects($parent_guide, 'field_lgms_guide_subject');
      }
    }

    $subjectsMarkup = '';
    if (!empty($subjects)) {
      $subjectsMarkup .= "<p><strong>Subjects:</strong></p><ul>";
      foreach ($subjects as $subject) {
        $subjectsMarkup .= "<li>{$subject}</li>";
      }
      $subjectsMarkup .= "</ul>";
    }
    return $subjectsMarkup;
  }

  private function extractSubjects($entity, $field_name): array
  {
    $subjects = [];
    if (!$entity->get($field_name)->isEmpty()) {
      foreach ($entity->get($field_name)->referencedEntities() as $term) {
        $subjects[] = $term->getName();
      }
    }
    return $subjects;
  }

  private function addEditProfileLink(): array
  {
    $current_url = \Drupal\Core\Url::fromRoute('<current>');
    $destination = $current_url->toString();
    $modal_form_url = \Drupal\Core\Url::fromRoute('lgmsmodule.owner_block_form', [], ['query' => ['destination' => $destination]]);
    return [
      'link_to_modal_form' => [
        '#type' => 'link',
        '#title' => $this->t('Edit Profile Information'),
        '#url' => $modal_form_url,
        '#attributes' => [
          'class' => ['use-ajax', 'button'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => \Drupal\Component\Serialization\Json::encode(['width' => 700]),
        ],
      ]
    ];
  }


  /**
   * Makes a phone number clickable for devices that support dialing.
   *
   * @param string $phone_number_raw The raw phone number.
   * @param string $phone_number_formatted The formatted phone number.
   * @return string The clickable phone number link.
   */
  protected function makePhoneNumberClickable($phone_number_raw, $phone_number_formatted): string
  {
    // Remove any non-numeric characters for the href attribute.
    $digits = preg_replace('/\D+/', '', $phone_number_raw);

    // Return a clickable link with the formatted phone number as the link text.
    return sprintf('<a href="tel:%s">%s</a>', $digits, $phone_number_formatted);
  }


  /**
   * Formats a raw phone number into the American format.
   *
   * @param string $phone_number_raw The raw phone number.
   * @return string The formatted phone number.
   */
  protected function formatPhoneNumber($phone_number_raw): string
  {
    \Drupal::logger('lgmsmodule')->notice('Phone number raw: @number', ['@number' => $phone_number_raw]);

    // Remove any non-numeric characters from the phone number.
    $digits = preg_replace('/\D+/', '', $phone_number_raw);

    // Format the digits into the American phone number format.
    if (strlen($digits) === 10) {
      return sprintf('(%s) %s-%s', substr($digits, 0, 3), substr($digits, 3, 3), substr($digits, 6));
    }

    // Return the original raw number if it doesn't have 10 digits.
    return $phone_number_raw;
  }

  public function getCacheMaxAge(): int
  {
    // Disable caching for this block.
    return 0;
  }

  public function blockForm($form, FormStateInterface $form_state): array
  {
    $form = parent::blockForm($form, $form_state);

    $form['show_user_picture'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show User Picture'),
      '#default_value' => $this->configuration['show_user_picture'],
    ];

    // Add a checkbox to toggle the visibility of the email address
    $form['show_email'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Email'),
      '#default_value' => $this->configuration['show_email'] ?? TRUE,
    ];

    // Add a checkbox to control the visibility of the phone number.
    $form['show_phone'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Phone Number'),
      '#default_value' => $this->configuration['show_phone'],
    ];

    $form['show_subjects'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Subjects'),
      '#default_value' => $this->configuration['show_subjects'],
    ];

    $form['body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Body'),
      '#default_value' => $this->configuration['body']['value'] ?? '',
      '#format' => $this->configuration['body']['format'] ?? 'basic_html',
      '#description' => $this->t('Add optional body content. This will be displayed in the block.'),
    ];

    return $form;
  }

  public function blockSubmit($form, FormStateInterface $form_state): void
  {
    parent::blockSubmit($form, $form_state);
    $this->configuration['show_user_picture'] = $form_state->getValue('show_user_picture');
    $this->configuration['show_email'] = $form_state->getValue('show_email');
    $this->configuration['show_phone'] = $form_state->getValue('show_phone');
    $this->configuration['show_subjects'] = $form_state->getValue('show_subjects');
    $this->configuration['body'] = $form_state->getValue('body');
  }

  public function defaultConfiguration(): array
  {
    return parent::defaultConfiguration() + [
        'show_email' => TRUE,
        'show_phone' => TRUE,
        'show_subjects' => TRUE,
        'show_user_picture' => TRUE,
        'body' => [
          'value' => '',
          'format' => 'basic_html',
        ],
      ];
  }
}


