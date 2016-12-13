<?php

/**
 * @file
 * Contains \Drupal\page_example\Controller\PageExampleController.
 */

namespace Drupal\synpanel\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller routines for page example routes.
 */
class Export extends ControllerBase {

  /**
   * Constructs a simple page.
   */
  public function export($json = false) {
    $config = \Drupal::service('config.factory')->getEditable('synpanel.settings');
    $base_url = str_replace(['http://', 'https://', 'www.'], '', $_SERVER['SERVER_NAME']);
    $data = [
      'code' => '0',
      'message' => 'export off',
      'description' => 'See '.$base_url.'/admin/config/system/synpanel',
    ];
    $key = '';
    $skip_ip_limit = true;
    if($config->get('panel-ipcontrol')){
      $skip_ip_limit = false;
      $data = [
        'code' => '5',
        'message' => 'ip limit',
        'description' => 'See '.$base_url.'/admin/config/system/synpanel',
      ];
      $ips = explode(',', $config->get('panel-ip'));
      foreach ($ips as $ip){
        if ($_SERVER["REMOTE_ADDR"] == trim($ip)){
          $skip_ip_limit = true;
        }
      }
    }

    if($config->get('panel-export') && isset($_GET['key']) && $skip_ip_limit){
      if ($_GET['key'] == substr($config->get('panel-key'), 0, 15)){
        $key = '?key='.$_GET['key'];
        $from  = strtotime('first day of today -3 month');
        $limit = 300;

        if (isset($_GET['limit']) && is_numeric($_GET['limit'])){
          $limit = $_GET['limit'];
        }
        if (isset($_GET['from'])){
           $from  = strtotime($_GET['from']);
          //  $from  = strtotime('today -3 weeks');
        }

        $nids_skip = explode(',', $config->get('panel-skip'));
        $skip = [];
        foreach ($nids_skip as $nid){
          $skip[] = trim($nid);
        }

        $piwik_site = $config->get('panel-piwik-siteid');
        $forms = [];

        // получаем данные по существующим формам
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

        // получаем данные по полученным сообщениям
        $query = \Drupal::entityQuery('contact_message')->condition('created', $from, '>');
        $messageNids = $query->execute();
        $entitys = entity_load_multiple('contact_message', $messageNids);

        global $base_url;
        $default_source = str_replace(['http://', 'https://', 'www.'], '', $base_url);
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
            if (!(strripos($key,'field') === false)) {
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

        // $entityTypes = entity_get_bundles();

        // $entity = entity_load('contact_message', 14)->getFieldDefinition('field_form_email')->getLabel();
        // $entity = $entitys[14]->toArray();


        /*
        $query = db_select('webform_submissions', 'ws');
        $query ->fields('ws', array('nid', 'sid', 'submitted', 'remote_addr'))
               ->addTag('webform_get_submissions_sids')
               ->condition('ws.submitted', $from, '>')
               ->condition('ws.nid', $skip, 'NOT IN')
               ->orderBy('ws.submitted', 'DESC')
               ->range(0, $limit);
        $result = $query->execute();
        foreach ($result as $row) {
          $node = node_load($row->nid);
          $submission = webform_get_submission($row->nid, $row->sid);
          $form = _synapse_panel_prepare($node, $submission);
          $form['piwik-key'] = $submission -> remote_addr;
          $form['site-id']    = variable_get("synapse_panel_site");
          $form['piwik-site'] = $piwik_site;
          $forms[] = $form;
          //dsm($submission);
        }*/

        $data = [
          'code' => '1',
          'message' => 'ok',
          'description' => 'See '.$base_url.'/admin/config/system/synpanel',
          'forms' => $forms,
        ];
      }else{
        $data = [
          'code' => '2',
          'message' => 'wrong key',
          'description' => 'See '.$base_url.'/admin/config/system/synpanel',
        ];
      }
    }

    if($json){
      $response = new \Symfony\Component\HttpFoundation\Response(json_encode($data, JSON_UNESCAPED_UNICODE));
      $response->headers->set('Content-Type', 'application/json');
      return $response;
    }else{
      $output = '<a href="/synapse-biz-panel/export/json'.$key.'">json here</a>';
      return array(
        '#markup' => '<p>' . $output . '</p>',
      );
    }
  }
}
