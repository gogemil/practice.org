<?php

namespace Drupal\ntca_externalauth\Service;

use Drupal\ntca_acgi_api\ACGIRequest\NTCA as NTCA_API;
use Drupal\ntca_acgi_api\ACGIRequest\FRS as FRS_API;
use Drupal\user\Entity\User;
//use Drupal\user\Entity\UserStorage;
use Drupal\user\UserAuth;
use Drupal\ntca_acgi_api\Entity\Authentication;
use Drupal\ntca_acgi_api\Entity\Role;
use Drupal\externalauth\ExternalAuth;

class RemoteUserManagerService
{
  private $sServiceId = "ntcaremote";

  // if these Drupal roles are already on the user, don't remove them
  private $aPreserveRoles = array('content_editor', 'content_manager', 'site_manager', 'site_developer', 'administrator');
  // role mapping from remote source to Drupal machine id's - each role can go to one or more drupal roles
  private $aRoleMapping = array( // per ticket TRUTH-1108
    "MEMBER" => ['member'],
    "STAFF" => ['staff', 'member', 'rural_telecom', 'washington_report'],
    "PAC ELIGIBLE" => ['pac_contributor'], // Name changed, machine id stayed the same
    "TECO_ACCESS" => ['pac_contributor', 'member'],
    "RT_ACCESS" => ['rural_telecom'],
    "WR_ACCESS" => ['washington_report'],
    // NTCAON-66 add new roles
    "BENEFIT_ONLY" => ['benefit_only'],
    "NON-MEMBER" => ['non_member'],
    "NO_ACCESS" => ['no_access'],
  );

  protected $oUserAuth;

  /* @var \Drupal\externalauth\ExternalAuth $oExternalAuthService */
  protected $oExternalAuthService;

  public function __construct(\Drupal\user\UserAuth $oUserAuth, ExternalAuth $oExternalAuthService)
  {
    $this->oUserAuth = $oUserAuth;
    $this->oExternalAuthService = $oExternalAuthService;
  }

  public function ping()
  {
    return ("pong");
  }

  /**
   * Goes out to remote service, tries to get the user record, then either creates or updates that user
   * Returns the local uid, if successful, so the login can proceed as normal
   * @param $sUsername
   * @param $sPassword
   * @return array - keys are bUseFallback, oDrupalUser, iDrupalId, sSessionId, sBreadcrumbSessionId, sRemoteId
   */
  public function getLocalUserBasedOnForeignCreds($sUsername, $sPassword)
  {
    $aRetHash = array(
      "bUseFallback" => true,
      "oDrupalUser" => null,
      "iDrupalId" => 0,
    );

    // has this user already logged in from a remote source, and was registered locally?
    $oExternalAuthUserRecord = $this->oExternalAuthService->load($sUsername, $this->sServiceId);

    $oAuthenticatedUser = $this->getRemoteUserByUsernamePassword($sUsername, $sPassword);
    if (!$oAuthenticatedUser instanceof Authentication) {
      if ($oExternalAuthUserRecord instanceof User) {
        // we don't have an external user, but we do have an internal user already registered as a former external user
        // don't allow login to fallback, not allowed
        $aRetHash["bUseFallback"] = false;
      }
      return ($aRetHash);
    }
    if ($oAuthenticatedUser->authenticated != 'true') {
      // see above comment
      if ($oExternalAuthUserRecord instanceof User) {
        $aRetHash["bUseFallback"] = false;
      }
      return ($aRetHash);
    }

    $aRetHash["sSessionId"] = $oAuthenticatedUser->session->{'session-id'};
    $aRetHash["sBreadcrumbSessionId"] = $oAuthenticatedUser->session->{'breadcrumb-session-id'};
    $aRetHash["sRemoteId"] = $oAuthenticatedUser->customer->{'cust-id'};

    // got the user, let's check them as a local user, create or update
    $oDrupalUser = $this->updateOrCreateLocalUser($oAuthenticatedUser);
    if ($oDrupalUser instanceof User) {
      $aRetHash["oDrupalUser"] = $oDrupalUser;
      $aRetHash["iDrupalId"] = $oDrupalUser->id();

      if (!$oExternalAuthUserRecord instanceof User) {
        $this->markAsExternalUser($sUsername, $oDrupalUser);
      }
    }

    return ($aRetHash);
  }

  /**
   * Goes out to remote service, tries to get the user record, then either creates or updates that user
   * Returns the local uid, if successful, so the login can proceed as normal
   * @param $sSessionId
   * @param $sCustomerId
   * @return array - keys are bUseFallback, oDrupalUser, iDrupalId, sSessionId, sBreadcrumbSessionId, sRemoteId
   */
  public function getLocalUserBasedOnForeignTokens($sSessionId, $sCustomerId)
  {
    $aRetHash = array(
      "bUseFallback" => true,
      "oDrupalUser" => null,
      "iDrupalId" => 0,
    );

    // has this user already logged in from a remote source, and was registered locally?
//    $oExternalAuthUserRecord = $this->oExternalAuthService->load($sUsername, $this->sServiceId);

    $oAuthenticatedUser = $this->getRemoteUserBySessionCustomerId($sSessionId, $sCustomerId);
    if (!$oAuthenticatedUser instanceof Authentication) {
//      if ($oExternalAuthUserRecord instanceof User) {
      // we don't have an external user, but we do have an internal user already registered as a former external user
      // don't allow login to fallback, not allowed
//        $aRetHash["bUseFallback"] = false;
//      }
      $aRetHash['sMessage'] = 'Remote auth failed uncontrollably (error) for session/customer ids';
      return ($aRetHash);
    }

    if ($oAuthenticatedUser->authenticated != 'true') {
      // see above comment
//      if ($oExternalAuthUserRecord instanceof User) {
//        $aRetHash["bUseFallback"] = false;
//      }
      $aRetHash['sMessage'] = 'Remote auth failed controllably for session/customer ids';
      return ($aRetHash);
    }

    $aRetHash["sSessionId"] = $oAuthenticatedUser->session->{'session-id'};
    $aRetHash["sBreadcrumbSessionId"] = $oAuthenticatedUser->session->{'breadcrumb-session-id'};
    $aRetHash["sRemoteId"] = $oAuthenticatedUser->customer->{'cust-id'};

    // got the user, let's check them as a local user, create or update
    $oDrupalUser = $this->updateOrCreateLocalUser($oAuthenticatedUser);

    if ($oDrupalUser instanceof User) {

      $aRetHash["oDrupalUser"] = $oDrupalUser;
      $aRetHash["iDrupalId"] = $oDrupalUser->id();

//      if (!$oExternalAuthUserRecord instanceof User) {
//        $this->markAsExternalUser($sUsername, $oDrupalUser);
//      }
    }

    return ($aRetHash);
  }

  protected function markAsExternalUser($sUsername, User $oDrupalUser)
  {
    $this->oExternalAuthService->linkExistingAccount($sUsername, $this->sServiceId, $oDrupalUser);
  }

  /**
   * @param $sUsername
   * @param $sPassword
   * @return \Drupal\ntca_acgi_api\Entity\Authentication $oAuthenticatedUser
   */
  public function getRemoteUserByUsernamePassword($sUsername, $sPassword)
  {
    $oLoginService = new NTCA_API\Login([
      'username' => $sUsername,
      'password' => $sPassword
    ]);
    $oAccountObj = $oLoginService->execute();
    return ($oAccountObj);
  }

  /**
   * @param $sSessionId - contents of the SSISID cookie (something like 1AB15A5B)
   * @param $sCustomerId - contents of the p_CUST_ID cookie (a 7-digit number?)
   * @return \Drupal\ntca_acgi_api\Entity\Authentication $oAuthenticatedUser
   */
  public function getRemoteUserBySessionCustomerId($sSessionId, $sCustomerId)
  {
    $oLoginService = new NTCA_API\Login([
      'session-id' => $sSessionId,
      "cust-id" => $sCustomerId,
    ]);
    $oAccountObj = $oLoginService->execute();
    return ($oAccountObj);
  }

  /**
   *
   * @param $oAuthenticatedUser
   */
  public function updateOrCreateLocalUser(Authentication $oAuthenticatedUser)
  {
    // flatten the basic record
    $aUserInfo = array(
      "iRemoteUserId" => $oAuthenticatedUser->customer->{'cust-id'},
      "sFirstName" => $oAuthenticatedUser->customer->name->{'first-name'},
      "sLastName" => $oAuthenticatedUser->customer->name->{'last-name'},
      "sDisplayName" => $oAuthenticatedUser->customer->name->{'display-name'},
      "sCompany" => $oAuthenticatedUser->customer->name->{'company-name'},
      "sCustomerType" => $oAuthenticatedUser->customer->{'cust-type'},
      "sEmail" => $oAuthenticatedUser->customer->{'cust-email'},
      "aRoles" => array() // filled in separately (in a loop)
    );
    // attach the roles
    foreach ($oAuthenticatedUser->session->roles as $oRole) {
      if ($oRole instanceof Role) {
        array_push($aUserInfo['aRoles'], $oRole->name);
      }
    }

    // SYNTHETIC TEST, SWITCH ROLES HERE
//    $aUserInfo['aRoles'] = ['NO_ACCESS'];
// print_r($aUserInfo['aRoles']);
//    die();

    // note: roles are updates as a part of either create or update (see call to updateLocalUserRoles)
    if (!$this->doesLocalUserExist($aUserInfo["iRemoteUserId"])) {
      $oDrupalUser = $this->createLocalUser($aUserInfo);
    } else {
      $oDrupalUser = $this->updateLocalUser($aUserInfo);
    }
    return ($oDrupalUser);
  }

  public function doesLocalUserExist($iRemoteUserId)
  {
    $uid = $this->getLocalUserUidFromRemoteUserId($iRemoteUserId);
    if (empty($uid)) {
      return (false);
    }
    return (true);
  }

  protected function generateLocalUsernameFromRemoteUserId($iRemoteUserId)
  {
    $sNewUserId = "user_" . (intval($iRemoteUserId) + 1704);
    return ($sNewUserId);
  }

  protected function generateLocalPasswordFromRemoteUserId($iRemoteUserId)
  {
    $pwdBase = "You will never guess this maybe or not" . $iRemoteUserId . " something %@#@%@@";
    return (md5($pwdBase));
  }

  protected function createLocalUser($aUserInfo)
  {
    $sLocalUserId = $this->generateLocalUsernameFromRemoteUserId($aUserInfo["iRemoteUserId"]);
    $sLocalPassword = $this->generateLocalPasswordFromRemoteUserId($aUserInfo["iRemoteUserId"]);

    $oUser = User::create();
    $oUser->setUsername($sLocalUserId);
    $oUser->setPassword($sLocalPassword);
    $oUser->enforceIsNew();

    $this->updateLocalUserWithRemoteInfo($oUser, $aUserInfo);
    $this->updateLocalUserRoles($oUser, $aUserInfo['aRoles']);

    $oUser->activate();
    $oUser->save();

    return ($oUser);
  }

  protected function getLocalUserUidFromLocalUsername($sLocalUsername)
  {
    $oQuery = \Drupal::entityQuery('user')
      ->condition('name', $sLocalUsername, '=');
    $aResults = $oQuery->execute();
    if (empty($aResults)) {
      return (false);
    }
    $iLocalUserUid = array_shift($aResults);
    return ($iLocalUserUid);
  }

  protected function getLocalUserUidFromRemoteUserId($iRemoteUserId)
  {
    $sLocalUserId = $this->generateLocalUsernameFromRemoteUserId($iRemoteUserId);
    return ($this->getLocalUserUidFromLocalUsername($sLocalUserId));
  }

  protected function updateLocalUser($aUserInfo)
  {
    $iLocalUserUid = $this->getLocalUserUidFromRemoteUserId($aUserInfo["iRemoteUserId"]);
    if (empty($iLocalUserUid)) {
      return (false);
    }
    $oUser = User::load($iLocalUserUid);
    if (!$oUser instanceof User) {
      return (false);
    }

    $this->updateLocalUserWithRemoteInfo($oUser, $aUserInfo);
    $this->updateLocalUserRoles($oUser, $aUserInfo["aRoles"]);

    $oUser->save();
    return ($oUser);
  }

  /**
   * Updates email, and any other attribute like first/last name, etc
   */
  protected function updateLocalUserWithRemoteInfo(User $oUser, $aUserInfo)
  {
    $bHasChanges = false;

    $sCurrentEmail = $oUser->getEmail();
    if ($aUserInfo['sEmail'] != $sCurrentEmail) {
      $oUser->setEmail($aUserInfo['sEmail']);
      $bHasChanges = true;
    }
    $oUser->set('field_first_name', $aUserInfo['sFirstName']);
    $oUser->set('field_last_name', $aUserInfo['sLastName']);
  }

  /**
   * Synchronizes this user's roles with the ones given
   * @param $oDrupalUser
   * @param $aUserRoles - flat array of roles, like ['MEMBER', 'NONMEMBER', 'CO_ADMIN', 'CO_ADMIN_NONMEMBER'], etc
   */
  protected function updateLocalUserRoles(User $oDrupalUser, $aUserRoles)
  {
    // grab external roles requested, convert that into a list of Drupal roles we need
    $aUserRoles = $this->convertExternalRolesIntoInternalRoleIds($aUserRoles);

    // figure out what the user needs first
    $aDrupalRoles = $this->getAllAvailableRoleIds();

    // straight one-to-one mapping to Drupal roles for now
    $aNeededRoles = array();
    foreach ($aUserRoles as $sUserRole) {
      if (in_array($sUserRole, $aDrupalRoles)) {
        $aNeededRoles[] = $sUserRole;
      }
    }
    // now we know what the user needs to end up with in $aNeededRoles, let's compare that to their own roles, and add/remove as needed
    $aCurrentRoles = $oDrupalUser->getRoles(TRUE);

    // let's figure out what they're missing first, add those
    foreach ($aNeededRoles as $sNeededRoleId) {
      if (!in_array($sNeededRoleId, $aCurrentRoles)) {
        $oDrupalUser->addRole($sNeededRoleId);
      }
    }
    // then let's figure out extras, remove any role you have, but you're not supposed to have it
    // in the process, make sure you preserve any manually-added Drupal roles
    foreach ($aCurrentRoles as $sCurrentRole) {
      $bCurrentRoleNeededAccordingToPrimarySource = in_array($sCurrentRole, $aUserRoles);
      $bCurrentRoleExemptFromClearing = in_array($sCurrentRole, $this->aPreserveRoles);
      if (!$bCurrentRoleNeededAccordingToPrimarySource && !$bCurrentRoleExemptFromClearing) {
        $oDrupalUser->removeRole($sCurrentRole);
      }
    }
  }

  protected function convertExternalRolesIntoInternalRoleIds($aUserRoles)
  {
    // convert incoming roles into their Drupal equivalents
    $aUserRolesDrupalTranslations = array();
    foreach ($aUserRoles as $sUserRole) {
      if (array_key_exists($sUserRole, $this->aRoleMapping)) {
        $aUserRolesDrupalTranslations = array_merge($aUserRolesDrupalTranslations, $this->aRoleMapping[$sUserRole]);
      }
    }
    return ($aUserRolesDrupalTranslations);
  }

  /**
   * @return array - flat array of role id's (strings)
   */
  protected function getAllAvailableRoleIds()
  {
    $aRoles = \Drupal\user\Entity\Role::loadMultiple();
    return (array_keys($aRoles));
  }

  // Note: for logout cookie destruction, see the hook_user_logout in this module's .module file
  public function getCookieParts($sRemoteUserId, $sSessionId, $sBreadcrumbSessionId)
  {
    $sDomain = $_SERVER['HTTP_HOST'];
    $aDomainParts = explode(".", $sDomain);
    if (sizeof($aDomainParts) >= 2) {
      $sDomain = array_pop($aDomainParts); // com or org part
      $sDomain = array_pop($aDomainParts) . "." . $sDomain;
    }

    $aCookieInfo = array(
      array(
        'name' => 'ssisid',
        'value' => $sSessionId,
        'expireTS' => 0,
        'path' => '/',
        'domain' => $sDomain,
      ),
      array(
        'name' => 'ssalogin',
        'value' => 'yes',
        'expireTS' => 0,
        'path' => '/',
        'domain' => $sDomain,
      ),
      array(
        'name' => 'p_cust_id',
        'value' => $sRemoteUserId,
        'expireTS' => 0,
        'path' => '/',
        'domain' => $sDomain,
      ),
      array(
        'name' => 'breadcrumb_session_id',
        'value' => $sBreadcrumbSessionId,
        'expireTS' => 0,
        'path' => '/',
        'domain' => $sDomain,
      ),
      array(
        'name' => 'SSISID',
        'value' => $sSessionId,
        'expireTS' => 0,
        'path' => '/',
        'domain' => $sDomain,
      ),
      array(
        'name' => 'SSALOGIN',
        'value' => 'yes',
        'expireTS' => 0,
        'path' => '/',
        'domain' => $sDomain,
      ),
      array(
        'name' => 'P_CUST_ID',
        'value' => $sRemoteUserId,
        'expireTS' => 0,
        'path' => '/',
        'domain' => $sDomain,
      ),
      array(
        'name' => 'BREADCRUMB_SESSION_ID',
        'value' => $sBreadcrumbSessionId,
        'expireTS' => 0,
        'path' => '/',
        'domain' => $sDomain,
      ),
      array(
        'name' => 'localextlogin',
        'value' => 'yes',
        'expireTS' => 0,
        'path' => '/',
        'domain' => $sDomain,
      )
    );

    return ($aCookieInfo);
  }

  public static function isCurrentUserARemoteUser()
  {
    $sRemoteSession = @$_COOKIE['ssisid'];
    return ($sRemoteSession != '');
  }
}