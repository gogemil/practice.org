<?php

namespace Drupal\ntca_externalauth\Controller;
//use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Controller;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\user\UserAuthInterface;
use Drupal\user\UserInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;
// our own remote service
use Drupal\ntca_externalauth\Service\RemoteUserManagerService;

//class NTCAExternalAuthController extends Drupal\UserAuthenticationController implements ContainerInjectionInterface
class UserAuthenticationController extends \Drupal\user\Controller\UserAuthenticationController implements ContainerInjectionInterface
{
    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
        if ($container->hasParameter('serializer.formats') && $container->has('serializer')) {
            $serializer = $container->get('serializer');
            $formats = $container->getParameter('serializer.formats');
        }
        else {
            $formats = ['json'];
            $encoders = [new JsonEncoder()];
            $serializer = new Serializer([], $encoders);
        }

        return new static(
            $container->get('ntca_externalauth.remoteusermanagerservice'),
            $container->get('flood'),
            $container->get('entity_type.manager')->getStorage('user'),
            $container->get('csrf_token'),
            $container->get('user.auth'),
            $container->get('router.route_provider'),
            $serializer,
            $formats
        );
    }

    /**
     * Constructs a new UserAuthenticationController object.
     *
     * @param \Drupal\ntca_externalauth\Service\RemoteUserManagerService
     *   Special remote user service, for external Auth
     * @param \Drupal\Core\Flood\FloodInterface $flood
     *   The flood controller.
     * @param \Drupal\user\UserStorageInterface $user_storage
     *   The user storage.
     * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token
     *   The CSRF token generator.
     * @param \Drupal\user\UserAuthInterface $user_auth
     *   The user authentication.
     * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
     *   The route provider.
     * @param \Symfony\Component\Serializer\Serializer $serializer
     *   The serializer.
     * @param array $serializer_formats
     *   The available serialization formats.
     */
    public function __construct(RemoteUserManagerService $remoteUserManagerService, FloodInterface $flood, UserStorageInterface $user_storage, CsrfTokenGenerator $csrf_token, UserAuthInterface $user_auth, RouteProviderInterface $route_provider, Serializer $serializer, array $serializer_formats) {
        $this->remoteUserManagerService = $remoteUserManagerService;
        $this->flood = $flood;
        $this->userStorage = $user_storage;
        $this->csrfToken = $csrf_token;
        $this->userAuth = $user_auth;
        $this->serializer = $serializer;
        $this->serializerFormats = $serializer_formats;
        $this->routeProvider = $route_provider;
    }

    /**
     * Logs in a user.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The request.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *   A response which contains the ID and CSRF token.
     */
    public function login(Request $request)
    {
        $format = $this->getRequestFormat($request);

        $content = $request->getContent();
        $credentials = $this->serializer->decode($content, $format);

        if (!isset($credentials['name']) && !isset($credentials['pass'])) {
            throw new BadRequestHttpException('Missing credentials.');
        }
        if (!isset($credentials['name'])) {
            throw new BadRequestHttpException('Missing credentials.name.');
        }
        if (!isset($credentials['pass'])) {
            throw new BadRequestHttpException('Missing credentials.pass.');
        }

        // $aResult will always contain an array with keys "bUseFallback", "oDrupalUser", "iDrupalId"
        $aResult = $this->remoteUserManagerService->getLocalUserBasedOnForeignCreds($credentials['name'], $credentials['pass']);

        // $aResult will always contain an array with keys "bUseFallback", "oDrupalUser", "iDrupalId", "sSessionId", "sBreadcrumbSessionId", "sRemoteId"
        if ($aResult["iDrupalId"] != null) {
            // external login successful, have a local user, log them in
            // very similar to parent's action/response (minus flooding)
            $user = $aResult["oDrupalUser"];
            $this->userLoginFinalize($user);

            $aCookiesToSet = $this->remoteUserManagerService->getCookieParts($aResult['sRemoteId'], $aResult['sSessionId'], $aResult['sBreadcrumbSessionId']);
            foreach ($aCookiesToSet as $aCookieHash) {
              setcookie($aCookieHash['name'], $aCookieHash['value'], $aCookieHash['expireTS'], $aCookieHash['path'], $aCookieHash['domain']);
            }

          // Send basic metadata about the logged in user.
            $response_data = [];
            if ($user->get('uid')->access('view', $user)) {
                $response_data['current_user']['uid'] = $user->id();
            }
            if ($user->get('roles')->access('view', $user)) {
                $response_data['current_user']['roles'] = $user->getRoles();
            }
            if ($user->get('name')->access('view', $user)) {
                $response_data['current_user']['name'] = $user->getAccountName();
            }
            $response_data['csrf_token'] = $this->csrfToken->get('rest');

            $logout_route = $this->routeProvider->getRouteByName('user.logout.http');
            // Trim '/' off path to match \Drupal\Core\Access\CsrfAccessCheck.
            $logout_path = ltrim($logout_route->getPath(), '/');
            $response_data['logout_token'] = $this->csrfToken->get($logout_path);

            $encoded_response_data = $this->serializer->encode($response_data, $format);
            return new Response($encoded_response_data);

        } else if ($aResult['bUseFallback']) {

            // external auth failed, go to Drupal to try to log in
            $oResponse = parent::login($request);
            // either good, and it returns a response, or it will throw an exception
            return($oResponse);

        } else {
            // external auth failed, but a local user exists, and a prev. ext. auth was linked to it
            // disallow this login
            throw new BadRequestHttpException('Sorry, unrecognized username or password.');
        }
    }

    /**
     * Fires from JS when a logged-in token is detected, but no local session
     */
    public function loginWithToken(Request $request) {

      // double-check local status, cookie
      $oCurrentUser = \Drupal::currentUser();
      $sCurrentUser = $oCurrentUser->getDisplayName();
      if ($sCurrentUser != "Anonymous") {
        return (new JsonResponse(array(
          "bLoginSuccess" => false,
          "sMessage" => "Already Logged In"
        )));
      }

      $aCookies = $request->cookies;
      if ($aCookies->has("ssisid") && $aCookies->has("p_cust_id")) {
        $sSessionId = $aCookies->get("ssisid");
        $sCustomerId = $aCookies->get("p_cust_id");
      } else {
        // try GET params instead
        $aParams = $request->query->all();
        if (isset($aParams['ssisid']) && isset($aParams['p_cust_id'])) {
          $sSessionId = $aParams['ssisid'];
          $sCustomerId = $aParams['p_cust_id'];
        } else {
          return (new JsonResponse(array(
            "bLoginSuccess" => false,
            "sMessage" => "Missing params (either as GET or Cookies)"
          )));
        }
      }

      // actually do the logging in
      $aResult = $this->remoteUserManagerService->getLocalUserBasedOnForeignTokens($sSessionId, $sCustomerId);

      // $aResult will always contain an array with keys "bUseFallback", "oDrupalUser", "iDrupalId", "sSessionId", "sBreadcrumbSessionId", "sRemoteId"
      if ($aResult["iDrupalId"] != null) {
        // external login successful, have a local user, log them in
        $user = $aResult["oDrupalUser"];
        $this->userLoginFinalize($user);

        $aCookiesToSet = $this->remoteUserManagerService->getCookieParts($aResult['sRemoteId'], $aResult['sSessionId'], $aResult['sBreadcrumbSessionId']);
        foreach ($aCookiesToSet as $aCookieHash) {
          setcookie($aCookieHash['name'], $aCookieHash['value'], $aCookieHash['expireTS'], $aCookieHash['path'], $aCookieHash['domain']);
        }

        return (new JsonResponse(array(
          "bLoginSuccess" => true,
          "sMessage" => "Logged in"
        )));

      } else {

        return (new JsonResponse(array(
          "bLoginSuccess" => false,
          "sMessage" => "Error processing tokens: ".$aResult["sErrorMessage"]
        )));

      }

    }
}