<?php

namespace Drupal\ntca_migration\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Remove H2 from body, but preserve the image inside
 * @MigrateProcessPlugin(
 *   id = "ntca_ruraltelecom_removeH2"
 * )
 */
class NTCARuraltelecomRemoveH2 extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $sBody = $value;

    $sReplacement = "";
    $sH2 = self::getTextWithin($sBody, "<h2>", "</h2>");
    if ($sH2 != "") {
      // try to find the image in there
      $sImg = self::getTextWithin($sH2, "<img", ">");
      if ($sImg != "") {
        $sReplacement = "<img". $sImg . ">";
      }
      $sFrom = "<h2>".$sH2."</h2>";
      $sBody = str_replace($sFrom, $sReplacement, $sBody);
      print "Removed first H2\n";
    }

    return($sBody);
  }

  /**
   * Gets text within a series of more narrowing substrings.
   */
  static function getTextAfter(&$textBlock, $stringParts) {
    if (!is_array($stringParts)) {
      $stringParts = array($stringParts);
    }
    $startIndex = 0;
    foreach ($stringParts as $token) {
      $index = strpos($textBlock, $token, $startIndex);
      if ($index !== false) {
        $startIndex = $index + strlen($token);
      } else {
        return(null);
      }
    }
    return(substr($textBlock, $startIndex));
  }

  static function getTextBefore(&$textBlock, $string) {
    $index = strpos($textBlock, $string);
    if ($index !== false) {
      return(substr($textBlock, 0, $index));
    }
    return(null);
  }

  /**
   * Gets to start string, finished with first instance of endString
   */
  static function getTextWithin(&$textBlock, $start, $endString) {
    $afterStartString = self::getTextAfter($textBlock, $start);
    $beforeEndString = self::getTextBefore($afterStartString, $endString);
    return($beforeEndString);
  }

  /**
   * Repeats getTextWithin, until there are no more matches
   * Returns array of elements (like list members)
   */
  static function getMultipleTextWithin(&$textBlock, $startString, $endString) {
    $retArr = array();
    $hasHits = true;
    $startIndex = 0;
    while ($hasHits) {
      $index = strpos($textBlock, $startString, $startIndex);
      if ($index !== false) {
        $tailSearchStart = $index + strlen($startString);
        $indexTail = strpos($textBlock, $endString, $tailSearchStart);
        if ($indexTail !== false) {
          $stringHit = substr($textBlock, $tailSearchStart, $indexTail - $tailSearchStart);
          array_push($retArr, $stringHit);
          $startIndex = $indexTail + strlen($endString);
        } else {
          $hasHits = false;
        }
      } else {
        $hasHits = false;
      }
    }
    return($retArr);
  }

}