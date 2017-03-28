<?php

namespace Drupal\synpanel\Controller;

/**
 * @file
 * Contains \Drupal\page_example\Controller\PageExampleController.
 */

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller routines for page example routes.
 */
class Export extends ControllerBase {

  /**
   * Constructs a simple page.
   */
  public function export($json = FALSE) {
    $config = \Drupal::service('config.factory')->getEditable('synpanel.settings');
    $base_url = str_replace(['http://', 'https://', 'www. '], '', $_SERVER['SERVER_NAME']);
    $data = [
      'code' => '0',
      'message' => 'export off',
      'description' => 'See ' . $base_url . '/admin/config/system/synpanel',
    ];
    $key = '';
    $skip_ip_limit = TRUE;
    if ($config->get('panel-ipcontrol')) {
      $skip_ip_limit = FALSE;
      $data = [
        'code' => '5',
        'message' => 'ip limit',
        'description' => 'See ' . $base_url . '/admin/config/system/synpanel',
      ];
      $ips = explode(',', $config->get('panel-ip'));
      foreach ($ips as $ip) {
        if ($_SERVER["REMOTE_ADDR"] == trim($ip)) {
          $skip_ip_limit = TRUE;
        }
      }
    }

    if ($config->get('panel-export') && isset($_GET['key']) && $skip_ip_limit) {
      if ($_GET['key'] == substr($config->get('panel-key'), 0, 15)) {
        $key = '?key=' . $_GET['key'];
        $from  = strtotime('first day of today -3 month');
        $limit = 300;

        if (isset($_GET['limit']) && is_numeric($_GET['limit'])) {
          $limit = $_GET['limit'];
        }
        if (isset($_GET['from'])) {
          $from = strtotime($_GET['from']);
        }

        $nids_skip = explode(',', $config->get('panel-skip'));
        $skip = [];
        foreach ($nids_skip as $nid) {
          $skip[] = trim($nid);
        }

        $piwik_site = $config->get('panel-piwik-siteid');
        $forms = [];

        // получаем данные по существующим формам.
        $formNids = \Drupal::entityQuery('contact_form')->execute();
        $entitys = entity_load_multiple('contact_form', $formNids);
        // dsm($entitys);
        $formArray = [];
        foreach ($entitys as $key => $value) {
          $formArray[$value->id()] = [
            'label' => $value->label(),
            'id' => $value->id(),
          ];
        }

        // Получаем данные по полученным сообщениям.
        $query = \Drupal::entityQuery('contact_message')->condition('created', $from, '>');
        $messageNids = $query->execute();
        $entitys = entity_load_multiple('contact_message', $messageNids);

        global $base_url;
        $default_source = str_replace(['http://', 'https://', 'www. '], '', $base_url);
        $source = $config->get('panel-source');
        if (!isset($source)) {
          $source = $default_source;
        }
        $messages = [];
        foreach ($entitys as $element) {
          $array = $element->toArray();
          $formID = isset($array['contact_form'][0]['target_id']) ? $array['contact_form'][0]['target_id'] : '';

          $fields = [];
          foreach ($array as $key => $value) {
            if (!(strripos($key, 'field') === FALSE)) {
              $fields[$key] = [
                'result' => isset($value[0]['value']) ? $value[0]['value'] : '',
                'lable' => $element->getFieldDefinition($key)->getLabel(),
              ];
            }
          }
          $date = isset($array['created'][0]['value']) ? $array['created'][0]['value'] : 0;
          $messages[$element->id()] = [
            'title' => isset($formArray[$formID]) ? $formArray[$formID]['label'] : '',
            'date' => $date,
            'human' => format_date($date, 'custom', 'd M Y, G:i'),
            'ip_address' => isset($array['ip_address'][0]['value']) ? $array['ip_address'][0]['value'] : '',
            'results' => $fields,

            'piwik-key' => isset($array['piwik'][0]) ? $array['piwik'][0]['value'] : '',
            'piwik-site' => isset($array['piwik-site']) ? $array['piwik-site'] : '',

            'nid' => $formID,
            'sid' => $element->id(),
            'source' => $source,

            'module' => 'contact_form',
          ];
        }
        // dsm($messages);
        $forms = array_reverse($messages);

        $data = [
          'code' => '1',
          'message' => 'ok',
          'description' => 'See ' . $base_url . '/admin/config/system/synpanel',
          'forms' => $forms,
        ];
      }
      else {
        $data = [
          'code' => '2',
          'message' => 'wrong key',
          'description' => 'See ' . $base_url . '/admin/config/system/synpanel',
        ];
      }
    }

    if ($json) {
      $response = new Response(json_encode($data, JSON_UNESCAPED_UNICODE));
      $response->headers->set('Content-Type', 'application/json');
      return $response;
    }
    else {
      $output = '<a href="/synapse-biz-panel/export/json' . $key . '">json here</a>';
      return array(
        '#markup' => '<p>' . $output . '</p>',
      );
    }
  }

}
