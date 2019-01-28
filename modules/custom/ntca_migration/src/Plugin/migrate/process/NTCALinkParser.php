<?php

namespace Drupal\ntca_migration\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\media_entity\Entity\Media;
use Drupal\file\Entity\File;
use SimpleXMLElement;
use Exception;

/**
 * Reformat date with dashes
 * @MigrateProcessPlugin(
 *   id = "ntca_linkparser"
 * )
 * @code
 *   field_tags:
 *     miro: default1
 *     miro3: default3
 * @endcode
 */
class NTCALinkParser extends ProcessPluginBase {
  // TODO: move these values into the yml file
  private $sURLForRelativeImageLinks = 'http://www.ntca.org'; // no terminating slash

  // internal workings, no real reason to touch it
  private $iMaxLinksPerBlock = 5000; // to prevent infinite loops

  // some exceptions to make code cleaner
//  const FINDIMAGE_TOOMANYFOUND = 1;
//  const GETIMAGE_CANTGETIT = 2;
  private $logFile = "/tmp/linkimport.log";

  // will be overridable in the future, params passed to this method are:
  // $aLinkParts, keys are:
  //  sFullLink - pretty clear, right?
  //  aLinkAttributes (again, simple key/val pairs)
  //  sLinkBody - text or value of the link
  // Needs to return a single string, containing the corrected (or blown away) replacement link string
  private $linkReplacementMethod = 'self::ProcessNTCALink';

  public function log($sMessage) {
    if ($this->logFile == "") {
      print $sMessage;
    } else {
      file_put_contents($this->logFile, $sMessage."\n", FILE_APPEND);
    }
  }

  /**
   * Entry point for a Plugin, this is what gets kicked off
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // value is an incoming
    // if you need other values from the yml file, go to $this->configuration, which is a pure key/value array

//    print_r($row);

    $aDestinationInfo = $row->getIdMap();
    $sSourceId = $aDestinationInfo['sourceid1'];
    $this->log("===================================");
    $this->log("SOURCE ID = $sSourceId");

//    print "VALUE\n";
//    print $value."\n";
//    print "===\n";

    // TODO: try to parse with an actual XML parser first
    // TODO: Only if that's too dirty to process, go to the RegExp processing

    // HTML input judged too dirty to process with pure XML reader, go to regexp
    $sNewBody = $this->processBodyWithRegexp($value);
    return($sNewBody);
  }

  /**
   * Takes in the HTML block, processes links, returns HTML block
   * Note: most of the decisions about what happens to these links has been offloaded into $this->linkReplacementMethod
   * @param $sHTMLBlock - all the input HTML, presumed not safe for XML parsing
   * @return $aReturn, keys are:
   *  bSuccess = true/false
   *  sProcessedBody - string containing the final output
   */
  function processBodyWithRegexp($sHTMLBlock) {
    $bFound = true;
    $sInspectedHTMLPart = ""; // as we go along, we'll put content here that we've already looked at, and keep appending to it, while the $sHTMLBlock will grow smaller
    $iCount = 0;

    while ($bFound && $iCount++ < $this->iMaxLinksPerBlock) {
      $aResponse = $this->getNextLink($sHTMLBlock);
      if ($aResponse == null) {
        $bFound = false;
        $sInspectedHTMLPart .= $sHTMLBlock; // we're done, just append the rest of it
      } else {
        $bFound = true;
        // take the new block and keep going, until there are no images left
        $sInspectedHTMLPart .= $aResponse['preHTML'];
        $sHTMLBlock = $aResponse['postHTML'];

        $aAttributes = $this->parseTagAttributes($aResponse["sRawLinkTag"], $aResponse["sTagInnards"]);

        // then we take the results of the tag and process it, do the substitution
        $this->log("\tFrom: ".$aResponse["sRawLinkTag"]);
        $sReplacementString = call_user_func($this->linkReplacementMethod, $aResponse["sRawLinkTag"], $aAttributes, $aResponse["sTagBody"]);
        $this->log("\tTo  : ".$sReplacementString."\n");
        $sInspectedHTMLPart .= $sReplacementString;
      }
    }

    return($sInspectedHTMLPart);
  }

  /**
   * Takes in the HTML block, and a temporary token (replacement) string,
   *   finds the first link tag,
   *   passes three HTML parts back (all HTML before image, image HTML, all HTML after the first image),
   *   and some processed info about this image back (see return)
   * @param $sHTMLBlock
   * @return array|null, keys are:
   *   preHTML - HTML preceding the link
   *   postHTML - all the HTML after the found link
   *   sRawLinkTag - just in case we need we didn't get everything from aAttributes
   *   sTagInnards - containing all the attributes from the link
   *   sTagBody - HTML inside the of the link
   */
  function getNextLink($sHTMLBlock) {

    preg_match('/<a([^>]*)>(.*?)<\/a>/i', $sHTMLBlock, $aMatches);
    if (sizeof($aMatches) == 0) {
      return(null);
    }

    $sRawTag = $aMatches[0];
    // now find it in the body, rip it out
    $sPos = strpos($sHTMLBlock, $sRawTag);
    if ($sPos !== FALSE) {
      $preHTML = substr($sHTMLBlock, 0, $sPos);
      $postHTML = substr($sHTMLBlock, $sPos+strlen($sRawTag));
    } else { // given the find with preg_match, this should not happen!
      throw (new Exception("Found tag with preg_match, can't find it with strpos. [".$aMatches[0]."]"));
    }

    return(array(
      "preHTML" => $preHTML,
      "postHTML" => $postHTML,
      'sRawLinkTag' => $sRawTag,
      'sTagInnards' => $aMatches[1], // so we don't have to do this parsing again
      'sTagBody' => $aMatches[2]
    ));
  }

  /**
   * Get all the attributes out of the tag
   * @param $sRawTag
   * @param $sTagInnards
   * @return $aNormalizedAttributes - a clean key => value set here, with all the keys being lowercased
   */
  function parseTagAttributes($sRawTag, $sTagInnards) {

    // get all the attributes out of the src tag
    if (substr($sTagInnards, strlen($sTagInnards)-1, 1) != "/") {
      // add the tag terminating / if needed
      $sTagInnards .= "/";
    }

    $aNormalizedAttributes = array();
    try {
      $sElement = "<element ".$sTagInnards.">";
      $oElement = new SimpleXMLElement($sElement);
      $aAttributes = current((array) $oElement);
      // normalize all the attributes
      foreach ($aAttributes as $key => $value) {
        $aNormalizedAttributes[strtolower($key)] = $value;
      }
    } catch (Exception $ex) {
      $aImageAttributes = array(); // can't do it!
      $this->log("\tTag parser issue: $sElement\n");
    }

    return($aNormalizedAttributes);
  }

  /**
   * Here's what we see:
   *  Mailto's - keep all
   *  Local links - remove altogether (but keep the body)
   *  External links - keeps
   *  PDF links - keep global count, redirect to a single new point the client will fill themselves
   * @param $sFullLink - pretty clear, right?
   * @param $aLinkAttributes - simple key/val pairs
   * @param $sLinkBody - text or value of the link
   * @return $sReplacement - replacement text for that link
   */
  static function ProcessNTCALink($sFullLink, $aLinkAttributes, $sLinkBody) {
    // CONSTANTS:
    $aOldSiteURLs = array(
      "https://www.ntca.org",
      "http://www.ntca.org",
      "https://ntca.org",
      "http://ntca.org"
    );
    $pdfNewStoragePath = "/sites/default/files/legacy/documents/pdf/pressreleases/"; // will be appended to the beginning of any pdf file
    // note: the following will be used to just change the path
    $aPDFReplacement = array(
      "from" => array('images/stories/'),
      "to" => array('/sites/default/files/legacy/images/stories/')
    );

    // Rule 1: if you don't have an href, return as is
    if (!array_key_exists("href", $aLinkAttributes)) {
      return($sFullLink);
    }

    $sHref = $aLinkAttributes["href"];
    // Rule 2: mailto: return as is
    if (strpos($sHref, "mailto:") === 0) {
      return($sFullLink);
    }

    // Rule 3: starts with #
    if (strpos($sHref, "#") === 0) {
      return($sFullLink);
    }

    // Rule 4: external site?
    if (preg_match("/https?:\/\//i", $sHref) && !preg_match("/^https:\/\/www\.ntca\.org/i", $sHref)) {
      $bFoundOldSite = false;
      foreach ($aOldSiteURLs as $sOldSiteUrl) {
        if (strpos($sHref, $sOldSiteUrl) !== false) {
          $bFoundOldSite = true;
          break;
        }
      }

      if (!$bFoundOldSite) {
        return($sFullLink);
      }
    }

    // Rule 5: local pdf link?
    // Let's convert full URL to partial, expected image URL
    $sOriginalHref = $sHref; // in case we overwrite it somehow (https -> images/stories)
    if (preg_match("/.pdf$/i", $sHref)) {
//      print "OLD HREF: $sHref\n";
      $sHref = str_replace("https://www.ntca.org/images/stories/", "images/stories/", $sHref);
      $newHref = str_replace($aPDFReplacement["from"], $aPDFReplacement["to"], $sHref);
//      print "NEW HREF: $newHref\n";
//      print "---";
      $sFullLink = str_replace($sOriginalHref, $newHref, $sFullLink);
      return($sFullLink);
    }

    return(" ".$sLinkBody." ");
  }
}
