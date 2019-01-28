<?php

namespace Drupal\ntca_acgi_api\Entity;

class Registration extends Base
{
    protected $attributes = [
        'regi-serno' => null,
        'customer-id' => null,
        'event-id' => null,
        'registration-date' => null,
        'registration-type' => null,
        'registration-name' => null,
        'representing' => null,
        'billto-id' => null,
        'promo-cd' => null,
        'purchase-order' => null,
        'prim-item-id' => null,
        'prim-reg-status' => null,
        'total-charges' => null,
        'total-payment' => null,
        'balance' => null,
        'event-nm' => null,
        'program-nm' => null,
        'primary-item-descr' => null,
        'event-start-dt' => null,
        'event-end-dt' => null,
        'location-name' => null,
        'location-street1' => null,
        'location-street2' => null,
        'location-city' => null,
        'location-state' => null,
        'location-zip' => null,
        'location-country' => null,
        'location-country-descr' => null,
        'first-name' => null,
        'last-name' => null,
        'company-name' => null,
        'email' => null,
        'evt-reg-street1' => null,
        'evt-reg-street2' => null,
        'evt-reg-street3' => null,
        'evt-reg-city' => null,
        'evt-reg-state' => null,
        'evt-reg-zip' => null,
        'evt-reg-country' => null,
        'regAttributes' => [],
        'items' => [],
        'guests' => []
    ];
}