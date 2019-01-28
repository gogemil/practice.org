<?php

namespace Drupal\ntca_acgi_api\Entity;

class CustomerName extends Base
{
    private $attributes = [
        'display-name' => null,
        'last-name' => null,
        'first-name' => null,
        'company-name' => null,
        'title-name' => null,
    ];
}