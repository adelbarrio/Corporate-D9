<?php

namespace Drupal\search_and_replace\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class SarRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('scanner.admin_content')) {
      $route->setDefault('_form', '\Drupal\search_and_replace\Form\SarScannerForm');
    }
    if ($route = $collection->get('scanner.admin_confirm')) {
      $route->setDefault('_form', '\Drupal\search_and_replace\Form\SarScannerConfirmForm');
    }
  }

}
