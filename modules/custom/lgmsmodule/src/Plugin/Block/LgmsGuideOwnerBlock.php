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
      // Fetch and display the guide's title.
      $build['#title'] = 'Contact Information';

      // Fetch the author's information.
      $author = $node->getOwner();
      $username = $author->getAccountName(); // Retrieve the username.
      $name = $author->getDisplayName();
      $email = $author->getEmail();

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

      // Prepare the markup for username, name, and email.
      $first_name = $author->get('field_first_name')->value;
      $last_name = $author->get('field_last_name')->value;

      $author_details_markup = "<p><strong>Name:</strong> {$first_name} {$last_name} ({$name})</p>";
      $author_details_markup .= "<p><strong>Email:</strong> <a href='mailto:{$email}'>{$email}</a></p>";

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


