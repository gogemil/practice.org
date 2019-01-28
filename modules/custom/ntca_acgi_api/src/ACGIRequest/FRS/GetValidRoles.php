<?php

namespace Drupal\ntca_acgi_api\ACGIRequest\FRS;

class GetValidRoles extends \Drupal\ntca_acgi_api\ACGIRequest\GetValidRoles implements FRSInterface
{
  function __construct(array $data = [])
  {
    $this->eSiteId = self::SITEID;
    parent::__construct($data);
  }
}