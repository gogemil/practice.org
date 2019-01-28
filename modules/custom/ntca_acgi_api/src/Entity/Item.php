<?php

namespace Drupal\ntca_acgi_api\Entity;

class Item extends Base
{
    protected $attributes = [
        'id' => null,
        'descr' => null,
        'registration-type' => null,
        'registration-status' => null,
        'registration-date' => null,
        'quantity' => null,
        'attended' => null,
        'regItemAttributes' => [],
        
    ];
}