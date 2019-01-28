<?php

/**
 * @file
 * Contains \Drupal\ntca_externalauth\Controller\NTCAExternalAuthController.
 */

namespace Drupal\ntca_externalauth\Controller;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\ntca_externalauth\Service\RemoteUserManagerService;

class NTCAExternalAuthController extends ControllerBase {
    private $oUserManagementService;

    // whatever this returns, you can then use that list of services in the actual constructor
    public static function create(ContainerInterface $container) {
        return(new static(
            $container->get('ntca_externalauth.remoteusermanagerservice')
        ));
    }

    public function __construct(RemoteUserManagerService $rsm) {
        $this->oUserManagementService = $rsm;
    }

    public function content() {
        $sUsername = "testanother";
        $sPassword = "testanother";
        $aResult = $this->oUserManagementService->getLocalUserBasedOnForeignCreds($sUsername, $sPassword);

        return array(
            '#type' => 'markup',
            '#markup' => print_r($aResult, true),
        );
    }

    public function getRawCreds() {

      // try cookies first
      $sSessionId = @$_COOKIE['SSISID'];
      $sCustomerId = @$_COOKIE['P_CUST_ID'];

      if ($sSessionId == '' || $sCustomerId == '') {
        $sSessionId = @$_GET['ssisid'];
        $sCustomerId = @$_GET['p_cust_id'];
      }

      if ($sSessionId == '' || $sCustomerId == '') {
        return [
          '#type' => 'markup',
          '#markup' => "No session or customer id in either cookies or ssisid/p_cust_id GET params"
        ];
      } else {
        $aRawResult = $this->oUserManagementService->getRemoteUserBySessionCustomerId($sSessionId, $sCustomerId);
        return [
          '#type' => 'markup',
          '#markup' => "<pre>".print_r($aRawResult, true)."</pre>",
        ];
      }
    }

}