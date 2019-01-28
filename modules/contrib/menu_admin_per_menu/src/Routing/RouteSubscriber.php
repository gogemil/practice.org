<?php

/**
 * @file
 * Contains \Drupal\menu_admin_per_menu\Routing\RouteSubscriber.
 */

namespace Drupal\menu_admin_per_menu\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $routes = $collection->all();
    foreach ($routes as $route_name => $route) {
      switch ($route_name) {
        case 'entity.menu.collection':
          $route->setDefaults(['_controller' => '\Drupal\menu_admin_per_menu\Controller\MenuAdminPerMenuController::menuOverviewPage']);
          $route->setRequirements(['_custom_access' => '\Drupal\menu_admin_per_menu\Access\MenuAdminPerMenuAccess::menusOverviewAccess']);
          break;

        case 'entity.menu.edit_form':
        case 'entity.menu.add_link_form':
          $route->setRequirements(['_custom_access' => '\Drupal\menu_admin_per_menu\Access\MenuAdminPerMenuAccess::menuAccess']);
          break;

        case 'menu_ui.link_edit':
        case 'menu_ui.link_reset':
          $route->setRequirements(['_custom_access' => '\Drupal\menu_admin_per_menu\Access\MenuAdminPerMenuAccess::menuLinkAccess']);
          break;

        case 'entity.menu_link_content.canonical':
        case 'entity.menu_link_content.delete_form':
          $route->setRequirements(['_custom_access' => '\Drupal\menu_admin_per_menu\Access\MenuAdminPerMenuAccess::menuItemAccess']);
          break;

      }
    }
  }

}
