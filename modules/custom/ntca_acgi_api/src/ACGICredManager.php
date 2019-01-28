<?php

namespace Drupal\ntca_acgi_api;

class ACGICredManager {

  const SITE_FRS = 'frs';
  const SITE_NTCA = 'ntca';

  // you can ask for these credentials
  const CRED_ID_COREURL = 'coreURL';
  const CRED_ID_ENVURI = 'integratorEnvURI';
  const CRED_ID_USERNAME = 'integratorUsername';
  const CRED_ID_PASSWORD = 'integratorPassword';
  const CRED_ID_LOGINJUMPURL = 'loginJumpUrl';

  private $eSite = '';
  private $aCreds ='';

  function __construct($eSite = '') {
    $this->setSite($eSite);
  }

  public function setSite($eSite) {
    if ($eSite == self::SITE_FRS || $eSite == self::SITE_NTCA) {
      $this->eSite = $eSite;
    }

    $this->readCreds();
  }

  /**
   * Reads creds from /sites/default/settings.acgi-api-access.php, if it exists
   */
  private function readCreds() {
    $sCreds = array();
    if (defined('DRUPAL_ROOT')) {
      $sDrupalRootPath = DRUPAL_ROOT;
    } else {
      throw(new Exception("DRUPAL_ROOT not defined"));
    }

    $sFile = $sDrupalRootPath."/sites/default/settings.acgi-api-access.php";
    if (!file_exists($sFile)) {
      throw(new Exception("ACGI settings not defined."));
    }
    include($sFile);
    if (!is_array($aAcgiCreds)) {
      throw(new Exception("Malformed ACGI settings, use same format at example."));
    }

    $aCreds = $aAcgiCreds['default'];
    if (array_key_exists($this->eSite, $aAcgiCreds['siteoverrides'])) {
      $aCreds = array_merge($aCreds, $aAcgiCreds['siteoverrides'][$this->eSite]);
    }

    $this->aCreds = $aCreds;
  }

  public function getAllCreds() {
    return($this->aCreds);
  }

  public function getCred($eCredIdentifier) {
    if (array_key_exists($eCredIdentifier, $this->aCreds)) {
      return($this->aCreds[$eCredIdentifier]);
    }
    return('');
  }
}
