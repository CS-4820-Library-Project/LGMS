<?php
/**
 * @file
 * Define the a single-column style plugin.
 */

// Plugin definition.
$plugin = array(
  'title' => t('DIY layout'),
  'category' => t('Rob Custom'),
  'icon' => 'onecol_clean.png',
  'theme' => 'diy_layout',
  'regions' => array(
	  'side' => t('Side'),
	  'content' => t('Content')

  ));

/**
 * Prepares variables for panels_onecol_clean templates.
 *
 * Default template: panels-onecol-clean.tpl.php.
 *
 * @param array $variables
 *   An associative array containing:
 *   - $content: An array of content, each item in the array is keyed to one
 *     panel of the layout.
 */
function template_preprocess_diy_layout(&$variables) {
  // Construct an ID/Class attributes.
  $variables['panel_classes'] = 'panel-display diy-layout clearfix';
  if ($variables['css_id']) {
    $variables['panel_id'] = ' id="' . $variables['css_id'] . '"';
    $variables['panel_classes'] .= ' ' . $variables['css_id'];
  }
  else {
    $variables['panel_id'] = '';
  }
}
