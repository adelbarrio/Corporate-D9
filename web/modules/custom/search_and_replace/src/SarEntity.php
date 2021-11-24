<?php

namespace Drupal\search_and_replace;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * General class for entity hooks.
 */
class SarEntity {

  use StringTranslationTrait;

  /**
   * Alter the local tasks.
   *
   * @see \hook_local_tasks_alter()
   */
  public function localTaskAlter(&$local_tasks) {
    $local_tasks['scanner.admin_content']['title'] = $this->t('Search and Replace');
    $local_tasks['scanner.admin']['title'] = $this->t('Search');
  }

}
