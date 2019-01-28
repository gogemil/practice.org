<?php

namespace Drupal\ntca_acgi_api\ACGIResponse;


class Response{

    public function processResposne($xml, $aryCurlInfo){
        $objXML = new \SimpleXMLElement($xml);
        $strClass = $this->getClassName($objXML->getName());
        $objResponse = $strClass::build($objXML);
        return $objResponse;
    }

    private function getClassName($className){
        return 'Drupal\\ntca_acgi_api\\Entity\\'.ucfirst(strtolower(str_replace('-', '', $className)));
    }
}