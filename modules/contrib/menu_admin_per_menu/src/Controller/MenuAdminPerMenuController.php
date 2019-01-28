<?php

/**
 * @file
 * Contains \Drupal\menu_admin_per_menu\Controller\MenuAdminPerMenuController.
 */

namespace Drupal\menu_admin_per_menu\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for page example routes.
 */
class MenuAdminPerMenuController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new MenuAdminPerMenu instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Constructs menus overview page.
   */
  public function menuOverviewPage() {
    $account = \Drupal::currentUser();
    $menu_table = $this->entityTypeManager->getListBuilder('menu')->render();
    if ($account->hasPermission('administer menu')) {
      return $menu_table;
    }
    $allowedMenusService = \Drupal::service('menu_admin_per_menu.allowed_menus');
    $allowed_menus = $allowedMenusService->getPerMenuPermissions($account);
    foreach ($menu_table['table']['#rows'] as $menu_key => $menu_item) {
      if (!isset($allowed_menus["administer $menu_key menu items"])) {
        unset($menu_table['table']['#rows'][$menu_key]);
      }
      else {
        $menu_row = &$menu_table['table']['#rows'][$menu_key];
        $menu_operations = &$menu_row['operations']['data']['#links'];
        $menu_operations['list']['title'] = t('List links');
        $menu_operations['list']['url'] = Url::fromRoute('entity.menu.edit_form', array('menu' => $menu_key));
        $menu_operations['add']['title'] = t('Add link');
        $menu_operations['add']['url'] = Url::fromRoute('entity.menu.add_link_form', array('menu' => $menu_key));
      }
    }
    return $menu_table;
  }

}
