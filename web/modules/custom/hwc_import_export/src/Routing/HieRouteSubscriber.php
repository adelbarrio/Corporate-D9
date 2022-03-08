<?php

namespace Drupal\hwc_import_export\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Alter the routes to check the access.
 */
class HieRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('entity.node.edit_form')) {
      $route->setRequirement('_custom_access', 'hwc_import_export.access_checker::access');
    }
  }

}
