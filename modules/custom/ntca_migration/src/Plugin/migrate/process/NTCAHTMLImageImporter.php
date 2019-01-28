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
 *   id = "ntca_htmlimageimporter"
 * )
 */
class NTCAHTMLImageImporter extends ProcessPluginBase {
  // TODO: move these values into the yml file
  private $sURLForRelativeImageLinks = 'http://www.ntca.org'; // no terminating slash

  private $sMediaImageMachinename = 'image'; // go to /admin/content/media, look up Type pulldown, see the value for image
  private $sEmbedSize = 'original'; // todo: use this in getEmbedStringReplacement()
  private $drupalFileTargetLocation = 'legacy/images/embedded/'; // should end with a slash, here's how drupal will use this: save to 'public://'.$this->drupalFileTargetLocation.$filename
  private $eFinalStatus = Media::PUBLISHED;
  private $bSaveMediaInLibrary = false; // default

  // internal workings, no real reason to touch it
  private $iMaxImagesPerHTMLBlock = 100; // to prevent infinite loops

  // some exceptions to make code cleaner
  const FINDIMAGE_TOOMANYFOUND = 1;
  const GETIMAGE_CANTGETIT = 2;
  const FILESAVEDATA_PROBLEM = 3;

  private $aMediaLookupTitleToMediaId = array(); // we're going to put the Media Items already read/constructed in here, key is the title string, value is the Media Id
  private $logFile = "/tmp/imageimport.log";

  // will be overridable in the future, params passed to this method are:
  // $fullImageTag
  private $badImageReplacementMethod = 'self::getBadImageReplacement_addImportDataAttribute'; // add data-import="error" into the tag

//  public function __construct() {
//    $this->log("New Instance");
//    parent::__construct();
//  }

  public function log($sMessage) {
    if ($this->logFile == "") {
      print $sMessage;
    } else {
      file_put_contents($this->logFile, $sMessage."\n", FILE_APPEND);
    }
  }

  /**
   * Examines $value, finds any <img> tag, rips it out, downloads the image into the local media library, and embeds the new media entity reference into the HTML
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (array_key_exists("save_media_in_library", $this->configuration)) {
      $this->bSaveMediaInLibrary = $this->configuration["save_media_in_library"] == "true";
    }

    $aDestinationInfo = $row->getIdMap();
    $sSourceId = $aDestinationInfo['sourceid1'];
    $this->log("===================================");
    $this->log("SOURCE ID = $sSourceId");

    $bFound = true;
    $sInspectedHTMLPart = ""; // as we go along, we'll put content here that we've already looked at, and keep appending to it, while the $sHTMLBlock will grow smaller
    $sHTMLBlock = $value; // value is an incoming block of HTML
    $iCount = 0;
    while ($bFound && $iCount++ < $this->iMaxImagesPerHTMLBlock) {

      $aResponse = $this->getNextImage($sHTMLBlock);
      if ($aResponse == null) {
        $bFound = false;
        $sInspectedHTMLPart .= $sHTMLBlock; // we're done, just append the rest of it
      } else {
        $bFound = true;
        // take the new block and keep going, until there are no images left
        $sInspectedHTMLPart .= $aResponse['preHTML'];
        $sHTMLBlock = $aResponse['postHTML'];

        $aImageAttributes = $this->parseImageAttributes($aResponse["sRawImageTag"], $aResponse["sTagInnards"]);

        // then we take the results of the tag and process it, do the substitution
        $aStatus = $this->loadOrGetExistingImage($aImageAttributes);
        if ($aStatus['bSuccess'] == true && $aStatus['oMediaEntityImage'] instanceof Media) {
          $sEmbedString = $this->getEmbedStringReplacement($aStatus['oMediaEntityImage'], $aStatus['aRawAttributes']['align']);
          $sInspectedHTMLPart .= $sEmbedString;
        } else {
          // ok, some problems, we can't get a media image
          // write out the error given, null out the image string so we don't loop at it infinitely
          $sEmbedString = call_user_func($this->badImageReplacementMethod, $aResponse["sRawImageTag"]);
          $sInspectedHTMLPart .= $sEmbedString;
        }

        // either way, print the message
        $this->log("\tRESULT: Image $iCount : ".$aStatus['sErrorMessage']);
      }
    }

    return($sInspectedHTMLPart);
  }

  /**
   * Takes in the HTML block, and a temporary token (replacement) string, finds the first img tag, passes three HTML parts back, and some processed info about this image back (see return)
   * @param $sHTMLBlock
   * @return array|null, keys are:
   *   preHTML - HTML preceding the image
   *   postHTML - all the HTML after the found image
   *   aImageAttributes - all the attributes from the image
   *   sRawImageTag - just in case we need we didn't get everything from aImageAttributes
   */
  function getNextImage($sHTMLBlock) {

    preg_match('/<img([^>]*)>/i', $sHTMLBlock, $aMatches);
    if (sizeof($aMatches) == 0) {
      return(null);
    }

    $sRawImgTag = $aMatches[0];
    // now find it in the body, rip it out
    $sPos = strpos($sHTMLBlock, $sRawImgTag);
    if ($sPos !== FALSE) {
      $preHTML = substr($sHTMLBlock, 0, $sPos);
      $postHTML = substr($sHTMLBlock, $sPos+strlen($sRawImgTag));
    } else { // given the find with preg_match, this should not happen!
      throw Exception("Found img with preg_match, can't find it with strpos. [".$aMatches[0]."]");
    }

    return(array(
      "preHTML" => $preHTML,
      "postHTML" => $postHTML,
      'sRawImageTag' => $sRawImgTag,
      'sTagInnards' => $aMatches[1] // so we don't have to do this parsing again
    ));
  }

  /**
   * Get all the attributes out of the img tag
   * @param $sRawImageTag
   * @param $sImgTagInnards
   * @return $aNormalizedAttributes - a clean key => value set here, with all the keys being lowercased
   */
  function parseImageAttributes($sRawImageTag, $sImgTagInnards) {

    // get all the attributes out of the src tag
    if (substr($sImgTagInnards, strlen($sImgTagInnards)-1, 1) != "/") {
      // add the tag terminating / if needed
      $sImgTagInnards .= "/";
    }

    $aNormalizedAttributes = array();
    try {
      $sElement = "<element ".$sImgTagInnards.">";
      $oElement = new SimpleXMLElement($sElement);
      $aAttributes = current((array) $oElement);
      // normalize all the attributes
      foreach ($aAttributes as $key => $value) {
        $aNormalizedAttributes[strtolower($key)] = $value;
      }
    } catch (Exception $ex) {
      $aImageAttributes = array(); // can't do it!
      $this->log("\tImage parser issue: $sElement\n");
    }

    return($aNormalizedAttributes);
  }

  /**
   * Step 1: normalize image attributes into an actual image name that will be visible in the Media Library, and by which we will search
   * Step 2: look for the image existing already
   * Step 3: if found, return the Media object
   * Step 4: if NOT found, create it, return the newly created Media object
   * @param $aImageAttributes
   * @return $aReturn, keys are
   *  bSuccess : boolean
   *  oMediaEntityImage : instance of Media object (has to exist if bSuccess if true)
   *  aRawAttributes: raw image attributes
   *  sErrorMessage: string describing what the problem is, if bSuccess is false
   */
  private function loadOrGetExistingImage($aImageAttributes) {
    $aReturn = array(
      'bSuccess' => false,
      'sErrorMessage' => "Undetermined error"
    );
    $aNormalizedAttributes = $this->getCleanedUpTitleAltSrc($aImageAttributes);

    $this->log("\t---------\n\tNORMALIZED ATTS\n");
    $this->log("\tSRC  : ".$aNormalizedAttributes['src']);
    $this->log("\tTITLE: ".$aNormalizedAttributes['title']);
    $this->log("\tALT  : ".$aNormalizedAttributes['alt']);
    $this->log("\t---\n");

    // just in case we need this upwards
    $aReturn['aRawAttributes'] = $aNormalizedAttributes;

    // check if src even exists? (then the title will exist too, because that's the fallback)
    if ($aNormalizedAttributes['src'] == '') {
      $aReturn['sErrorMessage'] = 'Image does not have a src attribute';
      return($aReturn);
    }

    // we have the src/title now, so keep going
    $oMediaEntityImage = null;
    try {
      $oMediaEntityImage = $this->getExistingImage($aNormalizedAttributes['title']);
    } catch (Exception $ex) {
      if ($ex->getCode() == self::FINDIMAGE_TOOMANYFOUND) {
        $aReturn['sErrorMessage'] = 'More than one media element by that title already exists (title = '.$aNormalizedAttributes['title'].').';
        return($aReturn);
      }
    }

    $bImageFound = true;

    if ($oMediaEntityImage == null) {
      $this->log("\tImage not found, downloading and creating now...");
      $bImageFound = false;
      // create a new one, nothing found
      try {
        $oMediaEntityImage = $this->createNewImageLocally($aNormalizedAttributes);
      } catch (Exception $ex) {
        if ($ex->getCode() == self::GETIMAGE_CANTGETIT) {
          $aReturn['sErrorMessage'] = 'Cannot download the image (URL = '.$aNormalizedAttributes['src'].').';
          return($aReturn);
        }
      }
    } else {
      $this->log("\tImage found.");
    }

    if ($oMediaEntityImage instanceof Media) {
      $this->aMediaLookupTitleToMediaId[$aNormalizedAttributes['title']] = $oMediaEntityImage->id();
      $this->log("\tHave image.");
      $aReturn['bSuccess'] = true;
      $aReturn['oMediaEntityImage'] = $oMediaEntityImage;
      if ($bImageFound) {
        $aReturn['sErrorMessage'] = 'Existing image found (URL = '.$aNormalizedAttributes['src'].')';
      } else {
        $aReturn['sErrorMessage'] = 'New image created (URL = '.$aNormalizedAttributes['src'].')';
      }
      return($aReturn);
    }
    $this->log("\tDon't have image.");

    $aReturn['sErrorMessage'] = 'Undetermined error creating image (title = '.$aNormalizedAttributes['title'].').';
    return($aReturn);
  }

  private function getExistingImage($sTitle) {
    $oMediaStorageEntity = \Drupal::entityTypeManager()->getStorage('media');

    // shortcut with the lookup by name
    if (array_key_exists($sTitle, $this->aMediaLookupTitleToMediaId)) {
      $iMediaEntityId = $this->aMediaLookupTitleToMediaId[$sTitle];
      $oMediaEntity = $oMediaStorageEntity->load($iMediaEntityId);
      return($oMediaEntity);
    }

    // try the actual title now
    $aMediaEntities = $oMediaStorageEntity->loadByProperties(['name' => $sTitle]);

    // note: keys of that array are entity id's, and values are Drupal\media_entity\Entity\Media objects
    $iNumberEntitiesFound = sizeof($aMediaEntities);
    if ($iNumberEntitiesFound > 1) {
      throw new Exception('Too many entities', self::FINDIMAGE_TOOMANYFOUND);
    } else if ($iNumberEntitiesFound == 1) {
      $oMediaEntity = array_shift($aMediaEntities);
      return ($oMediaEntity);
    } else {
      return (null);
    }
  }

  private function createNewImageLocally($aNormalizedAttributes) {
    $sFilePath = $aNormalizedAttributes['src']; // we'll read it from here
    $aFilenameParts = pathinfo($sFilePath);
    $sFilename = $aFilenameParts['filename'].".".$aFilenameParts['extension'];
    $sTitle = $aNormalizedAttributes['title'];
    $sAltText = $aNormalizedAttributes['alt'];

    $sImageData = file_get_contents($sFilePath);
    if ($sImageData === FALSE) {
      throw new Exception('Image inaccessible', self::GETIMAGE_CANTGETIT);
    }

    $sTargetLocation = 'public://'.$this->drupalFileTargetLocation.$sFilename;
    $this->log("\tCreate new image, TARGET LOCATION: $sTargetLocation");
    $oFile = file_save_data($sImageData, $sTargetLocation, FILE_EXISTS_REPLACE);

    if (is_bool($oFile)) {
      throw new Exception('Cannot Save Image', self::FILESAVEDATA_PROBLEM);
    }

    $oMediaImage = Media::create([
      'bundle' => 'image',
      'name' => $sTitle,
      'status' => $this->eFinalStatus, // published or not?
      'field_media_in_library' => $this->bSaveMediaInLibrary,
      $this->sMediaImageMachinename => [
        'target_id' => $oFile->id(),
        'alt' => $sAltText,
        'title' => $sTitle
      ],
    ]);
    $oMediaImage->save();
    return($oMediaImage);
  }

  /**
   * Make sure there are
   * @param $aImageAttributes
   * @return array, keys are:
   *  src - here, we just make sure src exists, and if it's relative, prepend with old site's site url
   *  title - will look at title, alt, then will normalize the full URL, prepend 'IMPORTED: ' to it
   *  alt - will take alt, title, and nothing, in that order
   *  align - if exists on the image
   */
  private function getCleanedUpTitleAltSrc($aImageAttributes) {
    $aReturn = array(
      "src" => "",
      "title" => "",
      "alt" => "",
      "align" => "",
    );

    // src first
    if (array_key_exists('src', $aImageAttributes)) {
      $sSrc = $aImageAttributes['src'];
      if (preg_match('/^https{0,1}:/i', $sSrc)) {
        $aReturn['src'] = $sSrc;
      } else {
        if (substr($sSrc, 0, 1) != "/") {
          $sSrc = "/" . $sSrc;
        }
        $aReturn['src'] = $this->sURLForRelativeImageLinks . $sSrc;
      }
    }

    // title next
    if (array_key_exists('title', $aImageAttributes)) {
      $aReturn['title'] = $aImageAttributes['title'];
    } else if (array_key_exists('alt', $aImageAttributes)) {
      $aReturn['title'] = $aImageAttributes['alt'];
    } else {
      $aReturn['title'] = "IMPORTED: ".preg_replace('/[^A-Za-z0-9]/', '_', $aReturn['src']);
    }
    $aReturn['title'] = trim($aReturn['title']);

    // alt finally
    if (array_key_exists('alt', $aImageAttributes)) {
      $aReturn['alt'] = $aImageAttributes['alt'];
    } else if (array_key_exists('title', $aImageAttributes)) {
      $aReturn['alt'] = $aImageAttributes['title'];
    }
    $aReturn['alt'] = trim($aReturn['alt']);

    if (array_key_exists('align', $aImageAttributes)) {
      $aReturn['align'] = $aImageAttributes['align'];
    }

    return($aReturn);
  }

  private function getEmbedStringReplacement(Media $oMediaEntityImage, $sAlignment = "") {
    if ($sAlignment != "") {
      $sAlignment = " data-align='$sAlignment'";
    }
    $sUUID = $oMediaEntityImage->uuid();
    $sReturn = <<<EOE
<drupal-entity data-embed-button="media_browser" $sAlignment data-entity-embed-display="view_mode:media.embedded_original" data-entity-type="media" data-entity-uuid="$sUUID"></drupal-entity>
EOE;

    return($sReturn);

  }

  /**
   * image tag was found, we have the sFullImageTag
   * @param $sFullImageTag - full image tag, should be <img ...>
   * @return $sReturn
   */
  public static function getBadImageReplacement_addImportDataAttribute($sFullImageTag) {
    // implementation: find the first space, add data-import="error" in there
    $iPositionOfFirstSpace = strpos($sFullImageTag, " ");
    if ($iPositionOfFirstSpace !== FALSE) {
      $sReturn =
        substr($sFullImageTag, 0, $iPositionOfFirstSpace).
        ' data-import="error" '.
        substr($sFullImageTag, $iPositionOfFirstSpace+1);
      return($sReturn);
    }
    // just in case, return the original tag
    return($sFullImageTag);
  }
}
