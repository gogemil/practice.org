<?php

namespace Drupal\ntca_acgi_api\ACGIRequest\NTCA;

class GetEmployers extends \Drupal\ntca_acgi_api\ACGIRequest\GetEmployers implements NTCAInterface
{
  function __construct(array $data = [])
  {
    $this->eSiteId = self::SITEID;
    parent::__construct($data);
  }
}