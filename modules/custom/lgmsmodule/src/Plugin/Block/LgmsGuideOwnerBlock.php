<?php

namespace Drupal\lgmsmodule\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Database;
use Drupal\lgmsmodule\sql\sqlMethods;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Component\Serialization\Json;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

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
  public function build() {
    $build = [];
    $node = \Drupal::routeMatch()->getParameter('node');

    if ($node instanceof Node && $node->bundle() === 'guide') {

      // Fetch the author's information.
      $author = $node->getOwner();
      $name = $author->getDisplayName();
      $email = $author->getEmail();

      // Prepare the markup for name, email and phone number.
      $first_name = '';
      $last_name = '';
      $phone_number_clickable = '';

      if($author->hasField('field_first_name')){
        $first_name = $author->get('field_first_name')->value;
        $last_name = $author->get('field_last_name')->value;
        $phone_number_raw = $author->get('field_phone_number')->value;
        $phone_number_formatted = $this->formatPhoneNumber($phone_number_raw);
        $phone_number_clickable = $this->makePhoneNumberClickable($phone_number_raw, $phone_number_formatted);
      }

      // Fetch and display the guide's title.
      $build['#title'] = $first_name . "'s Contact Information";

      // Attempt to load the profile picture.
      $user_picture = '';
      if ($author->user_picture && !$author->user_picture->isEmpty()) {
        $user_picture = $author->user_picture->entity->getFileUri();
      }

      // Initialize the author_info container.
      $build['author_info'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['author-info']],
      ];

      // Add the profile picture to the author_info container if available.
      if ($user_picture) {
        $build['author_info']['picture'] = [
          '#theme' => 'image',
          '#uri' => $user_picture,
          '#attributes' => ['alt' => $this->t("@name's profile picture", ['@name' => $name])],
          '#style' => ['width' => '100px'], // Example to control size, adjust as needed.
        ];
      }

      $author_details_markup = "<p><strong>Name:</strong> {$first_name} {$last_name} ({$name})</p>";
      $author_details_markup .= "<p><strong>Email:</strong> <a href='mailto:{$email}'>{$email}</a></p>";
      $author_details_markup .= "<p><strong>Phone:</strong> {$phone_number_clickable}</p>";

      // Fetch and prepare subjects.
      $subjects = [];
      if (!$node->get('field_lgms_guide_subject')->isEmpty()) {
        foreach ($node->get('field_lgms_guide_subject')->referencedEntities() as $term) {
          $subjects[] = $term->getName();
        }
      }

      // Add subjects to the markup if available.
      if (!empty($subjects)) {
        $author_details_markup .= "<p><strong>Subjects:</strong></p><ul>";
        foreach ($subjects as $subject) {
          $author_details_markup .= "<li>{$subject}</li>";
        }
        $author_details_markup .= "</ul>";
      }

      // Include the combined markup and subjects in the build.
      $build['author_info']['details'] = [
        '#markup' => $author_details_markup,
      ];
    }

    return $build;
  }

  /**
   * Makes a phone number clickable for devices that support dialing.
   *
   * @param string $phone_number_raw The raw phone number.
   * @param string $phone_number_formatted The formatted phone number.
   * @return string The clickable phone number link.
   */
  protected function makePhoneNumberClickable($phone_number_raw, $phone_number_formatted) {
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
  protected function formatPhoneNumber($phone_number_raw) {
    // Remove any non-numeric characters from the phone number.
    $digits = preg_replace('/\D+/', '', $phone_number_raw);

    // Format the digits into the American phone number format.
    if (strlen($digits) === 10) {
      return sprintf('(%s) %s-%s', substr($digits, 0, 3), substr($digits, 3, 3), substr($digits, 6));
    }

    // Return the original raw number if it doesn't have 10 digits.
    return $phone_number_raw;
  }



  public function getCurrentGuideId()
  {
    $current_node = \Drupal::routeMatch()->getParameter('node');
    if ($current_node->getType() == 'guide') {
      return $current_node->id();
    }
    elseif ($current_node->getType() == 'guide_page') {

      $sqlMethods = new sqlMethods(\Drupal::database());
      return $sqlMethods->getGuideNodeIdByPageId($current_node->id());

    }
    return NULL;
  }
  public function getCacheMaxAge() {
    // Disable caching for this block.
    return 0;
  }
}


