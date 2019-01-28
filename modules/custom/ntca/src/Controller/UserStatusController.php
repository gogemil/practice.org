<?php

namespace Drupal\ntca\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Drupal\ntca_acgi_api\ACGICredManager;

class UserStatusController extends ControllerBase {

  /**
   * Returns a tiny smidgeon of HTML needed to render the Hi! (welcome) message.
   * Normally in a twig, but that's a heavier solution than just coding in the controller.
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function loginStatusBar() {
    $oResponse = new Response();
    $oResponse->isCacheable(false);

    $oCurrentSession = $this->currentUser();
    $iCurrentSessionUserId = $oCurrentSession->id();
    if ($iCurrentSessionUserId == 0) { // anonymous
      return($oResponse);
    }
    $oUser = \Drupal\user\Entity\User::load($iCurrentSessionUserId);

    $oFirstName = $oUser->get("field_first_name");
    $aFirstNameValues = $oFirstName->getValue();
    $sFirstNameHTMLPiece = "";
    if (isset($aFirstNameValues[0]["value"])) {
      $sFirstNameHTMLPiece = ", ".$aFirstNameValues[0]["value"];
    }

    $oCredManager = new ACGICredManager();
    $sLoginUrl = $oCredManager->getCred(ACGICredManager::CRED_ID_LOGINJUMPURL);

    $sMarkup = <<<EOHTML
<div class="userinfo">
    Hi $sFirstNameHTMLPiece! <a href="$sLoginUrl">Go to Member Central</a>
</div>
EOHTML;

    $aDisplayDetail = [
      '#type' => 'markup',
      '#markup' => $sMarkup,
    ];

    $sHtml = \Drupal::service('renderer')->renderRoot($aDisplayDetail);
    $oResponse->setContent($sHtml);
    return($oResponse);
  }
}