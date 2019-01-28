<?php

$aTitles = array(
  "pressreleases" => array(),
  "exparte" => array(),
  "federalfiling" => array(),
);
$aBodies = array(
  "pressreleases" => array(),
  "exparte" => array(),
  "federalfiling" => array(),
);

$aExports = array(
  "2014 Federal Filings" => "federalfiling",
  "2015 Federal FIlings" => "federalfiling",
  "2016 Federal Filings" => "federalfiling",
  "2017 Federal Filings" => "federalfiling",
  "2014 Press Releases" => "pressreleases",
  "2015 Press Releases" => "pressreleases",
  "2016 Press Releases" => "pressreleases",
  "2017 Press Releases" => "pressreleases",
  "2018 Press Releases" => "pressreleases",
  "Ex Parte Letters 2014" => "exparte",
  "Ex Parte Letters 2015" => "exparte",
  "Ex Parte Letters 2016" => "exparte",
  "Ex Parte Letters 2017" => "exparte",
);
$aExportFiles = array(
  "pressreleases" => dirname(__FILE__)."/"."../../../../sites/default/files/import/BROKENOUTFILE_pressreleases.txt",
  "exparte" => dirname(__FILE__)."/"."../../../../sites/default/files/import/BROKENOUTFILE_exparte.txt",
  "federalfiling" => dirname(__FILE__)."/"."../../../../sites/default/files/import/BROKENOUTFILE_federalfiling.txt",
);
$aExportFileHandles = array();
// open all files, establish file handles
foreach ($aExportFiles as $key => $sFilePath) {
  $aExportFileHandles[$key] = fopen($sFilePath, "w");
}
$iCounts = array();
foreach ($aExportFiles as $key => $junk) {
  $iCounts[$key] = 0;
}
$sFilePath = dirname(__FILE__)."/"."../../../../sites/default/files/import/20180103/fullexport_2014_to_2018.csv";

$hOriginal = fopen($sFilePath, "r");

// deal with headers
$aHeaderRow = fgetcsv($hOriginal);
$aHeaderRow[0] = "Title";
$aColumnNameToId = array();
$iHeaderCols = sizeof($aHeaderRow);
for ($i=0; $i<$iHeaderCols; $i++) {
  $sColumnName = $aHeaderRow[$i];
  if (array_key_exists($sColumnName, $aColumnNameToId)) {
    throw new Exception("Duplicate column name ($sColumnName), ambiguous CSV file!");
  }
  $aColumnNameToId[trim($sColumnName)] = $i;
}

// write headers to all output files
foreach ($aExportFileHandles as $key => $hOutput) {
  fputcsv($hOutput, $aHeaderRow, "\t", '"');
}

print_r($aColumnNameToId);
$aTypes = array();

$iRow = 0;
while ($aRow = fgetcsv($hOriginal)) {
  $iRow++;

  $type = trim($aRow[$aColumnNameToId['Category']]);
  if (array_key_exists($type, $aExports)) {
    $sExportLocation = $aExports[$type]; // something like "pressrelease"
    $hOutput = $aExportFileHandles[$sExportLocation];

    // remember title
    $sTitle = trim($aRow[$aColumnNameToId['Title']]);
    $sDate = trim($aRow[$aColumnNameToId['Created Date']]);
    $sBody = trim($aRow[$aColumnNameToId['Content']]);
    $bDuplicate = false;
    if (!array_key_exists($sTitle, $aTitles[$sExportLocation])) {
      $aTitles[$sExportLocation][$sTitle] = $sDate;
      $aBodies[$sExportLocation][$sTitle] = $sBody;
    } else {
      print "Category $sExportLocation DUPLICATE TITLE: $sTitle\n";
      print "\tOriginal Date: ".$aTitles[$sExportLocation][$sTitle]."\n";
      print "\tDate: $sDate\n";
      if ($sBody != $aBodies[$sExportLocation][$sTitle]) {
        print "\tBodies DIFFERENT!\n";
      } else {
        $bDuplicate = true;
        print "\tBodies SAME\n";
      }
    }
    if (!$bDuplicate) {
      $iCounts[$sExportLocation] ++;
      fputcsv($hOutput, $aRow, "\t", '"');
    }
  }
//  $aTypes[$type] = 1;
}
//ksort($aTypes);

print "Total Lines: $iRow\n";
//print "All types:\n";
//print_r(array_keys($aTypes));
print_r($iCounts);

fclose($hOriginal);
// close the rest
foreach ($aExportFileHandles as $key => $hFile) {
  fclose($hFile);
}

