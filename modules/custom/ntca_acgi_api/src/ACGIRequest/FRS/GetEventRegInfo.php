<?php

namespace Drupal\ntca_acgi_api\ACGIRequest\FRS;

class GetEventRegInfo extends \Drupal\ntca_acgi_api\ACGIRequest\GetEventRegInfo implements FRSInterface
{
  function __construct(array $data = [])
  {
    $this->eSiteId = self::SITEID;
    parent::__construct($data);
  }
}