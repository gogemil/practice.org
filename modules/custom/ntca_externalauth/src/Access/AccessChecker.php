<?php

namespace Drupal\ntca_externalauth\Access;

use Drupal\ntca_externalauth\Service\RemoteUserManagerService;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

class AccessChecker implements AccessInterface {
  protected $nonAccessiblePaths = array(
    '/user/{user}/edit',
    '/user/{user}'
  );

  public function access(AccountInterface $account, Route $route) {

    // note: we could pass a user id in there, look up the database
    // shortcut method: use the cookie check
    $bIsExternalUser = RemoteUserManagerService::isCurrentUserARemoteUser();

    $sRoutePath = $route->getPath();
    if (in_array($sRoutePath, $this->nonAccessiblePaths) && $bIsExternalUser) {
      return(new Access\AccessResultForbidden());
    }

    // as far as we're concerned, it's good, something else might block it later
    return(new Access\AccessResultAllowed());

  }
}