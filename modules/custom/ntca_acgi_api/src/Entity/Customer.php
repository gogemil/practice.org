<?php

namespace Drupal\ntca_acgi_api\Entity;

class Customer extends Base
{
    protected $attributes = [
        'cust-id' => null,
        'cust-type' => null,
        'name' => null,
        'cust-email' => null
    ];

}