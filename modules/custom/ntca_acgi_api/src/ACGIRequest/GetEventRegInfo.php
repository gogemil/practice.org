<?php

namespace Drupal\ntca_acgi_api\ACGIRequest;

use Drupal\ntca_acgi_api\ACGIResponse\Response;
use Drupal\ntca_acgi_api\ACGICredManager;

class GetEventRegInfo extends Request
{
    protected $eSiteId; // will be set by a subclass of this
    protected $package = 'EVTSSAWEBSVCLIB';
    protected $procedure = 'GET_EVENTREG_INFO_XML';
    protected $root = 'eventreg-request';
    protected $attributes = [
        'vendor-id' => null,
        'vendor-password' => null,
        'cust-id' => null, //Optional, but should not be null if event-id is null
        'event-id' => null, //Optional, but should not be null if cust-id is null
        'first-name' => null, //Optional, used as an alternate lookup parameter
        'last-name' => null, //Optional, used as an alternate lookup parameter
        'company-name' => null, //Optional, used as an alternate lookup parameter
        'email' => null, //Optional, used as an alternate lookup parameter
        'secondaries-require-ticket' => null, //Optional boolean input. Further explanation below
        'regAttributes' => [
            'include' => false,
            'includeAll' => false,
            'attrs' => [] //<attr type="CELLPHONE" /> or <attr type="FREE_GIFT" code="TSHIRT" />
        ],
        'regItemAttributes ' => [
            'include' => false,
            'includeAll' => false,
            'attrs' => [] //<attr type="DIET_RESTRICTION" /> or <attr type="FREE_GIFT" code="TSHIRT" />
        ]
    ];

    function __construct(array $data = []){

      $oCredManager = new ACGICredManager($this->eSiteId);
      $this->attributes['vendor-id'] = $oCredManager->getCred(ACGICredManager::CRED_ID_USERNAME);
      $this->attributes['vendor-password'] = $oCredManager->getCred(ACGICredManager::CRED_ID_PASSWORD);

      parent::__construct($data);
    }

    // let's override this to take the special reg attribute items
    // this is used in the parent class
    final private function _buildRequest(){
        $dom = new \DOMDocument();
        $dom->formatOutput = true;
        $request = $dom->createElement($this->root);
        foreach ($this->attributes as $key => $val){
            if(is_array($val)){
                if(count($val['attrs']) > 0){
                    $ele = $dom->createElement($key);
                    $ele->setAttribute('include', (isset($val['include']) && $val['include']===true?'true':'false'));
                    $ele->setAttribute('includeAll', (isset($val['includeAll']) && $val['includeAll']===true?'true':'false'));
                    foreach($val['attrs'] as $attrToAdd) {
                        $attribute = $dom->createElement('attr');
                        foreach ($attrToAdd as $attr => $attrVal){
                            $attribute->setAttribute($attr, $attrVal);
                        }
                        $ele->appendChild($attribute);
                    }
                    $request->appendChild($ele);
                }
            }else{
                if(!is_null($val)){
                    $ele = $dom->createElement($key);
                    $ele->nodeValue = $val;
                    $request->appendChild($ele);
                }
            }
        }
        $dom->appendChild($request);
        return str_replace(PHP_EOL, '', $dom->saveXML());
    }

    // we're also going to override this function
    // so that this build request is called
    // otherwise this buildrequest is never called from the patent class
    final public function execute($xml=null){
        $xml = $this->_buildRequest();
        return parent::execute($xml);
    }
}