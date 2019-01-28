<?php

namespace Drupal\ntca_acgi_api\Entity;

class Subscription extends Base
{
    protected $attributes = [
        'package-code' => null,
        'package-name' => null,
        'benefit-of-membership' => null,
        'start-date' => null,
        'end-of-service-date' => null,
        'paid-through-date' => null
    ];
}