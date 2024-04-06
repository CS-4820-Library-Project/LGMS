<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

class lgmsmoduleSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array
  {
    return ['lgmsmodule.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string
  {
    return 'lgmsmodule_admin_settings';
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
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

    $field_config = \Drupal\field\Entity\FieldConfig::loadByName('node', 'guide_database_item', 'field_database_link');
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
