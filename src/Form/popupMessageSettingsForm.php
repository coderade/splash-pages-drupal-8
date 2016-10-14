<?php

/**
 * @file
 * Contains \Drupal\popup_message\Form\popupMessageSettingsForm
 */
namespace Drupal\popup_message\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class popupMessageSettingsForm extends ConfigFormBase
{
    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'popup_message_admin_settings';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames()
    {
        return [
            'popup_message.settings',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = $this->config('popup_message.settings');

        $form = [];

        $form['popup_message_enable'] = array(
            '#type' => 'radios',
            '#title' => t('Enable Splash Page'),
            '#default_value' => $config->get('enable') ? $config->get('enable') : 0,
            '#options' => array(
                1 => t('Enabled'),
                0 => t('Disabled'),
            ),
        );

        $form['popup_message_fieldset'] = array(
            '#type' => 'fieldset',
            '#title' => t('Splash message settings'),
            '#collapsed' => FALSE,
            '#collapsible' => TRUE,
        );

        $form['popup_message_fieldset']['popup_message_title'] = array(
            '#type' => 'textfield',
            '#title' => t('Title'),
            '#default_value' => $config->get('title'),
        );

        $popup_message_body = $config->get('body');

        $form['popup_message_fieldset']['popup_message_body'] = array(
            '#type' => 'text_format',
            '#base_type' => 'textarea',
            '#title' => t('Message body'),
            '#default_value' => $popup_message_body['value'],
            '#format' => isset($popup_message_body['format']) ? $popup_message_body['format'] : NULL,
        );

        $form['popup_message_fieldset']['popup_message_url'] = array(
            '#type' => 'url',
            '#default_value' => $config->get('url'),
            '#maxlength' => 1024,
            '#title' => t('Link')
        );

        $form['popup_message_fieldset']['popup_message_target'] = array(
            '#type' => 'radios',
            '#title' => t('Target type: (External link)'),
            '#description' => t('Tar'),
            '#default_value' => $config->get('target') ? $config->get('target') : '_blank',
            '#options' => array(
                '_blank' => t('_blank'),
                '_self' => t('_self'),
            ),
        );
        $form['popup_message_fieldset']['popup_message_check_cookie'] = array(
            '#type' => 'radios',
            '#title' => t('Check cookie'),
            '#description' => t('If enabled message will be displayed only once per browser session'),
            '#default_value' => $config->get('check_cookie') ? $config->get('check_cookie') : 0,
            '#options' => array(
                1 => t('Enabled'),
                0 => t('Disabled'),
            ),
        );
        $form['popup_message_fieldset']['popup_message_delay'] = array(
            '#type' => 'textfield',
            '#title' => t('Delay'),
            '#description' => t('Splash will show after this number of seconds. Set to 0 to show instantly.'),
            '#default_value' => $config->get('delay') ? $config->get('delay') : 0,
        );

        // Styles.
        // Find styles in module directory.
        $directory = drupal_get_path('module', 'popup_message') . '/styles';
        $subdirectories = scandir($directory);
        $styles = array();
        $styles_type = [];

        foreach ($subdirectories as $subdir) {
            if (is_dir($directory . '/' . $subdir)) {
                if (file_exists($directory . '/' . $subdir . '/' . POPUP_MESSAGE_CSS_NAME)) {
                    $path = $directory . '/' . $subdir . '/' . POPUP_MESSAGE_CSS_NAME;
                    $lib_path = $subdir . '/' . POPUP_MESSAGE_CSS_NAME;
                    $styles[$lib_path] = $path;
                    $styles_type[$lib_path] = array('type' => 'module');
                }
            }
        }

        $form['popup_message_fieldset']['popup_message_style'] = array(
            '#type' => 'hidden',
            '#title' => t('Splash style'),
            '#default_value' => empty($config->get('style')) ? 0 : $config->get('style'),
            '#options' => $styles,
            '#description' => t('To add custom styles create directory and file "modules/popup_message/popup_message_styles/custom_style/popup.css" and set in this file custom CSS code.'),
        );

        $form['popup_message_fieldset']['visibility']['path'] = array(
            '#type' => 'hidden',
            '#title' => t('Pages'),
            '#collapsible' => TRUE,
            '#collapsed' => FALSE,
            '#group' => 'visibility',
            '#weight' => 0,
        );
        $options = array(
            POPUP_MESSAGE_VISIBILITY_NOTLISTED => t('All pages except those listed'),
            POPUP_MESSAGE_VISIBILITY_LISTED => t('Only the listed pages'),
        );
        $description = t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.", array(
                '%blog' => 'blog',
                '%blog-wildcard' => 'blog/*',
                '%front' => '<front>'
            )
        );

//    $title = t('Pages');
        $form['popup_message_fieldset']['visibility']['path']['popup_message_visibility'] = array(
            '#type' => 'hidden',
            '#title' => t('Show block on specific pages'),
            '#options' => $options,
            '#default_value' => $config->get('visibility') ? $config->get('visibility') : POPUP_MESSAGE_VISIBILITY_NOTLISTED,
        );

        $form['popup_message_fieldset']['visibility']['path']['popup_message_visibility_pages'] = array(
            '#type' => 'hidden',
            '#default_value' => $config->get('visibility_pages') ? $config->get('visibility_pages') : '',
            '#description' => $description,
//      '#title' => '<span class="element-invisible">' . $title . '</span>',
        );


        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {

        $config = \Drupal::configFactory()->getEditable('popup_message.settings');
        $flush_cache = ($config->get('style') == $form_state->getValue('popup_message_enable')) ? FALSE : TRUE;
        $flush_cache_css = ($config->get('style') == $form_state->getValue('popup_message_style')) ? FALSE : TRUE;
        $flush_cache_js = ($config->get('style') == $form_state->getValue('popup_message_check_cookie')) ? FALSE : TRUE;

        $config->set('enable', $form_state->getValue('popup_message_enable'))
            ->set('title', $form_state->getValue('popup_message_title'))
            ->set('body', $form_state->getValue('popup_message_body'))
            ->set('height', $form_state->getValue('popup_message_height'))
            ->set('width', $form_state->getValue('popup_message_width'))
            ->set('url', $form_state->getValue('popup_message_url'))
            ->set('target', $form_state->getValue('popup_message_target'))
            ->set('check_cookie', $form_state->getValue('popup_message_check_cookie'))
            ->set('delay', $form_state->getValue('popup_message_delay'))
            ->set('style', $form_state->getValue('popup_message_style'))
            ->set('visibility', $form_state->getValue('popup_message_visibility'))
            ->set('visibility_pages', $form_state->getValue('popup_message_visibility_pages'))
            ->save();

        if ($flush_cache) {
            drupal_flush_all_caches();
        }
        if ($flush_cache_css) {
            \Drupal::service('asset.css.collection_optimizer')->deleteAll();
        }
        if ($flush_cache_js) {
            \Drupal::service('asset.js.collection_optimizer')->deleteAll();
        }

        parent::submitForm($form, $form_state);
    }
}
