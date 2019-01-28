<?php

namespace Drupal\ntca_migration\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Come up with a new alias, based on the Title
 * @MigrateProcessPlugin(
 *   id = "ntca_ruraltelecom_alias"
 * )
 */
class NTCARuraltelecomAlias extends ProcessPluginBase {

  private $sPathStart = "/ruraliscool/publications/rural-telecom-magazine/";

  /**
   * $value is the incoming title, strip of any tags, and normalize it, the prepend with $sPathStart
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = strip_tags($value);
    $value = preg_replace("/[^a-zA-Z0-9\-\s]/", "", $value);
    $value = preg_replace("/\s/", "-", $value);
    $value = strtolower($value);

    return($this->sPathStart.$value);
  }

}