<?php

namespace Drupal\ntca_acgi_api\ACGIRequest\NTCA;

class GetEventRegInfo extends \Drupal\ntca_acgi_api\ACGIRequest\GetEventRegInfo implements NTCAInterface
{
  function __construct(array $data = [])
  {
    $this->eSiteId = self::SITEID;
    parent::__construct($data);
  }
}