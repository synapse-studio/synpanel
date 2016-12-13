<?php

/**
 * @file
 * Builds placeholder replacement tokens for user-related data.
 */

use Drupal\Component\Utility\Html;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function synpanel_token_info() {
  $sp['piwik-id'] = [
    'name' => t('User role names'),
  ];
  $sp['piwik-key'] = [
    'name' => t('User role ids'),
  ];
  $sp['piwik-site'] = [
    'name' => t('User role ids'),
  ];
  $sp['piwik-uid'] = [
    'name' => t('User role ids'),
  ];
  $sp['site-id'] = [
    'name' => t('User role ids'),
  ];
  return [
    'tokens' => ['synpanel' => $sp],
  ];
}

/**
 * Implements hook_tokens().
 */
function synpanel_tokens($type, $tokens, array $data = [], array $options = [], BubbleableMetadata $bubbleable_metadata) {

  $replacements = [];
  $piwik_id   = false;
  $piwik_key  = false;
  $piwik_site = false;
  if ($type == 'synpanel') {
    $config = \Drupal::service('config.factory')->getEditable('synpanel.settings');
    foreach($_COOKIE as $key => $val){
      if(substr($key, 0, 6) == '_pk_id'){
        $piwik_id = $val;
        $piwik_key = strstr($piwik_id . '.', '.', true);
        $piwik_site = strstr(substr($key, 7, 5) . '_', '_', true);
        $config
          ->set('panel-piwik-siteid'  ,$piwik_site)
          ->save();
      }
    }
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'piwik-id':
          $replacements[$original] = $piwik_id ? $piwik_id : 'empty';
          break;
        case 'piwik-key':
          $replacements[$original] = $piwik_key ? $piwik_key : 'empty';
          break;
        case 'piwik-site':
          $replacements[$original] = $piwik_site ? $piwik_site : 'empty';
          break;
        case 'panel-uid':
          $replacements[$original] = $config->get('panel-uid');
          break;
        case 'site-id':
          $replacements[$original] = $config->get('panel-site');
          break;
      }
    }
  }
  return $replacements;
}
