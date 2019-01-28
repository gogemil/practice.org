<?php

namespace Drupal\ntca_migration\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Reformat date with dashes
 * @MigrateProcessPlugin(
 *   id = "ntca_simpledateconverter"
 * )
 */
class NTCASimpleDateConverter extends ProcessPluginBase {
  /**
  * {@inheritdoc}
  */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    preg_match('/(\d{4})-(\d{1,2})-(\d{1,2})\s\d{1,2}:\d{1,2}:\d{1,2}/', $value, $aMatches);
    if (sizeof($aMatches) != 0) {
      list($sFullMatch, $sYear, $sMon, $sDay) = $aMatches;
      $tTimestamp = mktime(0, 0, 0, $sMon, $sDay, $sYear);
      $sRetdate = date("Y-m-d", $tTimestamp);
      return($sRetdate);
    }

    // try m/d/Y h:m first
    preg_match('/(\d{1,2})\/(\d{1,2})\/(\d{4})\s\d{1,2}:\d{1,2}/', $value, $aMatches);
    if (sizeof($aMatches) == 0) {
      // then try m/d/Y
      preg_match('/(\d{1,2})\/(\d{1,2})\/(\d{2,4})/', $value, $aMatches);
      if (sizeof($aMatches) == 0) {
        return $value;
      }
    }

    list($sFullMatch, $sMon, $sDay, $sYear) = $aMatches;
    if ($sYear > 10 && $sYear <=99) {
      $sYear = 2000+$sYear;
    }
    $tTimestamp = mktime(0, 0, 0, $sMon, $sDay, $sYear);
    $sRetdate = date("Y-m-d", $tTimestamp);
    return($sRetdate);
  }
}