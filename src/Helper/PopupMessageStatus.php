<?php
namespace Drupal\popup_message\Helper;

use Symfony\Component\HttpFoundation\Response;
use Drupal\Component\Utility\Unicode;
use Drupal\popup_message\EventSubscriber;

class PopupMessageStatus {

  public function check() {

    $config = \Drupal::configFactory()->get('popup_message.settings');
    // Get popup message visibility settings.
    $visibility = $config->get('visibility') ? $config->get('visibility') : POPUP_MESSAGE_VISIBILITY_NOTLISTED;

    // Get popup message visibility pages settings.
    $visibility_pages = $config->get('visibility_pages') ? $config->get('visibility_pages') : '';

    // Predefine value.
    $page_match = TRUE;

    // Limited visibility popup message must list at least one page.
    $status = TRUE;
    if ($visibility == POPUP_MESSAGE_VISIBILITY_LISTED && empty($visibility_pages)) {
      $status = FALSE;
    }

    // Match path if necessary.
    if ($visibility_pages && $status) {
      // Convert path to lowercase. This allows comparison of the same path
      // with different case. Ex: /Page, /page, /PAGE.
      $real_path = $_GET['popup_path'];
      if ($real_path == '/') {
        $real_path = \Drupal::configFactory()
          ->get('system.site')
          ->get('page.front');
      }
      else {
        $real_path = substr($real_path, 1);
      }
      $pages = Unicode::strtolower($visibility_pages);

      if ($visibility < POPUP_MESSAGE_VISIBILITY_MAX) {
        // Convert the Drupal path to lowercase.
        $path = Unicode::strtolower($real_path);
        // Compare the lowercase internal and lowercase path alias (if any).
        $page_match = \Drupal::service('path.matcher')
          ->matchPath($path, $pages);

        // When $visibility has a value of 0 (POPUP_MESSAGE_VISIBILITY_NOTLISTED),
        // the popup message is displayed on all pages except those listed in
        // $visibility_pages.
        // When set to 1 (POPUP_MESSAGE_VISIBILITY_LISTED), it is displayed only
        // on those
        // pages listed in $visibility_pages.
        $page_match = !($visibility xor $page_match);
      }
      else {
        $page_match = FALSE;
      }
    }

    // Allow show or not modal
    if ($page_match) {
      $show_popup = 1;
    }
    else {
      $show_popup = 0;
    }

    $response = new Response();
    $response->setContent(json_encode(array('status' => $show_popup)));
    $response->headers->set('Content-Type', 'application/json');
    return $response;
  }
}
