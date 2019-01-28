<?php

namespace Drupal\ntca_acgi_api\ACGIRequest\NTCA;

class GetEventInfo extends \Drupal\ntca_acgi_api\ACGIRequest\GetEventInfo implements NTCAInterface
{
  function __construct(array $data = [])
  {
    $this->eSiteId = self::SITEID;
    parent::__construct($data);
  }
}