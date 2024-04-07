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
 * Provides a 'LGMS Author Information' Block.
 *
 * This block displays the guide owner's contact information including their
 * name, email, phone number, profile picture, and any associated subjects.
 *
 * @Block(
 *   id = "lgms_guide_owner_block",
 *   admin_label = @Translation("LGMS Author Information"),
 *   category = @Translation("LGMS")
 * )
 */
class LgmsGuideOwnerBlock extends BlockBase {

  /**
   * Builds the block content.
   *
   * Fetches and displays author information based on the node context. The
   * displayed information includes name, profile picture, email, phone number,
   * and subjects associated with the guide's owner.
   *
   * @return array
   *   A renderable array representing the block content.
   */
  public function build(): array
  {
    $build = [];
    // Initialize an array to hold our elements
    $elements = [];

    $first_name = '';
    $last_name = '';

    // Get the node from the route
    $node = \Drupal::routeMatch()->getParameter('node');

    if (!$node) {
      return $build; // Early return if there's no node context.
    }

    // Fetch the node's author information.
    $author = $node->getOwner();


    // Get the first name if present
    if($author->hasField('field_lgms_first_name')){
      $first_name = $author->get('field_lgms_first_name')->value;
    }

    // Get the first name if present
    if($author->hasField('field_lgms_last_name')){
      $last_name = $author->get('field_lgms_last_name')->value;
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
      $subjectsMarkup = $this->fetchSubjectsMarkup($author);

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
      $elements['body'] = [
        'content' => [
          '#type' => 'container',
          '#attributes' => [
            'style' => 'margin-left: 16px;',
          ],
          'children' => [
            '#type' => 'processed_text',
            '#text' => $author->get('field_lgms_body')->value,
            '#format' => $author->get('field_lgms_body')->format,
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

    // Add the edit profile link for owner of the guide
    if (\Drupal::currentUser()->isAuthenticated() && \Drupal::currentUser()->id() == $author->id()) {
      $build['edit_profile_link'] = $this->addEditProfileLink();
    }

    $build['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $build['#attached']['library'][] = 'lgmsmodule/lgmsmodule';

    return $build;
  }

  /**
   * Fetches and constructs markup for the subjects associated with the user.
   *
   * @param User $owner
   *   The user entity whose subjects are to be fetched.
   *
   * @return string
   *   The constructed HTML markup containing the list of subjects.
   */
  private function fetchSubjectsMarkup($owner): string
  {
    // Fetch all of the user's selected subjects
    $subjects = $owner->get('field_lgms_user_subjects')->referencedEntities();
    $subjects_arr = [];

    // Add them to an array
    foreach ($subjects as $subject){
      $subjects_arr[] = $subject->label();
    }

    // Make them into a markup
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

  /**
   * Adds a link for users to edit their profile.
   *
   * This method generates a link to the user profile edit form. It's shown
   * to the owner of the guide, allowing them to directly access and edit their
   * profile information.
   *
   * @return array
   *   A render array for the edit profile link.
   */
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
   * Converts a phone number into a clickable link format.
   *
   * @param string $phone_number_raw
   *   The unformatted phone number string.
   * @param string $phone_number_formatted
   *   The phone number formatted for display.
   *
   * @return string
   *   A string containing an HTML anchor tag with the href set to a tel: URI.
   */
  protected function makePhoneNumberClickable($phone_number_raw, $phone_number_formatted): string
  {
    // Remove any non-numeric characters for the href attribute.
    $digits = preg_replace('/\D+/', '', $phone_number_raw);

    // Return a clickable link with the formatted phone number as the link text.
    return sprintf('<a href="tel:%s">%s</a>', $digits, $phone_number_formatted);
  }


  /**
   * Formats a raw phone number into a more readable format.
   *
   * @param string $phone_number_raw
   *   The unformatted phone number string.
   *
   * @return string
   *   The formatted phone number.
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

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge(): int
  {
    // Disable caching for this block.
    return 0;
  }

  /**
   * {@inheritdoc}
   */
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

  /**
   * {@inheritdoc}
   */
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

  /**
   * {@inheritdoc}
   */
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


