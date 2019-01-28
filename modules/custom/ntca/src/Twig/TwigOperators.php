<?php
namespace Drupal\ntca\Twig;

class TwigOperators extends \Twig_Extension {

  private $aExtensionsToLongExt = array(
    "pdf" => "Adobe Acrobat PDF",
    "xlsx" => "Microsoft Excel Spreadsheet",
    "xls" => "Microsoft Excel Spreadsheet",
    "doc" => "Microsoft Word Document",
    "docx" => "Microsoft Word Document",
  );

  public function getName() {
    return("ntca.twig_operators");
  }

  /**
   * @return array
   */
  public function getFunctions()
  {
    return [
      new \Twig_SimpleFunction('getFormattedIANDBFormTitleFile', [$this, 'getFormattedIANDBFormTitleFile'])
    ];
  }

  /**
   * Gets a node from a (menu) Url
   *
   * @param $sTitle - just a boring old title, plain text
   * @param $sComplexFileLink - something like:
   *    <span class="file file--mime-application-pdf file--application-pdf">
   *       <a href="http://stage.ntca.beaconfire.us/sites/default/files/benefits-forms/2017-06/DC2_AgreementTaxExempt_03_16.pdf" type="application/pdf; length=70468">DC2_AgreementTaxExempt_03_16.pdf</a>
   *    </span> (preview)
   *    OR A LONGER VERSION
   *    <span class="file file--mime-application-pdf file--application-pdf icon-before"><span class="file-icon"><span class="icon glyphicon glyphicon-file text-primary" aria-hidden="true"></span></span><span class="file-link"><a href="http://ntca.local/sites/default/files/benefits-forms/2017-06/DC2_AgreementTaxExempt_03_16.pdf" type="application/pdf; length=70468" title="" target="_blank" data-toggle="tooltip" data-placement="bottom" data-original-title="Open file in new window">DC2_AgreementTaxExempt_03_16.pdf</a></span><span class="file-size">68.82 KB</span></span>
   * @return $sFormattedBlock - block that looks like:
   *  sTitle, linked to the file
   *  [long type name - size]
   */
  public function getFormattedIANDBFormTitleFile($sTitle, $sFileLinkTitle)
  {
    $aFileParts = $this->getFileParts($sFileLinkTitle);
    $sReturn = <<<EOM
<a href="{$aFileParts['sFileLink']}">$sTitle</a>
<br/>
[<span class="{$aFileParts['sSpanClasses']}">{$aFileParts['sFileType']}, {$aFileParts['sFileLength']}</span>]
EOM;

    return($sReturn);
  }

  /**
   * @param $sFileLinkTitle - see above, something like:
   *  <span class="file file--mime-application-pdf file--application-pdf"> <a href="http://stage.ntca.beaconfire.us/sites/default/files/benefits-forms/2017-06/DC2_AgreementTaxExempt_03_16.pdf" type="application/pdf; length=70468">DC2_AgreementTaxExempt_03_16.pdf</a></span>
   * @return array of broken down parts (see $aResult)
   */
  function getFileParts($sFileLinkTitle) {
    $aResult = array(
      "sSpanClasses" => "",
      "sFileLink" => "",
      "sFileTitle" => "",
      "sFileType" => "", // formatted file type
      "sFileLength" => "", // something like 68KB
    );
    preg_match('/<span class="([^"]*)"/', $sFileLinkTitle, $aMatches);
    if (sizeof($aMatches) > 0) {
      $aResult['sSpanClasses'] = $aMatches[1];
    }
    preg_match('/<a href="([^"]*)"\s*type="([^;]*);\s*length=([^"]*)"/', $sFileLinkTitle, $aMatches);
    if (sizeof($aMatches) > 0) {
      $aResult['sFileLink'] = $aMatches[1];
      $aResult['sFileTypeRaw'] = $aMatches[2];
      $aResult['sFileLengthRaw'] = $aMatches[3];
    }

    preg_match('/<a[^>]*>([^<]*)</', $sFileLinkTitle, $aMatches);
    if (sizeof($aMatches) > 0) {
      $aResult['sFileTitle'] = $aMatches[1];
    }

    // now what do we have
    // additionally process extension, length
    if ($aResult['sFileLink'] != "") {
      $sRawExt = strtolower(pathinfo($aResult['sFileLink'], PATHINFO_EXTENSION));
      if (array_key_exists($sRawExt, $this->aExtensionsToLongExt)) {
        $aResult["sFileType"] = $this->aExtensionsToLongExt[$sRawExt];
      } else {
        $aResult["sFileType"] = strtoupper($sRawExt);
      }
    }

    if ($aResult['sFileLengthRaw'] != "") {
      if (intval($aResult['sFileLengthRaw']) > 0) {
        $aResult["sFileLength"] = $this->filesize_formatted($aResult['sFileLengthRaw']);
      }
    }
    return($aResult);
  }

  function filesize_formatted($iSizeBytes)
  {
    $units = array('KB', 'MB', 'GB', 'TB');
    $currUnit = '';
    while (count($units) > 0  &&  $iSizeBytes > 1024) {
      $currUnit = array_shift($units);
      $iSizeBytes /= 1024;
    }
    return ($iSizeBytes | 0) . $currUnit;
  }
}