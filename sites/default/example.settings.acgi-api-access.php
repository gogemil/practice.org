<?php

/**
 * @file
 * ACGI API Credentials and Login info
 *:q
 * To use, rename this file to /sites/default/settings.acgi-api-access.php
 *
 * Note: to access these, please use Drupal\ACGIRequest\Service\
 * see Crypt-o for ACGICreds folder
 *
 * These will be loaded into
 * - modules\custom\ntca_acgi_api\src\ACGICredManager.php
 * - modules\custom\ntca\src\Controller\UserStatusController.php
 *
 */

$aAcgiCreds = [
  'default' => [
    // the next four should be overridden in frs/ntca site override
    'coreURL' => '', // ends in slash, typically, the domain name
    'integratorEnvURI' => '', // This is the endpoint (path) in the URL
    'integratorUsername' => '',
    'integratorPassword' => '',
    // URLs (environment specific)
    'forgotPasswordUrl' => 'http://...',      // in the login box/on the login page
    'registerAccountUrl' => 'http://...',     // in the login box/on the login page
    'loginJumpUrl' => 'http://...',           // shows when you've logged in (i.e. Member central link)
  ],
  'siteoverrides' => [
    'frs' => [
      'coreURL' => '',
      'integratorEnvURI' => '',
      'integratorUsername' => '',
      'integratorPassword' => ''
    ],
    'ntca' => [
      'coreURL' => '',
      'integratorEnvURI' => '',
      'integratorUsername' => '',
      'integratorPassword' => ''
    ]
  ]
];
