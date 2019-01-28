<?php

namespace Drupal\ntca_acgi_api\ACGIRequest;

use Drupal\ntca_acgi_api\ACGIResponse\Response;
use Drupal\ntca_acgi_api\ACGICredManager;

abstract class Request
{
    protected $eSiteId; // corresponds to one of two ACGICredManager's SITE_ consts, set in child classes
    private $url;
    private $bDebug = false;

    function __construct(array $data= []){

      $oCredManager = new ACGICredManager($this->eSiteId);
      $this->url =
        $oCredManager->getCred(ACGICredManager::CRED_ID_COREURL).
        $oCredManager->getCred(ACGICredManager::CRED_ID_ENVURI).
        "/";

      if(count($data)>0){
            foreach($data as $key => $val){
                if(array_key_exists($key, $this->attributes)){
                    $this->attributes[$key] = $val;
                }
            }
        }
    }

    private function _buildRequest(){
        $dom = new \DOMDocument();
        $request = $dom->createElement($this->root);
        foreach ($this->attributes as $key => $val){
            if(!is_null($val)){
                $ele = $dom->createElement($key);
                $ele->nodeValue = $val;
                $request->appendChild($ele);
            }
        }
        $dom->appendChild($request);
        return str_replace(PHP_EOL, '', $dom->saveXML());
    }

    public function execute($xml=null){
        if(is_null($xml)){
            $xml = $this->_buildRequest();
        }
//        echo $xml;
        $url = $this->url.$this->package.'.'.$this->procedure;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        if(!$this->bDebug){
          curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
          curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query([
            'P_INPUT_XML_DOC' => $xml
        ]));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $aryInfo = curl_getinfo($ch);
        if($httpcode === 200){
            if($this->bDebug){
                $dom = new \DOMDocument();
                $dom->formatOutput = true;
                $dom->loadXML($result);
//                echo "<pre>RESPONSE: ".$dom->saveXML()."</pre>";
//                echo "RESPOSNE: ".$result.PHP_EOL.PHP_EOL;
            }
            $response = new Response();
            return $response->processResposne($result, $aryInfo);
        } else {
            if($this->bDebug){
                $info = curl_getinfo($ch);
                print('<pre>') ; print_r($info) ; print('</pre>');
            }
            return $httpcode;
        }
    }

}
