<?php

namespace Drupal\ntca_acgi_api\ACGIRequest\NTCA;

class GetValidRoles extends \Drupal\ntca_acgi_api\ACGIRequest\GetValidRoles implements NTCAInterface
{
  function __construct(array $data = [])
  {
    $this->eSiteId = self::SITEID;
    parent::__construct($data);
  }
}