<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\Node;

/**
 * Configuration form for LGMS module settings.
 *
 * Provides a form interface for administrators to specify the proxy link prefix
 * applied to certain guide database items. Additionally, it updates existing database
 * item links with the new prefix and adjusts the field description accordingly to reflect
 * the change.
 */
class lgmsmoduleSettingsForm extends ConfigFormBase {

  /**
   * Returns a list of configuration names that should be editable.
   *
   * @return array An array of configuration object names that are editable.
   */
  protected function getEditableConfigNames(): array
  {
    return ['lgmsmodule.settings'];
  }

  /**
   * Gets the unique form ID for the settings form.
   *
   * @return string The form ID.
   */
  public function getFormId(): string
  {
    return 'lgmsmodule_admin_settings';
  }

  /**
   * Builds the settings form for proxy link prefix configuration.
   *
   * @param array $form The initial form array.
   * @param FormStateInterface $form_state The state of the form.
   *
   * @return array The form array with fields for setting the proxy link prefix.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array
  {
    $config = $this->config('lgmsmodule.settings');

    $form['proxy_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Proxy Link Prefix'),
      '#default_value' => $config->get('proxy_prefix'),
      '#description' => $this->t('Enter the prefix for the proxy links.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Submits the settings form.
   *
   * Upon form submission, updates the stored proxy link prefix in configuration,
   * adjusts all existing guide database items to use the new prefix, and updates
   * the field description to reflect the change.
   *
   * @param array &$form The form array.
   * @param FormStateInterface $form_state The state of the form.
   * @throws EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    // Get new and old prefix
    $proxy_prefix = $form_state->getValue('proxy_prefix');
    $old_proxy_prefix = \Drupal::config('lgmsmodule.settings')->get('proxy_prefix');

    // Save the configuration on form submission.
    $this->config('lgmsmodule.settings')
      ->set('proxy_prefix', $proxy_prefix)
      ->save();


    // Load all guide_database_item nodes
    $databases_ids = \Drupal::entityQuery('node')
      ->condition('type', 'guide_database_item')
      ->accessCheck(FALSE)
      ->execute();

    $nodes = Node::loadMultiple($databases_ids);

    foreach ($nodes as $node) {
      // Check if the field exists and is not empty
      if ($node->hasField('field_database_link') && !$node->get('field_database_link')->isEmpty()) {
        $current_value = $node->get('field_database_link')->uri;

        // if it has a proxy prefix
        if ($node->hasField('field_make_proxy') && $node->get('field_make_proxy')->value
          && str_starts_with($current_value, $old_proxy_prefix)) {
          // Remove old prefix
          $current_value = substr($current_value, strlen($old_proxy_prefix));

          // Attach new prefix
          $new_value = $proxy_prefix . $current_value;
          $node->set('field_database_link', ['uri' => $new_value, 'title' => $node->get('field_database_link')->title]);
          $node->save();
        }
      }
    }

    $field_config = FieldConfig::loadByName('node', 'guide_database_item', 'field_database_link');
    if ($field_config) {
      $new_description ='Enter the Title of the proxied content.
       All links in this field will have a proxy prefix attached
        to the url by the system (eg.'. $form_state->getValue('proxy_prefix').').
         So enter the URL of the content without the proxy.';

      $field_config->setDescription($new_description)->save();
    }

    parent::submitForm($form, $form_state);
  }
}
