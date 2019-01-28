<?php

namespace Drupal\ntca_migration\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\views\Plugin\views\argument\YearDate;

/**
 * @MigrateProcessPlugin(
 *   id = "ntca_resolveems"
 * )
 */
class NTCAResolveEMs extends ProcessPluginBase {

  /**
  * {@inheritdoc}
  */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    $value = str_replace("</em><em>", "", $value);
    return($value);

  }

}