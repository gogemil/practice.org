<?php

namespace Drupal\ntca_acgi_api\Entity;

class Eventlist extends Base
{
    protected $attributes = [
        'event' => [],
        'status' => null,
        'message' => null,
    ];
}