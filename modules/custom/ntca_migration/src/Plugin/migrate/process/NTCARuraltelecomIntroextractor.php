<?php

namespace Drupal\ntca_migration\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Reformat date with dashes
 * @MigrateProcessPlugin(
 *   id = "ntca_ruraltelecom_introextrator"
 * )
 */
class NTCARuraltelecomIntroextractor extends ProcessPluginBase {
  private $iParagraphLongerThanChars = 200;

  /**
   * Find the first p tag, stipped of multiple white space, stripped of tags, with more than 200 chars, then take the whole paragraph.
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $aParagraphs = self::getMultipleTextWithin($value, "<p", "</p>");

    foreach ($aParagraphs as $sParagraph) {
      // find the close of that p tag first, trim that out
      $iPos = strpos($sParagraph, ">");
      if ($iPos === false) {
        continue;
      }
      $sParagraph = substr($sParagraph, $iPos+1);
      // now strip tags
      $sParagraph = strip_tags($sParagraph);
      if (strlen($sParagraph) > $this->iParagraphLongerThanChars) {
//        print "===\n\nFound Paragraph\n\n$sParagraph\n\n";
        return($sParagraph);
      }
    }

    return("");
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