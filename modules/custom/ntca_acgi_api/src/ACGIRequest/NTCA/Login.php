<?php

namespace Drupal\ntca_acgi_api\ACGIRequest\NTCA;

class Login extends \Drupal\ntca_acgi_api\ACGIRequest\Login implements NTCAInterface
{
  function __construct(array $data = [])
  {
    $this->eSiteId = self::SITEID;
    parent::__construct($data);
  }
}