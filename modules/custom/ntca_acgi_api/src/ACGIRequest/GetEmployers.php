<?php

namespace Drupal\ntca_acgi_api\ACGIRequest;

use Symfony\Component\Validator\Exception\MissingOptionsException;
use Drupal\ntca_acgi_api\ACGICredManager;

class GetEmployers extends Request
{
    const YES = 'Y';
    const NO = 'N';
    protected $eSiteId; // will be set by a subclass of this
    protected $package = 'CENEMPWEBSVCLIB';
    protected $procedure = 'GET_EMPLOYERS_XML';
    protected $root = 'employer-request';
    protected $attributes = [
        'vendor-id' => null,
        'vendor-password' => null,
        'cust-id' => null,
        'org-type' => null, //optional from here down
        'preferred-only' => null, // Y or N
        'current-only' => null // Y or N
    ];

    function __construct(array $data = []) {

      $oCredManager = new ACGICredManager($this->eSiteId);
      $this->attributes['vendor-id'] = $oCredManager->getCred(ACGICredManager::CRED_ID_USERNAME);
      $this->attributes['vendor-password'] = $oCredManager->getCred(ACGICredManager::CRED_ID_PASSWORD);

      parent::__construct($data);
    }

    public function execute($xml=null){
        if(is_null($this->attributes['cust-id'])){
            throw new MissingOptionsException('cust-id is a required parameter for the GetEmployers method', ['cust-id']);
        }else{
            return parent::execute();
        }
    }
}