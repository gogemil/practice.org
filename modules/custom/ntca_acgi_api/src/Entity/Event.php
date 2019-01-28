<?php

namespace Drupal\ntca_acgi_api\Entity;

class Event extends Base
{
    protected $attributes = [
        'id' => null,
        'program-name' => null,
        'name' => null,
        'type' => null,
        'event-desrc' => null,
        'status' => null,
        'start-dt' => null,
        'end-dt' => null,
        'location-nm' => null,
        'location-street1' => null,
        'location-street2' => null,
        'location-city' => null,
        'location-state' => null,
        'location-zip' => null,
        'location-country' => null,
        'register-url' => null,
        'registration-status' => null,
        'attribute-list' => [],
        'validRegistrationTypes' => []
    ];
}