<?php
namespace Drupal\lgmsmodule\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class lgmsmoduleSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['lgmsmodule.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lgmsmodule_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save the configuration on form submission.
    $this->config('lgmsmodule.settings')
      ->set('proxy_prefix', $form_state->getValue('proxy_prefix'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
