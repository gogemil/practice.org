<?php

namespace Drupal\ntca_acgi_api\ACGIRequest\FRS;

class Login extends \Drupal\ntca_acgi_api\ACGIRequest\Login implements FRSInterface
{
  function __construct(array $data = [])
  {
    $this->eSiteId = self::SITEID;
    parent::__construct($data);
  }
}