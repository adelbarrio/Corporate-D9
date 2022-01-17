<?php

namespace Drupal\translation_workflow;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Service for utils and manage tmgmt elements on html.
 */
class TmgmtElementsUtils {

  /**
   * Add tmgmt Elements to html value of fields.
   *
   * @param string $fieldValue
   *   Field value.
   *
   * @return string
   *   Text with tmgmt elements added.
   *
   * @throws \DOMException
   */
  public function addTmgmtElements(string $fieldValue) {
    $finalValue = '';
    $valueDocument = new \DOMDocument();
    $text = mb_convert_encoding('<body>' . $fieldValue . '</body>', 'HTML-ENTITIES', 'UTF-8');
    $valueDocument->loadHTML($text);
    $crawler = new Crawler($fieldValue);
    $tmgmtCounter = 1;
    $crawler->filter('body > *')->each(function (Crawler $node, $i) use ($valueDocument, &$tmgmtCounter, &$finalValue) {
      $oldDomNode = $node->getNode(0);
      $domNode = $valueDocument->createElement($oldDomNode->tagName);
      $domNode->textContent = $oldDomNode->textContent;
      if ($domNode->hasAttribute('id')) {
        $domNode->removeAttribute('id');
      }
      $domNode->setAttribute('id', 'tmgmt-' . $tmgmtCounter);
      $tmgmtCounter++;
      $finalValue .= $valueDocument->saveHTML($domNode);
    });
    return $finalValue;
  }

}
