<?php

namespace Drupal\ntca_acgi_api\Entity;

class Base
{
    function __construct($data = [])
    {
        foreach($data as $key => $val){
            if(array_key_exists($key, $this->attributes)){
                $this->attributes[$key] = $val;
            }
        }
    }

    function __set($name, $value)
    {
        if(property_exists($this ,'attributes') && !is_null($this->attributes)) {
            if (array_key_exists($name, $this->attributes)) {
                $this->attributes[$name] = $value;
                return true;
            }
        }
        return null;
    }

    function __get($name)
    {
        if(property_exists($this, 'attributes') && !is_null($this->attributes)){
            if(array_key_exists($name, $this->attributes)){
                return $this->attributes[$name];
            }
        }
        return null;
    }

    public static function build(\SimpleXMLElement $objXML, $bStop=false){
        $calledClass = get_called_class();
        $calledClassObject = new $calledClass;

        // some override's need to happen for the foreach code below.
        if($calledClassObject instanceof Role){
            // Role doesn't have attributes in the API, we need to build our own
            $calledClassObject->name = (string)$objXML;
            return $calledClassObject;
        }
        if($calledClassObject instanceof Rolelist){
            $tmp = [];
            foreach($objXML->role as $role){
                $tmp[] = new Role(['name' => $role]);
            };
            $calledClassObject->role = $tmp;
            return $calledClassObject;
        }
        foreach($objXML as $attribute => $value){
            if($attribute == "guests"){
                /*print_r($value->count());
                print_r($calledClassObject);
                exit;*/
            }
            if($value->count() == 0){
                $calledClassObject->$attribute = (string)$value;
            }else{
                if(!$bStop){
                    $strClassName = ucfirst(strtolower(str_replace("-", '',$value->getName())));
                    $strClass = 'Drupal\\ntca_acgi_api\\Entity\\'.$strClassName;
                    if(strtolower(substr($strClassName, -1)) == "s"){ // multiple class
                        /*
                         * in this situation, we're referring to an xml element that looks similar to this:
                         *
                         * <guests>
                         *  <guest></guest>
                         *  <guest></guest>
                         *  <guest></guest>
                         * </guests>
                         *
                         * let's skip multiple arrays and just  attach the <guest> object to the parent <guests>
                         * to do this, lets look for the "s" at the end of the element name.
                         * If the API changes, this will need to be updated.
                         *
                         * !!!!! This is a little bit of a shady to detect this. !!!!!
                         * */
                        $calledClassObject->$attribute = [];
                        $aryTmp = [];
                        foreach($value as $attr2 => $val2){
                            // these variables are only used in this loop
                            $strClassName2 = ucfirst(strtolower($attr2));
                            $strClass2 = 'Drupal\\ntca_acgi_api\\Entity\\'.$strClassName2;
                            $aryTmp[] = $strClass2::build($val2, false);
                        }
                        $calledClassObject->$attribute = $aryTmp;
                    }else{
                        /*if($attribute == 'items'){
                            echo $strClassName.PHP_EOL;
                            print_r($calledClassObject->$attribute);
                            print_r($value);
                            exit;
                        }*/
                        if(is_array($calledClassObject->$attribute)){
                            $tmp = $calledClassObject->{$attribute};
                            $tmp[] = $strClass::build($value, false);
                            $calledClassObject->{$attribute} = $tmp;
                        }else{
                            //print_r($value);exit;
                            $calledClassObject->$attribute = $strClass::build($value, false);
                        }

                    }
                }
            }
        }
        return $calledClassObject;
    }
}