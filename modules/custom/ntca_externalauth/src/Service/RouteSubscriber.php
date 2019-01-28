<?php

namespace Drupal\ntca_externalauth\Service;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;
use Drupal\externalauth\ExternalAuth;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * NTCARDSN-111 disable certain user operations for non-admins, and certain operations for users who
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // user login goes to our custom form
    if ($route = $collection->get('user.login')) {
      $route->setDefault('_form', '\Drupal\ntca_externalauth\Form\UserLoginForm');
    }

    // reset password only allowed to full admins for now
    if ($route = $collection->get('user.pass')) {
//      $route->setRequirement('_permission', 'administer users');
      $route->setRequirement('_access', 'FALSE');
    }
//    if ($route = $collection->get('user.reset.form')) {
//      $route->setRequirement('_permission', 'administer users');
//    }

    // the Following are from /core/module/user/src/Entity/UserRouteProvider.php
    // Edit user
/*
    if ($route = $collection->get('entity.user.edit_form')) {
      $route->setRequirement('_permission', 'administer users');
      $route->
    }

    // View user
    if ($route = $collection->get('entity.user.canonical')) {
      $route->setRequirement('_permission', 'administer users');
    }

    // Delete account
    if ($route = $collection->get('entity.user.cancel_form')) {
      $route->setRequirement('_permission', 'administer users');
    }

    // There are also these operations, but they shouldn't be Role-based. Anybody who is not
    // user.reset - reset your password
    // user.reset.form
*/


  }
}