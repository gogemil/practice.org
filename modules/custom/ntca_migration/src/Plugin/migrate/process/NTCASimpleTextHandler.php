<?php

namespace Drupal\ntca_migration\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Reformat date with dashes
 * @MigrateProcessPlugin(
 *   id = "ntca_simpletexthandler"
 * )
 */
class NTCASimpleTextHandler extends ProcessPluginBase {

  /**
  * {@inheritdoc}
  */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    if (array_key_exists("method", $this->configuration)) {
      $method = $this->configuration["method"];
    }

    if ($method == "strip_tags") {
      return ($this->__method_strip_tags($value, $migrate_executable, $row, $destination_property));
    } else {
      return("");
    }
  }

  function __method_strip_tags($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (array_key_exists("tags", $this->configuration)) {
      $aTags = $this->configuration["tags"];
    }

    if (!is_array($aTags) || sizeof($aTags) == 0) {
      return($value);
    }

    foreach ($aTags as $sTag) {
      $value = preg_replace("/<\\/?" . $tag . "(.|\\s)*?>/", '', $value);
    }

    return($value);
  }

}