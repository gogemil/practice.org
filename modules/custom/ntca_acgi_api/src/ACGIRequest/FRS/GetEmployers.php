<?php

namespace Drupal\ntca_acgi_api\ACGIRequest\FRS;

class GetEmployers extends \Drupal\ntca_acgi_api\ACGIRequest\GetEmployers implements FRSInterface
{
    function __construct(array $data = [])
    {
        $this->eSiteId = self::SITEID;
        parent::__construct($data);
    }
}