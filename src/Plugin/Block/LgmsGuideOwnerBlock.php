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
    if (!$node) {
      return $build; // Early return if there's no node context.
    }

    $current_user = \Drupal::currentUser();
    // Initialize an array to hold our elements
    $elements = [];

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

    // Full name
    $build['author_info']['author_name'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('<strong>Name:</strong> @first_name @last_name', [
        '@first_name' => $first_name,
        '@last_name' => $last_name
      ]),
      '#attributes' => [
        'style' => 'margin-left: 16px; margin-top: 8px;',
      ],
    ];

    // User picture
    if ($author->hasField('user_picture') && !$author->get('user_picture')->isEmpty()) {
      $user_picture_file = $author->get('user_picture')->entity;
      if ($user_picture_file && $this->configuration['show_user_picture']) {
        $user_picture_uri = $user_picture_file->getFileUri();
        $elements['user_picture'] = [
          'content' => [
            '#theme' => 'image',
            '#uri' => $user_picture_uri,
            '#attributes' => [
              'alt' => $this->t("@name's profile picture", ['@name' => $author->getDisplayName()]),
              'loading' => 'lazy',
              'style' => 'margin-left: 16px; margin-right: 16px; margin-bottom: 16px; border: 4px solid #fefefe; border-radius: 6px; box-shadow: 0 0 0 1px rgba(51,51,51,.2);',
            ],
          ],
          'position' => $this->configuration['position_user_picture'],
        ];

      }
    }

    // Email
    if ($this->configuration['show_email']) {
      $email = $author->getEmail();
      $elements['email'] = [
        'content' => [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $this->t('<strong>Email:</strong> <a href="mailto:@email">@email</a>', ['@email' => $email]),
          '#attributes' => ['style' => 'margin-left: 16px;'],
        ],
        'position' => $this->configuration['position_email'],
      ];
    }

    // Phone Number
    if ($author->hasField('field_lgms_phone_number') && !$author->get('field_lgms_phone_number')->isEmpty() && $this->configuration['show_phone']) {
      $phone_number_raw = $author->get('field_lgms_phone_number')->value;
      $phone_number_clickable = $this->makePhoneNumberClickable($phone_number_raw, $this->formatPhoneNumber($phone_number_raw));
      $elements['phone_number'] = [
        'content' => [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => "<strong>Phone:</strong> {$phone_number_clickable}",
          '#attributes' => [
            'style' => 'margin-left: 16px;',
          ],
        ],
        'position' => $this->configuration['position_phone_number'],
      ];
    }

    // Subjects
    if ($this->configuration['show_subjects']) {
      $subjectsMarkup = $this->fetchSubjectsMarkup($node);
      $elements['subjects'] = [
        'content' => [
          '#type' => 'container',
          '#attributes' => ['style' => 'margin-left: 16px;'],
          'children' => ['#markup' => $subjectsMarkup],
        ],
        'position' => $this->configuration['position_subjects'],
      ];
    }

    // Body
    if ($author->hasField('field_lgms_body') && !$author->get('field_lgms_body')->isEmpty() && $this->configuration['show_body']) {
      $body_value = $author->get('field_lgms_body')->value;
      $body_format = $author->get('field_lgms_body')->format;
      $elements['body'] = [
        'content' => [
          '#type' => 'container',
          '#attributes' => [
            'style' => 'margin-left: 16px;',
          ],
          'children' => [
            '#type' => 'processed_text',
            '#text' => $body_value,
            '#format' => $body_format,
          ],
        ],
        'position' => $this->configuration['position_body'],
      ];
    }


    // Sort the elements by their position
    uasort($elements, function ($a, $b) {
      return $a['position'] <=> $b['position'];
    });

    // Add sorted elements to the build array
    foreach ($elements as $key => $element) {
      $build[$key] = $element['content'];
    }

    // Add the edit profile link for authenticated users
    if ($current_user->isAuthenticated()) {
      $build['edit_profile_link'] = $this->addEditProfileLink();
    }

    $build['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $build['#attached']['library'][] = 'lgmsmodule/lgmsmodule';

    return $build;
  }

  private function fetchSubjectsMarkup($node): string
  {
    $owner = $node->getOwner();

    $subjects = $owner->get('field_lgms_user_subjects')->referencedEntities();
    $subjects_arr = [];

    foreach ($subjects as $subject){
      $subjects_arr[] = $subject->label();
    }


    $subjectsMarkup = '';
    if (!empty($subjects_arr)) {
      $subjectsMarkup .= "<p><strong>Subjects:</strong></p><ul>";
      foreach ($subjects_arr as $subject) {
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

  private function addEditProfileLink(): array {
    // Get the current user's ID.
    $user_id = \Drupal::currentUser()->id();

    // Get the current request URI to use as the destination after saving the profile.
    $destination = \Drupal::request()->getRequestUri();

    // Construct the URL to the user's edit page, including the destination parameter.
    $user_edit_url = \Drupal\Core\Url::fromRoute('entity.user.edit_form', ['user' => $user_id], ['query' => ['destination' => $destination]]);

    return [
      'link_to_user_edit' => [
        '#type' => 'link',
        '#title' => $this->t('Edit Your Profile'),
        '#url' => $user_edit_url,
        '#attributes' => [
          'class' => ['button'],
          'style' => 'margin-bottom: 10px;',
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

    // Define the number of positions based on the number of elements you have.
    $positions = range(1, 5); // Adjust the number 5 based on your actual number of elements.
    $position_options = array_combine($positions, $positions);

    $form['show_user_picture'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show User Picture'),
      '#default_value' => $this->configuration['show_user_picture'],
      '#description' => $this->t('Check this box to display the user’s profile picture.'),
    ];

    $form['show_email'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Email'),
      '#default_value' => $this->configuration['show_email'] ?? TRUE,
      '#description' => $this->t('Check this box to display the user’s email address.'),
    ];

    $form['show_phone'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Phone Number'),
      '#default_value' => $this->configuration['show_phone'],
      '#description' => $this->t('Check this box to display the user’s phone number.'),
    ];

    $form['show_subjects'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Subjects'),
      '#default_value' => $this->configuration['show_subjects'],
      '#description' => $this->t('Check this box to display the subjects associated with the content.'),
    ];

    $form['show_body'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Body'),
      '#default_value' => $this->configuration['show_body'],
      '#description' => $this->t('Check this box to display the body content from the user’s profile.'),
    ];

    // Position selectors
    $form['position_user_picture'] = [
      '#type' => 'select',
      '#title' => $this->t('Position for User Picture'),
      '#options' => $position_options,
      '#default_value' => $this->configuration['position_user_picture'] ?? 1,
    ];

    $form['position_email'] = [
      '#type' => 'select',
      '#title' => $this->t('Position for Email'),
      '#options' => $position_options,
      '#default_value' => $this->configuration['position_email'] ?? 2,
    ];

    $form['position_phone_number'] = [
      '#type' => 'select',
      '#title' => $this->t('Position for Phone Number'),
      '#options' => $position_options,
      '#default_value' => $this->configuration['position_phone_number'] ?? 3,
    ];

    $form['position_subjects'] = [
      '#type' => 'select',
      '#title' => $this->t('Position for Subjects'),
      '#options' => $position_options,
      '#default_value' => $this->configuration['position_subjects'] ?? 4,
    ];

    $form['position_body'] = [
      '#type' => 'select',
      '#title' => $this->t('Position for Body'),
      '#options' => $position_options,
      '#default_value' => $this->configuration['position_body'] ?? 5,
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
    $this->configuration['show_body'] = (bool) $form_state->getValue('show_body');

    // Save position settings
    $this->configuration['position_user_picture'] = $form_state->getValue('position_user_picture');
    $this->configuration['position_email'] = $form_state->getValue('position_email');
    $this->configuration['position_phone_number'] = $form_state->getValue('position_phone_number');
    $this->configuration['position_subjects'] = $form_state->getValue('position_subjects');
    $this->configuration['position_body'] = $form_state->getValue('position_body');
  }

  public function defaultConfiguration(): array
  {
    return parent::defaultConfiguration() + [
        'show_email' => TRUE,
        'show_phone' => TRUE,
        'show_subjects' => TRUE,
        'show_user_picture' => TRUE,
        'show_body' => TRUE,
        'position_user_picture' => 1,
        'position_email' => 2,
        'position_phone_number' => 3,
        'position_subjects' => 4,
        'position_body' => 5,
      ];
  }
}

