<?php

namespace Drupal\ntca_acgi_api\Entity;

class Session extends Base
{
    protected $attributes = [
        'session-id' => null,
        'session_id' => null,
        'breadcrumb-session-id' => null,
        'login-serno' => null,
        'roles' => [],
    ];
}