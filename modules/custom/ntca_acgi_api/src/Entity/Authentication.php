<?php

namespace Drupal\ntca_acgi_api\Entity;

class Authentication extends Base
{
    protected $attributes = [
        'authenticated' => null,
        'authentication-message' => null,
        'authentication-error-id' => null,
        'session' => null,
        'customer' => null,
        'memberships' => []
    ];
}