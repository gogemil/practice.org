<?php

namespace Drupal\ntca_acgi_api\ACGIRequest;

use Drupal\ntca_acgi_api\ACGICredManager;

class GetEventInfo extends Request
{
    const STATUS_ACTIVE = 'ACTIVE';
    protected $eSiteId; // will be set by a subclass of this
    protected $package = 'EVTSSAWEBSVCLIB';
    protected $procedure = 'GET_EVENT_INFO_XML';
    protected $root = 'event-request';
    protected $attributes = [
        'vendor-id' => null,
        'vendor-password' => null,
        'cust-id' => null, //optional from here down
        'start-date' => null,
        'end-date' => null,
        'status' => null,
        'category' => null,
        'event-type' => null,
        'item-type' => null,
        'state-code' => null,
        'sponsor' => null
    ];

    function __construct(array $data = []){

      $oCredManager = new ACGICredManager($this->eSiteId);
      $this->attributes['vendor-id'] = $oCredManager->getCred(ACGICredManager::CRED_ID_USERNAME);
      $this->attributes['vendor-password'] = $oCredManager->getCred(ACGICredManager::CRED_ID_PASSWORD);

      parent::__construct($data);
    }
}