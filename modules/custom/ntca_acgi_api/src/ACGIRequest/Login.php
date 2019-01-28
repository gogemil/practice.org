<?php

namespace Drupal\ntca_acgi_api\ACGIRequest;

use Drupal\ntca_acgi_api\ACGICredManager;

abstract class Login extends Request{

    protected $eSiteId; // will be set by a subclass of this
    protected $package = 'censsawebsvclib';
    protected $procedure = 'authentication';
    protected $root = 'authentication-request';
    protected $attributes = [
        'integratorUsername' => null,
        'integratorPassword' => null,
        'cust-id' => null,
        'last-nm' => null,
        'alias' => null,
        'username' => null,
        'password' => null,
        'session-id' => null,
        'session-id' => null
    ];

    function __construct(array $data = []){

      $oCredManager = new ACGICredManager($this->eSiteId);
      $this->attributes['integratorUsername'] = $oCredManager->getCred(ACGICredManager::CRED_ID_USERNAME);
      $this->attributes['integratorPassword'] = $oCredManager->getCred(ACGICredManager::CRED_ID_PASSWORD);

      parent::__construct($data);
    }

}