<?php
/**
 * @file
 * Custom setting for Vani theme.
 */

function vani_form_system_theme_settings_alter(&$form, &$form_state) {
  $image_vanipro = $GLOBALS['base_url'] . '/' . \Drupal::service('extension.list.theme')->getPath('vani') . '/images/vanipro.png';
  $vanipro = '<img src="'.$image_vanipro.'" alt="vanipro" />';
  $form['vani'] = [
    '#type'       => 'vertical_tabs',
    '#title'      => '<h3>' . t('Vani Theme Settings') . '</h3>',
    '#default_tab' => 'general',
  ];

  /**
   * Main Tabs.
   */

  // Main Tabs -> General.
  $form['general'] = [
    '#type'  => 'details',
    '#title' => t('General'),
    '#description' => t('<h3>Thank you for using Vani theme</h3>Vani is a free Drupal 8, 9 & 10 theme designed and developed by <a href="https://drupar.com" target="_blank">Drupar.com</a>'),
    '#group' => 'vani',
  ];

  // Main Tabs -> Color.
  $form['color'] = [
    '#type'  => 'details',
    '#title' => t('Theme Color'),
    '#group' => 'vani',
  ];

  // Main Tabs -> Social.
  $form['social'] = [
    '#type'  => 'details',
    '#title' => t('Social'),
    '#description' => t('Social icons settings. These icons appear in the footer.'),
    '#group' => 'vani',
  ];

  // Main Tabs -> Homepage Slider.
  $form['slider'] = [
    '#type'  => 'details',
    '#title' => t('Homepage Slider'),
    '#description' => t('Manage homepage slider.'),
    '#group' => 'vani',
  ];

  // Main Tabs -> Header.
  $form['header'] = [
    '#type'  => 'details',
    '#title' => t('Header'),
    '#group' => 'vani',
  ];

  // Main Tabs -> Sidebar.
  $form['sidebar'] = [
    '#type'  => 'details',
    '#title' => t('Sidebar'),
    '#group' => 'vani',
  ];

  // Main Tabs -> Content.
  $form['content'] = [
    '#type'  => 'details',
    '#title' => t('Content'),
    '#group' => 'vani',
  ];

  // Main Tabs -> Footer.
  $form['footer'] = [
    '#type'  => 'details',
    '#title' => t('Footer'),
    '#group' => 'vani',
  ];
  // Insert codes
  $form['insert_codes'] = [
    '#type'  => 'details',
    '#title' => t('Insert Codes'),
    '#group' => 'vani',
  ];
  // Main Tabs -> Support.
  $form['support'] = [
    '#type'  => 'details',
    '#title' => t('Support'),
    '#group' => 'vani',
  ];

  // Main Tabs -> Upgrade to VaniPro tab.
  $form['upgrade'] = [
    '#type'  => 'details',
    '#title' => t('Upgrade To VaniPro'),
    '#description'  => t('<h3>Upgrade To VaniPro For $19 only.</h3>'),
    '#group' => 'vani',
  ];

  // General -> info.
  $form['general']['general_info'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Theme Info'),
    '#description' => t('<a href="https://drupar.com/theme/vani" target="_blank">Theme Homepage</a> || <a href="https://demo2.drupar.com/vani/" target="_blank">Theme Demo</a> || <a href="https://drupar.com/vani-documentation" target="_blank">Theme Documentation</a> || <a href="https://drupar.com/support" target="_blank">Theme Support</a>'),
  ];

  $form['general']['general_info_upgrade'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Upgrade To VaniPro for $19 only'),
    '#description' => t('<a href="https://drupar.com/theme/vanipro" target="_blank">Purchase VaniPro</a> || <a href="https://demo2.drupar.com/vanipro/" target="_blank">VaniPro Demo</a>'),
  ];

  // Color -> Settings.
  $form['color']['theme_color'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Theme Color'),
    '#description'   => t('This feature is available in the premium version of this theme. <a href="https://drupar.com/theme/vanipro" target="_blank">Buy VaniPro for $19 only.</a>'),
  ];

  // Settings under social tab.
  // Show or hide all icons.
  $form['social']['social_profile'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Social Profile'),
  ];
  $form['social']['social_profile']['all_icons'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Show Social Icons'),
  ];

  $form['social']['social_profile']['all_icons']['all_icons_show'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Show social icons in footer'),
    '#default_value' => theme_get_setting('all_icons_show'),
    '#description'   => t("Check this option to show social icons in footer. Uncheck to hide."),
  ];

  // Facebook.
    $form['social']['social_profile']['facebook'] = [
    '#type'        => 'details',
    '#title'       => t("Facebook"),
  ];

  $form['social']['social_profile']['facebook']['facebook_url'] = [
    '#type'          => 'textfield',
    '#title'         => t('Facebook Url'),
    '#description'   => t("Enter yours facebook profile or page url. Leave the url field blank to hide this icon."),
    '#default_value' => theme_get_setting('facebook_url'),
  ];

  // Twitter.
  $form['social']['social_profile']['twitter'] = [
    '#type'        => 'details',
    '#title'       => t("Twitter"),
  ];

  $form['social']['social_profile']['twitter']['twitter_url'] = [
    '#type'          => 'textfield',
    '#title'         => t('Twitter Url'),
    '#description'   => t("Enter yours twitter page url. Leave the url field blank to hide this icon."),
    '#default_value' => theme_get_setting('twitter_url'),
  ];

  // Instagram.
  $form['social']['social_profile']['instagram'] = [
    '#type'        => 'details',
    '#title'       => t("Instagram"),
  ];

  $form['social']['social_profile']['instagram']['instagram_url'] = [
    '#type'          => 'textfield',
    '#title'         => t('Instagram Url'),
    '#description'   => t("Enter yours instagram page url. Leave the url field blank to hide this icon."),
    '#default_value' => theme_get_setting('instagram_url'),
  ];

  // Linkedin.
  $form['social']['social_profile']['linkedin'] = [
    '#type'        => 'details',
    '#title'       => t("Linkedin"),
  ];

  $form['social']['social_profile']['linkedin']['linkedin_url'] = [
    '#type'          => 'textfield',
    '#title'         => t('Linkedin Url'),
    '#description'   => t("Enter yours linkedin page url. Leave the url field blank to hide this icon."),
    '#default_value' => theme_get_setting('linkedin_url'),
  ];

  // YouTube.
  $form['social']['social_profile']['youtube'] = [
    '#type'        => 'details',
    '#title'       => t("YouTube"),
  ];

  $form['social']['social_profile']['youtube']['youtube_url'] = [
    '#type'          => 'textfield',
    '#title'         => t('YouTube Url'),
    '#description'   => t("Enter yours youtube.com page url. Leave the url field blank to hide this icon."),
    '#default_value' => theme_get_setting('youtube_url'),
  ];

  // YouTube.
  $form['social']['social_profile']['vimeo'] = [
    '#type'        => 'details',
    '#title'       => t("vimeo"),
  ];

  $form['social']['social_profile']['vimeo']['vimeo_url'] = [
    '#type'          => 'textfield',
    '#title'         => t('vimeo Url'),
    '#description'   => t("Enter yours vimeo.com page url. Leave the url field blank to hide this icon."),
    '#default_value' => theme_get_setting('vimeo_url'),
  ];

  // telegram.
    $form['social']['social_profile']['telegram'] = [
    '#type'        => 'details',
    '#title'       => t("Telegram"),
  ];

  $form['social']['social_profile']['telegram']['telegram_url'] = [
    '#type'          => 'textfield',
    '#title'         => t('Telegram Url'),
    '#description'   => t("Enter yours Telegram profile or page url. Leave the url field blank to hide this icon."),
    '#default_value' => theme_get_setting('telegram_url'),
  ];

  // WhatsApp.
    $form['social']['social_profile']['whatsapp'] = [
    '#type'        => 'details',
    '#title'       => t("WhatsApp"),
  ];

  $form['social']['social_profile']['whatsapp']['whatsapp_url'] = [
    '#type'          => 'textfield',
    '#title'         => t('WhatsApp Url'),
    '#description'   => t("Enter yours whatsapp message url. Leave the url field blank to hide this icon."),
    '#default_value' => theme_get_setting('whatsapp_url'),
  ];

  // Github.
    $form['social']['social_profile']['github'] = [
    '#type'        => 'details',
    '#title'       => t("GitHub"),
  ];

  $form['social']['social_profile']['github']['github_url'] = [
    '#type'          => 'textfield',
    '#title'         => t('GitHub Url'),
    '#description'   => t("Enter yours github page url. Leave the url field blank to hide this icon."),
    '#default_value' => theme_get_setting('github_url'),
  ];

  // Social -> vk.com url.
  $form['social']['social_profile']['vk'] = [
    '#type'        => 'details',
    '#title'       => t("vk.com"),
  ];
  $form['social']['social_profile']['vk']['vk_url'] = [
      '#type'          => 'textfield',
      '#title'         => t('vk.com'),
      '#description'   => t("Enter yours vk.com page url. Leave the url field blank to hide this icon."),
      '#default_value' => theme_get_setting('vk_url'),
  ];

  // Settings under slider tab.
  // Slider -> Show or hide slider
  $form['slider']['slider_enable_option'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Enable Slider'),
  ];

  $form['slider']['slider_enable_option']['slider_show'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Show Slider on Homepage'),
    '#default_value' => theme_get_setting('slider_show'),
    '#description'   => t("Check this option to show slider on homepage. Uncheck to hide."),
  ];

  /* Slider Image upload */
  $form['slider']['slider_image_section'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Slider Background Image'),
  ];
  $form['slider']['slider_image_section']['slider_image'] = [
    '#type'          => 'managed_file',
    '#upload_location' => 'public://',
    '#upload_validators' => array(
      'file_validate_extensions' => array('gif png jpg jpeg svg'),
    ),
    '#title'  => t('<p>Upload Homepage Slider Image</p>'),
    '#default_value'  => theme_get_setting('slider_image', 'vani'),
  ];
  $form['slider']['slider_code'] = [
    '#type'          => 'textarea',
    '#title'         => t('Slider Code'),
    '#default_value' => theme_get_setting('slider_code'),
    '#description'   => t('Please refer to this <a href="https://drupar.com/node/912/" target="_blank">documentation page</a> for slider code tutorial.'),
  ];

  // Settings under header tab.
  $form['header']['sticky_header'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Sticky Header'),
    '#description'   => t('This feature is available in the premium version of this theme. <a href="https://drupar.com/theme/vanipro" target="_blank">Buy VaniPro for $19 only.</a>'),
  ];

  $form['header']['circle'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Disable Animated Circles in Header'),
    '#description'   => t('This feature is available in the premium version of this theme. <a href="https://drupar.com/theme/vanipro" target="_blank">Buy VaniPro for $19 only.</a>'),
  ];

  // Settings under sidebar.
  $form['sidebar']['front_sidebars'] = [
    '#type'          => 'fieldset',
    '#title'         => t('Homepage Sidebar'),
  ];
  $form['sidebar']['front_sidebars']['front_sidebar'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Show Sidebars On Homepage'),
    '#default_value' => theme_get_setting('front_sidebar'),
    '#description'   => t('Check this option to enable left and right sidebar on homepage.'),
  ];
  $form['sidebar']['animated_sidebar'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Animated Sidebar'),
    '#description'   => t('This feature is available in the premium version of this theme. <a href="https://drupar.com/theme/vanipro" target="_blank">Buy VaniPro for $19 only.</a>'),
  ];
  /**
   * Content
   */
  $form['content']['content_tab'] = [
    '#type'  => 'vertical_tabs',
  ];
  // content -> Homepage  content
  $form['content_tab']['home_content'] = [
    '#type'        => 'details',
    '#title'       => t('Homepage content'),
    '#description' => t('Please follow this tutorial to add content on homepage. <a href="https://drupar.com/node/922" target="_blank">How to add content on homepage</a>'),
    '#group' => 'content_tab',
  ];
  // content -> Page loader
  $form['content_tab']['preloader'] = [
    '#type'        => 'details',
    '#title'       => t('Pre Page Loader'),
    '#description' => t('This feature is available in the premium version of this theme. <a href="https://drupar.com/theme/vanipro" target="_blank">Buy VaniPro for $19 only.</a>'),
    '#group' => 'content_tab',
  ];
  // content -> Animated Content
  $form['content_tab']['animated_content'] = [
    '#type'        => 'details',
    '#title'       => t('Animated Content'),
    '#description' => t('This feature is available in the premium version of this theme. <a href="https://drupar.com/theme/vanipro" target="_blank">Buy VaniPro for $19 only.</a>'),
    '#group' => 'content_tab',
  ];
  // content -> Google fonts
  $form['content_tab']['font_tab'] = [
    '#type'        => 'details',
    '#title'       => t('Google Fonts'),
    '#description' => t(''),
    '#group' => 'content_tab',
  ];
  // content -> Font icons
  $form['content_tab']['icon_tab'] = [
    '#type'        => 'details',
    '#title'       => t('Font Icon'),
    '#description' => t(''),
    '#group' => 'content_tab',
  ];
  // content -> shortcodes
  $form['content_tab']['shortcode'] = [
    '#type'        => 'details',
    '#title'       => t('Shortcodes'),
    '#description' => t('Vani theme has some custom shortcodes. You can create some styling content using these shortcodes.<br />Please visit this tutorial page for details. <a href="https://drupar.com/node/913" target="_blank">Shortcodes in Vani theme</a>.'),
    '#group' => 'content_tab',
  ];
  // content -> comment
  $form['content_tab']['comment'] = [
    '#type'        => 'details',
    '#title'       => t('Comment'),
    '#description' => t(''),
    '#group' => 'content_tab',
  ];
  // content -> node
  $form['content_tab']['node'] = [
    '#type'        => 'details',
    '#title'       => t('Node'),
    '#description' => t(''),
    '#group' => 'content_tab',
  ];
  // content -> share page
  $form['content_tab']['node_share'] = [
    '#type'        => 'details',
    '#title'       => t('Share Page'),
    '#description' => t('<h3>Share Page On Social Media</h3><p>This feature is available in the premium version of this theme. <a href="https://drupar.com/theme/vanipro" target="_blank">Buy VaniPro for $19 only.</a></p>'),
    '#group' => 'content_tab',
  ];
  // content -> comment -> user picture in comment
  $form['content_tab']['comment']['comment_section'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Comment'),
  ];
  $form['content_tab']['comment']['comment_section']['comment_user_pic'] = [
    '#type'          => 'checkbox',
    '#title'         => t('User Picture in comments'),
    '#default_value' => theme_get_setting('comment_user_pic'),
    '#description'   => t("Check this option to show user picture in comment. Uncheck to hide."),
  ];
  // content -> node -> Node author picture
  $form['content_tab']['node']['node_section'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Node'),
  ];

  $form['content_tab']['node']['node_section']['node_author_pic'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Node Author Picture'),
    '#default_value' => theme_get_setting('node_author_pic'),
    '#description'   => t("Check this option to show node author picture in submitted details. Uncheck to hide."),
  ];
  $form['content_tab']['node']['node_section']['node_tags'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Node Tags'),
    '#default_value' => theme_get_setting('node_tags'),
    '#description'   => t("Check this option to show node tags (if any) in submitted details. Uncheck to hide."),
  ];
  // content -> Google fonts -> Google fonts options
  $form['content_tab']['font_tab']['font_section'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Google Fonts'),
  ];
  $form['content_tab']['font_tab']['font_section']['google_font'] = [
    '#type'          => 'select',
    '#title'         => t('Select Google Fonts Location'),
    '#options' => array(
    	'local' => t('Local Self Hosted'),
      'googlecdn' => t('Google CDN Server')
    ),
    '#default_value' => theme_get_setting('google_font'),
    '#description'   => t('Vani theme uses following Google fonts: Open Sans and Roboto. You can serve these fonts locally or from Google server.'),
  ];
  // content -> Font icons -> FontAwesome 4
  $form['content_tab']['icon_tab']['fontawesome4'] = [
    '#type'        => 'fieldset',
    '#title'       => t('FontAwesome 4'),
  ];
  $form['content_tab']['icon_tab']['fontawesome4']['font_icon_fontawesome'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Font Awesome 4'),
    '#default_value' => theme_get_setting('font_icon_fontawesome'),
    '#description'   => t('Check this option to enable FontAwesome 4 font icons. Uncheck to disable.<br /><br />vani theme has included FontAwesome v4.7.0 font icons. You can use any FontAwesome font icon with vani theme.<br />Please visit this tutorial page for details. <a href="https://drupar.com/custom-shortcodes-set-two/fontawesome-font-icons" target="_blank">How To Use FontAwesome Font Icons</a>'),
  ];
  // content -> Font icons -> FontAwesome 5
  $form['content_tab']['icon_tab']['fontawesome5'] = [
    '#type'        => 'fieldset',
    '#title'       => t('FontAwesome 5'),
  ];
  $form['content_tab']['icon_tab']['fontawesome5']['font_icon_fontawesome5'] = [
    '#type'          => 'checkbox',
    '#disabled'   => TRUE,
    '#title'         => t('Font Awesome 5'),
    '#default_value' => theme_get_setting('font_icon_fontawesome5'),
    '#description'   => t('<p>This feature is available in the premium version of this theme. <a href="https://drupar.com/theme/vanipro" target="_blank">Buy VaniPro for $19 only.</a></p>'),
  ];
  // content -> Font icons -> FontAwesome 6
  $form['content_tab']['icon_tab']['fontawesome6'] = [
    '#type'        => 'fieldset',
    '#title'       => t('FontAwesome 6'),
  ];
  $form['content_tab']['icon_tab']['fontawesome6']['font_icon_fontawesome6'] = [
    '#type'          => 'checkbox',
    '#disabled'   => TRUE,
    '#title'         => t('Font Awesome 6'),
    '#default_value' => theme_get_setting('font_icon_fontawesome6'),
    '#description'   => t('<p>This feature is available in the premium version of this theme. <a href="https://drupar.com/theme/vanipro" target="_blank">Buy VaniPro for $19 only.</a></p>'),
  ];
  // content -> Font icons -> Bootstrap Font Icons
  $form['content_tab']['icon_tab']['bootstrap_icons'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Bootstrap Font Icons'),
  ];
  $form['content_tab']['icon_tab']['bootstrap_icons']['bootstrapicons'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Enable Bootstrap Icons'),
    '#default_value' => theme_get_setting('bootstrapicons'),
    '#description'   => t('Check this option to enable Bootstrap Font Icons. Read more about <a href="https://icons.getbootstrap.com/" target="_blank">Bootstrap Icons</a>'),
  ];
  // Settings under content tab -> Font Icons -> Google Material Icons
  $form['content_tab']['icon_tab']['material_icons'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Google Material Font Icons'),
  ];
  $form['content_tab']['icon_tab']['material_icons']['font_icon_material'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Google Material Font Icons'),
    '#default_value' => theme_get_setting('font_icon_material'),
    '#description'   => t('Check this option to enable Google Material font icons. Uncheck to disable.<br /><br /><br />Vani theme has included Google material font icons. You can use any Google material icon with Vani theme.<br />Please visit this tutorial page for details. <a href="https://drupar.com/node/934" target="_blank">How To Use Google Material Font Icons</a>.'),
  ];
  $form['social']['node_share'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Share Page On Social networking websites'),
    '#description'   => t('This feature is available in the premium version of this theme. <a href="https://drupar.com/theme/vanipro" target="_blank">Buy VaniPro for $19 only.</a>'),
  ];

  // Settings under footer tab.
  // Scroll to top.
  $form['footer']['scrolltotop'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Scroll To Top'),
  ];

  $form['footer']['scrolltotop']['scrolltotop_on'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Enable scroll to top feature.'),
    '#default_value' => theme_get_setting('scrolltotop_on'),
    '#description'   => t("Check this option to enable scroll to top feature. Uncheck to disable this fearure and hide scroll to top icon."),
  ];

  // Footer -> Copyright.
  $form['footer']['copyright'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Website Copyright Text'),
  ];

  $form['footer']['copyright']['copyright_text'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Show website copyright text in footer.'),
    '#default_value' => theme_get_setting('copyright_text'),
    '#description'   => t("Check this option to show website copyright text in footer. Uncheck to hide."),
  ];

  // Footer -> Copyright -> custom copyright text
  $form['footer']['copyright']['copyright_text_custom'] = [
    '#type'          => 'fieldset',
    '#title'         => t('Custom copyright text'),
    '#description'   => t('This feature is available in the premium version of this theme. <a href="https://drupar.com/theme/vanipro" target="_blank">Buy VaniPro for $19 only.</a>'),
  ];

  // Footer -> Cookie message.
  $form['footer']['cookie'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Cookie Consent message'),
    '#description'   => t('This feature is available in the premium version of this theme. <a href="https://drupar.com/theme/vanipro" target="_blank">Buy VaniPro for $19 only.</a>'),
  ];

  $form['footer']['cookie']['cookie_message'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Show Cookie Consent Message'),
    '#description'   => t('Make your website EU Cookie Law Compliant. According to EU cookies law, websites need to get consent from visitors to store or retrieve cookies.'),
  ];

  /**
   * Insert Codes
   */
  $form['insert_codes']['insert_codes_tab'] = [
    '#type'  => 'vertical_tabs',
  ];
  // Insert Codes -> Head
  $form['insert_codes']['head'] = [
    '#type'        => 'details',
    '#title'       => t('Head'),
    '#description' => t('<h3>Insert Codes Before &lt;/HEAD&gt;</h3><hr />'),
    '#group' => 'insert_codes_tab',
  ];
  // Insert Codes -> Body
  $form['insert_codes']['body'] = [
    '#type'        => 'details',
    '#title'       => t('Body'),
    '#group' => 'insert_codes_tab',
  ];
  // Insert Codes -> CSS
  $form['insert_codes']['css'] = [
    '#type'        => 'details',
    '#title'       => t('CSS Codes'),
    '#group'       => 'insert_codes_tab',
  ];
  // Insert Codes -> Head -> Head codes
  $form['insert_codes']['head']['insert_head'] = [
    '#type'          => 'fieldset',
    '#description'   => t('This feature is available in the premium version of this theme. <a href="https://drupar.com/theme/vanipro" target="_blank">Buy VaniPro for $19 only.</a>'),
  ];
  // Insert Codes -> Body -> Body start codes
  $form['insert_codes']['body']['insert_body_start_section'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Insert code after &lt;BODY&gt; tag'),
    '#description'   => t('This feature is available in the premium version of this theme. <a href="https://drupar.com/theme/vanipro" target="_blank">Buy VaniPro for $19 only.</a>'),
  ];
  // Insert Codes -> Body -> Body ENd codes
  $form['insert_codes']['body']['insert_body_end_section'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Insert code before &lt;/BODY&gt; tag'),
    '#description'   => t('This feature is available in the premium version of this theme. <a href="https://drupar.com/theme/vanipro" target="_blank">Buy VaniPro for $19 only.</a>'),
  ];
  $form['insert_codes']['css']['custom'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Custom Styling'),
  ];

  $form['insert_codes']['css']['custom']['styling'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Enable custom css'),
    '#default_value' => theme_get_setting('styling'),
    '#description'   => t("Check this option to enable custom styling. Uncheck to disable this fearure.<br />Please refer to this tutorial page. <a href='https://drupar.com/vani-documentation/custom-css' target='_blank'>How To Use Custom Styling</a>"),
  ];

  $form['insert_codes']['css']['custom']['styling_code'] = [
    '#type'          => 'textarea',
    '#title'         => t('Custom CSS Codes'),
    '#default_value' => theme_get_setting('styling_code'),
    '#description'   => t('Please enter your custom css codes in this text box. You can use it to customize the appearance of your site.<br />Please refer to this tutorial for detail: <a href="https://drupar.com/vani-documentation/custom-css" target="_blank">Custom CSS</a>'),
  ];

  // Settings under support tab.
  $form['support']['info'] = [
    '#type'        => 'fieldset',
    '#description' => t('<h4>Documentation</h4>
    <p>We have a detailed documentation about how to use theme. Please read the <a href="https://drupar.com/vani-documentation" target="_blank">Vani Theme Documentation</a>.</p>
    <hr />
    <h4>Open An Issue</h4>
    <p>If you need support that is beyond our theme documentation, please <a href="https://www.drupal.org/project/issues/vani?categories=All" target="_blank">open an issue</a> at project page.</p>
    <hr />
    <h4>Contact Us</h4>
    <p>If you need some specific customization in theme, please contact us<br><a href="https://drupar.com/contact" target="_blank">drupar.com/contact</a></p>'),
  ];

  // Settings under upgrade tab.
  $form['upgrade']['info'] = [
    '#type'        => 'fieldset',
    '#title'       => t('<a href="https://demo2.drupar.com/vanipro/" target="_blank">VaniPro Demo</a> | <a href="https://drupar.com/theme/vanipro" target="_blank">Purchase VaniPro for $19 only</a>'),
    '#description' => t("$vanipro<br /><p><a href='https://demo2.drupar.com/vanipro/' target='_blank'>VaniPro Demo</a> | <a href='https://drupar.com/theme/vanipro' target='_blank'>Purchase VaniPro for $19 only</a></p>"),
  ];
// End form.
}
