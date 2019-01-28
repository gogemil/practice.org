<?php

namespace Drupal\ntca_acgi_api\ACGIRequest;

use Drupal\ntca_acgi_api\ACGICredManager;

class GetValidRoles extends Request
{
    protected $eSiteId; // will be set by a subclass of this
    protected $package = 'CENSSAWEBSVCLIB';
    protected $procedure = 'GET_VALID_ROLES_XML';
    protected $root = 'roleRequest';
    protected $attributes = [
        'integratorUsername' => null,
        'integratorPassword' => null,
        'filter' => null
    ];

    function __construct(array $data = []){

      $oCredManager = new ACGICredManager($this->eSiteId);
      $this->attributes['integratorUsername'] = $oCredManager->getCred(ACGICredManager::CRED_ID_USERNAME);
      $this->attributes['integratorPassword'] = $oCredManager->getCred(ACGICredManager::CRED_ID_PASSWORD);

      parent::__construct($data);
    }
}