<?php

namespace Drupal\hwc_import_export\Plugin\views\style;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\rest\Plugin\views\style\Serializer;

/**
 * The style plugin for serialized output formats.
 *
 * Add and extra row to the output.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "hwc_serializer",
 *   title = @Translation("HWC Type Serialization"),
 *   help = @Translation("Serializes views row data using the Serializer component."),
 *   display_types = {"data"}
 * )
 */
class HwcRestStyle extends Serializer implements CacheableDependencyInterface{

  /**
   * {@inheritdoc}
   */
  public function render() {
    $rows = [];

    foreach ($this->view->result as $row_index => $row) {
      $this->view->row_index = $row_index;
      $rows[] = ['item' => $this->view->rowPlugin->render($row)];
    }

    unset($this->view->row_index);
    // Get the content type configured in the display or fallback to the
    // default.
    if ((empty($this->view->live_preview))) {
      $content_type = $this->displayHandler->getContentType();
    }
    else {
      $content_type = !empty($this->options['formats']) ? reset($this->options['formats']) : 'json';
    }

    $result = [
      'items' => $rows
    ];

    return $this->serializer->serialize($result, $content_type, ['views_style_plugin' => $this]);
  }

}
