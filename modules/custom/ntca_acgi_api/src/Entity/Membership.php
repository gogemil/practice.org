<?php

namespace Drupal\ntca_acgi_api\Entity;

class Membership extends Base
{

    protected $attributes = [
        'member' => null,
        'status' => null,
        'subgroup-ID' => null,
        'subgroup-type' => null,
        'subgroup-name' => null,
        'class-code' => null,
        'subclass-code' => null,
        'level-of-service' => null,
        'end-of-service-date' => null,
        'paid-through-date' => null,
        'inheritedFromCustId' => null
    ];

}