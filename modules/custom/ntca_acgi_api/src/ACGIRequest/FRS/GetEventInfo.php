<?php

namespace Drupal\ntca_acgi_api\ACGIRequest\FRS;

class GetEventInfo extends \Drupal\ntca_acgi_api\ACGIRequest\GetEventInfo implements FRSInterface
{
  function __construct(array $data = [])
  {
    $this->eSiteId = self::SITEID;
    parent::__construct($data);
  }
}