<?php

namespace Drupal\synpanel\Form;

use \Drupal\Core\Form\ConfigFormBase;
use \Drupal\Core\Form\FormStateInterface;
use \Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements the form controller.
 */
class Settings extends ConfigFormBase {

  public function getFormId() {
    return 'synpanel_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['synpanel.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('synpanel.settings');
    $piwik_id = \Drupal::token()->replace('[synpanel:piwik-id]');
    if($piwik_id){
      $message = \Drupal::token()->replace('Piwik COOKIE: [synpanel:piwik-id] for [synpanel:site-id]');
      drupal_set_message($message);
    }else{
      drupal_set_message('No piwik COOKIE', 'warning');
    }
    //if($config->get('panel-piwik-siteid'))

    $access = true;
    if($config->get('panel-key')){
      $access = false;
    }
    // Access.
    $form['access'] = [
      '#type' => 'details',
      '#title' => $this->t('Access'),
      '#open' => $access,
    ];
    $form["access"]['panel-url'] = [
      '#title' => t("Url"),
      '#prefix' => '<br />',
      '#type' => 'textfield',
      '#default_value' => $config->get('panel-url'),
      '#description' => $this->t('Default url: <a
       href="https://www.biz-panel.com" target="_blank">www.biz-panel.com</a>'),
    ];
    $form["access"]['panel-uid'] = [
      '#title' => t("Uid / Email"),
      '#prefix' => '<br />',
      '#type' => 'textfield',
      '#default_value' => $config->get('panel-uid'),
      '#description' => $this->t('User id or email'),
    ];
    $form["access"]['panel-key'] = [
      '#title' => t("Access Key"),
      '#prefix' => '<br />',
      '#type' => 'textfield',
      '#default_value' => $config->get('panel-key'),
      '#description' => $this->t('REST access key'),
    ];
    // Settings.
    $form['settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Settings'),
      '#open' => true,
    ];
    $form["settings"]['panel-site'] = [
      '#title' => t("Site ID"),
      '#type' => 'textfield',
      '#default_value' => $config->get('panel-site'),
      '#description' => $this->t('ID from <a
        href="https://www.biz-panel.com" target="_blank">www.biz-panel.com</a>'),
    ];
    $default_source = str_replace(['http://', 'https://', 'www.'], '', $_SERVER['SERVER_NAME']);
    $form["settings"]['panel-source'] = [
      '#title' => t("Source"),
      '#type' => 'textfield',
      '#default_value' => $config->get('panel-source'),
      '#description' => $this->t('Default url: ') . '<strong>' . $default_source . '</strong>',
    ];
    // Export.
    $form['export'] = [
      '#type' => 'details',
      '#title' => $this->t('Export'),
      '#open' => true,
    ];
    $key = substr($config->get('panel-key'), 0, 15);
    $export = $default_source . '/synapse-biz-panel/export?key='.$key;
    $form["export"]['panel-export'] = [
      '#title' => t("Export"),
      '#type' => 'checkbox',
      '#default_value' => $config->get('panel-export'),
      '#description' => $this->t('Enable: ') . '<a href="http://'.$export.'" tagget="_blank">' . $export . '</a>',
    ];
    $form["export"]['panel-ipcontrol'] = [
      '#title' => t("IP limit"),
      '#type' => 'checkbox',
      '#default_value' => $config->get('panel-ipcontrol'),
      '#description' => $this->t('Limit access by ip'),
    ];
    $form["export"]['panel-ip'] = [
      '#title' => t("IP"),
      '#type' => 'textfield',
      '#default_value' => $config->get('panel-ip'),
      '#description' => $this->t('Comma separated IPs'),
    ];
    $form["export"]['panel-skip'] = [
      '#title' => t("Skip Forms"),
      '#type' => 'textfield',
      '#default_value' => $config->get('panel-skip'),
      '#description' => $this->t('Comma separated contact form ids'),
    ];
    return parent::buildForm($form, $form_state);
  }



  /**
   * Implements form validation.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * Implements a form submit handler.
   */
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('synpanel.settings');
    $source = str_replace(['http://', 'https://', 'www.'], '', $_SERVER['SERVER_NAME']);
    if($form_state->getValue('panel-source')){
      $source = $form_state->getValue('panel-source');
    }
    $url = 'https://www.biz-panel.com';
    if($form_state->getValue('panel-url')){
      $url = $form_state->getValue('panel-url');
    }
    $config
      ->set('panel-url'  ,$url)
      ->set('panel-uid'  ,$form_state->getValue('panel-uid'))
      ->set('panel-key'  ,$form_state->getValue('panel-key'))
      ->set('panel-site' ,$form_state->getValue('panel-site'))
      ->set('panel-source' ,$source)
      ->set('panel-export' ,$form_state->getValue('panel-export'))
      ->set('panel-ipcontrol' ,$form_state->getValue('panel-ipcontrol'))
      ->set('panel-ip'        ,$form_state->getValue('panel-ip'))
      ->set('panel-skip'      ,$form_state->getValue('panel-skip'))
      ->save();
  }

}
