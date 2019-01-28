<?php

namespace Drupal\ntca_externalauth\Form;

use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\user\UserAuthInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

// our own remote service, and cred manager for some links
use Drupal\ntca_externalauth\Service\RemoteUserManagerService;
use Drupal\ntca_acgi_api\ACGICredManager;

/**
 * Extends the core user login form.
 */
class UserLoginForm extends \Drupal\user\Form\UserLoginForm
{
  /**
   * Remote User Management
   *
   * @var \Drupal\ntca_externalauth\Service\RemoteUserManagerService
   */
  protected $remoteUserManagerService;

  /**
   * {@inheritdoc}
   */
//  public function getFormId() {
//    return 'ntca_externalauth_user_login_form';
//  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
      return new static(
          $container->get('ntca_externalauth.remoteusermanagerservice'),
          $container->get('flood'),
          $container->get('entity.manager')->getStorage('user'),
          $container->get('user.auth'),
          $container->get('renderer')
      );
  }

  /**
   * Constructs a new UserLoginForm.
   *
   * @param \Drupal\ntca_externalauth\Service\RemoteUserManagerService
   *   Special remote user service, for external Auth
   * @param \Drupal\Core\Flood\FloodInterface $flood
   *   The flood service.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param \Drupal\user\UserAuthInterface $user_auth
   *   The user authentication object.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(RemoteUserManagerService $remoteUserManagerService, FloodInterface $flood, UserStorageInterface $user_storage, UserAuthInterface $user_auth, RendererInterface $renderer) {
      $this->remoteUserManagerService = $remoteUserManagerService;
      $this->flood = $flood;
      $this->userStorage = $user_storage;
      $this->userAuth = $user_auth;
      $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // do what the parent does, add destination
    $form = parent::buildForm($form, $form_state);
    $form['destination'] = array(
      '#type' => 'hidden',
//      '#value' => '',
    );

    // block of text before the login fields
    // pull a custom block by title
    $oBlockStorageEntity = \Drupal::entityTypeManager()->getStorage('block_content');
    $aBlocks = $oBlockStorageEntity->loadByProperties(['info' => 'Login Preamble Block - DO NOT DELETE OR CHANGE TITLE']);

    if (sizeof($aBlocks) > 0) {
      $oBlock = array_shift($aBlocks);
      $oBlockView = \Drupal::entityTypeManager()->getViewBuilder('block_content')->view($oBlock);

      $form['preamble'] = array(
        '#markup' => render($oBlockView),
        '#weight' => -1000
      );
    }

    $oACGICredManager = new ACGICredManager();
    $sForgotPasswordLink = $oACGICredManager->getCred("forgotPasswordUrl");
    $sRegisterNewAccountLink = $oACGICredManager->getCred("registerAccountUrl");
    // links after the login window
    $sExtraLinkHTML = <<<EOHTML
        <div class="link-wrapper">
            <div class="forgot-link">
                <a href="$sForgotPasswordLink">
                    Forgot username/Password?
                </a>
            </div>
            <div class="register-link">  
                <a href="$sRegisterNewAccountLink">
                    Register for an account
                </a>            
            </div>
        </div>
EOHTML;

    $form['extralinks'] = array(
      '#markup' => $sExtraLinkHTML,
      '#weight' => 1000
    );
    return $form;
  }

  /**
   * Mostly repeated from the parent UserLoginForm, but we check for destination, etc
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $account = $this->userStorage->load($form_state->get('uid'));

    // A destination was set, probably on an exception controller,
    if (!$this->getRequest()->request->has('destination')) {
      // no special destination, go to homepage after login
      $this->getRequest()->query->set('destination', "/");
    }
    else {
      $this->getRequest()->query->set('destination', $this->getRequest()->request->get('destination'));
  }

    user_login_finalize($account);
  }

  /**
   * Overridden validateAuthentication from parent class, insert our own calls in here
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function validateAuthentication(array &$form, FormStateInterface $form_state) {
    $sUsername = $form_state->getValue('name');
    $sPassword = $form_state->getValue('pass');

    $aResult = $this->remoteUserManagerService->getLocalUserBasedOnForeignCreds($sUsername, $sPassword);
//    $aResult = array(
//      'iDrupalId' => null,
//      'bUseFallback' => true
//    );
    // $aResult will always contain an array with keys "bUseFallback", "oDrupalUser", "iDrupalId", "sSessionId", "sBreadcrumbSessionId", "sRemoteId"
    if ($aResult["iDrupalId"] != null) {

        // very similar to parent's action/response (minus flooding)
        $uid = $aResult['iDrupalId'];
        $form_state->set('uid', $uid);

        $aCookiesToSet = $this->remoteUserManagerService->getCookieParts($aResult['sRemoteId'], $aResult['sSessionId'], $aResult['sBreadcrumbSessionId']);
        foreach ($aCookiesToSet as $aCookieHash) {
          setcookie($aCookieHash['name'], $aCookieHash['value'], $aCookieHash['expireTS'], $aCookieHash['path'], $aCookieHash['domain']);
        }

    } else if ($aResult['bUseFallback']) {

        // external auth failed, go to Drupal to try to log in
        parent::validateAuthentication($form, $form_state);

    } else {
        // external auth failed, but a local user exists, and a prev. ext. auth was linked to it
        // disallow this login
        $form_state->set('uid', FALSE);
    }
  }
}