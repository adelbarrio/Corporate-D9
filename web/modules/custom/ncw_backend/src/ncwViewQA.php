<?php

namespace Drupal\ncw_backend;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Menu\MenuLinkManager;
use Drupal\menu_link_content\Plugin\migrate\source\MenuLink;
use Drupal\node\Entity\Node;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeInterface;

/**
 * This class is a view query alter
 */
class ncwViewQA implements ContainerInjectionInterface {
  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   *
   */
  protected $routeMatch;

  /**
   * The entity type manager.
   *
   * @var  \Drupal\Core\Menu\MenuLinkManager
   *
   */
  protected $menuLinkManager;

  /**
   * {@inheritdoc}
  */

  public function __construct(RouteMatchInterface $route_match, MenuLinkManager $menu_link_manager){
    $this->routeMatch = $route_match;
    $this->menuLinkManager = $menu_link_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {

    return new static(
      $container->get('current_route_match'),
      $container->get('plugin.manager.menu.link')
    );
  }

  /**
   * Load only the content menus from each landing page
   */
  public function viewsQueryAlter(ViewExecutable $view, QueryPluginBase $query){
    if ($view->storage->get('id') === 'landing_menu') {
      /** @var \Drupal\menu_item_extras\Entity\MenuItemExtrasMenuLinkContent $menu */

      $node = $this->routeMatch->getParameter('node');
      if ($node instanceof NodeInterface) {
        // You can get nid and anything else you need from the node object.
        $node_id = $node->id();

        $result = $this->menuLinkManager->loadLinksByRoute('entity.node.canonical', array('node' => $node_id));

        $parentFinal =key(array_slice($result, -1, 1, true));
        $query->where[0]['conditions'][0]["value"] = $parentFinal;
      }

    }

    //@ ========================================================================
    //@ Filter by View ID and Display ID.
    //@ If we are in the target view, extract related node id's and exclude from
    //@ the query.
    //@ ========================================================================
    $viewId = $view->storage->get('id');
    $displayId = $view->current_display ?? "";
    if ($viewId === "related_content" && $displayId === "block_4") {
      if ($node = $this->routeMatch->getParameter('node')) {
        $tids = [];
        foreach ($node->field_related_publications as $related) {
          $tids[] = $related->target_id;
        }
        $query->addWhere('conditions', 'node_field_data.nid', $tids, 'NOT IN');
      }
    }

  }

}
