<?php 

//
// Global Variables
//
$options = array();
include 'version.inc';
include 'parser/utilities.inc';
include "analyser/utilities.inc";
/**
 * @var DOMDocument $dom
 */
$dom = null;
/**
 * @var DOMXpath $xpath
 */
$xpath = null;
/**
 * @var messageStore $messages
 */
$messages = null;

$spareRoom = str_repeat('*', 1024 * 1024);
$size = 500;

//
// Argument processing
//
if (isset($argc)) {
  if ( $argc > 1 ) { // run in debug mode, probably
    $options['blazon'] = implode(' ', array_slice($argv,1));
  } else {
  if (file_exists('debug.inc')) include 'debug.inc';
  }
}

// Process arguments
$ar = null;
$size = null;
// For backwards compatibility we support argument in GET, but prefer POST
if (isset($_SERVER["REQUEST_METHOD"]) && $_SERVER["REQUEST_METHOD"] == "POST") {
  if (isset($_FILES['blazonfile']) && ($_FILES['blazonfile']['name'] != "")) {
    $fileName = $_FILES['blazonfile']['name'];
    $fileSize = $_FILES['blazonfile']['size'];
    $fileTmpName  = $_FILES['blazonfile']['tmp_name'];
   // $fileType = $_FILES['blazonfile']['type']; // not currently used
    if (preg_match('/.txt$/i', $fileName) && $fileSize < 1000000) {
      $options['blazon'] = file_get_contents($fileTmpName);
    } 
  } else {    
      if (isset($_POST['blazon'])) $options['blazon'] = strip_tags(trim($_POST['blazon']));
  }
  if (isset($_POST['outputformat'])) $options['outputFormat'] = strip_tags ($_POST['outputformat']);;
  if (isset($_POST['saveformat'])) $options['saveFormat'] = strip_tags ($_POST['saveformat']);;
  if (isset($_POST['asfile'])) $options['asFile'] = ($_POST['asfile'] == "1");
  if (isset($_POST['palette'])) $options['palette'] = strip_tags($_POST['palette']);
  if (isset($_POST['shape'])) $options['shape'] = strip_tags($_POST['shape']);
  if (isset($_POST['stage'])) $options['stage'] = strip_tags($_POST['stage']);
  if (isset($_POST['filename'])) $options['filename'] = strip_tags($_POST['filename']);
//  if (isset($_POST['printable'])) $options['printable'] = ($_POST['printable'] == "1");
  if (isset($_POST['effect'])) $options['effect'] = strip_tags($_POST['effect']);
  if (isset($_POST['size'])) $options['size']= strip_tags ($_POST['size']);
    if (isset($_POST['units'])) $options['units']= strip_tags ($_POST['units']);
  if (isset($_POST['ar'])) $ar = strip_tags ($_POST['ar']);
  if (isset($_POST['webcols'])) $options['useWebColours'] = $_POST['webcols'] == 'yes';
  if (isset($_POST['tartancols'])) $options['useTartanColours'] = $_POST['tartancols'] == 'yes';
  if (isset($_POST['whcols'])) $options['useWarhammerColours'] = $_POST['whcols'] == 'yes';
} else { // for old API
  if (isset($_GET['blazon'])) $options['blazon'] = strip_tags(trim($_GET['blazon']));
  if (isset($_GET['saveformat'])) $options['saveFormat'] = strip_tags ($_GET['saveformat']);;
  if (isset($_GET['outputformat'])) $options['outputFormat'] = strip_tags ($_GET['outputformat']);;
  if (isset($_GET['asfile'])) $options['asFile'] = ($_GET['asfile'] == "1");
  if (isset($_GET['palette'])) $options['palette'] = strip_tags($_GET['palette']);
  if (isset($_GET['shape'])) $options['shape'] = strip_tags($_GET['shape']);
  if (isset($_GET['stage'])) $options['stage'] = strip_tags($_GET['stage']);
  if (isset($_GET['filename'])) $options['filename'] = strip_tags($_GET['filename']);
  if (isset($_GET['raw'])) $options['raw'] = true;
  //  if (isset($_GET['printable'])) $options['printable'] = ($_GET['printable'] == "1");
  if (isset($_GET['effect'])) $options['effect'] = strip_tags($_GET['effect']);
  if (isset($_GET['size'])) $options['size'] = strip_tags ($_GET['size']);
    if (isset($_GET['units'])) $options['units'] = strip_tags ($_GET['units']);
  if (isset($_GET['ar'])) $ar = strip_tags ($_GET['ar']);
  if (isset($_GET['webcols'])) $options['useWebColours'] = true;
  if (isset($_GET['tartancols'])) $options['useTartanColours'] = true;
  if (isset($_GET['whcols'])) $options['useWarhammerColours'] = true;
}

$options['blazon'] = preg_replace("/&#?[a-z0-9]{2,8};/i","",$options['blazon']); // strip all entities.
$options['blazon'] = preg_replace("/\\x[0-9-a-f]{2}/i","",$options['blazon']); // strip all entities.

 register_shutdown_function(function()
    {
        global $options, $spareRoom;
        $spareRoom = null;
        if ((!is_null($err = error_get_last()))  
              && (!in_array($err['type'], array (E_NOTICE, E_WARNING))) // comment this line to get all
               )
        {
           error_log($err['message'] . " (" . $err['type'] . ") at " . $err['file'] . ":" . $err['line'] . ' - ' . $options['blazon']);
        }
    });

// Quick response for empty blazon
if ( $options['blazon'] == '' ) {
  $dom = new DOMDocument('1.0');
  $dom->loadXML('<blazon ID="N1-0" blazonText="argent" blazonTokens="argent" creatorName="drawshield.net" ><shield ID="N1-6"><simple ID="N1-4"><field ID="N1-3"><tincture ID="N1-1" index="1" origin="given"><colour ID="N1-2" keyterm="argent" tokens="argent" linenumber="1" link="https://drawshield.net/reference/parker/a/argent.html"></colour></tincture></field></simple></shield><messages></messages></blazon>');
} else {
  // log the blazon for research... (unless told not too)
  if ( $options['logBlazon']) error_log($options['blazon']);
  include "parser/parser.inc";
  $p = new parser('english');
  $dom = $p->parse($options['blazon'],'dom');
  $p = null; // destroy parser
  if ( $options['stage'] == 'parser') { 
      $note = $dom->createComment("Debug information - parser stage.\n(Did you do SHIFT + 'Save as File' by accident?)");
      $dom->insertBefore($note,$dom->firstChild);
      header('Content-Type: text/xml; charset=utf-8');
      $dom->outputFormat = true;
      echo $dom->saveXML(); 
      exit; 
  }

  // Resolve references
  include "analyser/references.inc";
  $references = new references($dom);
  $dom = $references->setReferences();
  $references = null; // destroy references
  if ( $options['stage'] == 'references') { 
      $note = $dom->createComment("Debug information - references stage.\n(Did you do SHIFT + 'Save as File' by accident?)");
      $dom->insertBefore($note,$dom->firstChild);
      header('Content-Type: text/xml; charset=utf-8');
      $dom->outputFormat = true;
      echo $dom->saveXML(); 
      exit; 
  }
}
// Make the blazonML searchable
$xpath = new DOMXPath($dom);

/*
 * Update any options that were set in the blazon itself
 */
$blazonOptions = $xpath->query('//instructions/child::*');
if (!is_null($blazonOptions)) {
  for ($i = 0; $i < $blazonOptions->length; $i++) {
    $blazonOption = $blazonOptions->item($i);
    switch ($blazonOption->nodeName) {
      case blazonML::E_SHAPE:
        $options['shape'] = $blazonOption->getAttribute('keyterm');
        break;
      case blazonML::E_PALETTE:
        $options['palette'] = $blazonOption->getAttribute('keyterm');
        break;
      case blazonML::E_EFFECT:
        $options['effect'] = $blazonOption->getAttribute('keyterm');
        break;
      case blazonML::E_ASPECT:
        $ar = $blazonOption->getAttribute('keyterm');
        break;
    }
  }
}

/*
 * General options tidy-up
 */
if ($options['asFile']) {
    $options['printSize'] = $options['size'];
    $options['size'] = 1000;
}
// Minimum sensible size
if ( $options['size'] < 100 ) $options['size'] = 100;
if (!array_key_exists('shape',$options)) {
    $options['shape'] = 'heater';
} elseif (in_array($options['shape'],array('circular','round'))) {
    // Synonyms for circle shape
    $options['shape'] = 'circle';
} elseif ($options['shape'] == 'flag') {
    if ($ar != null) {
      $options['aspectRatio'] = calculateAR($ar);
    }
    $options['flagHeight'] = (int)(round($options['aspectRatio'] * 1000));
}


if ($options['palette'] == 'default') $options['palette'] = 'drawshield';

  // Read in the drawing code  ( All formats start out as SVG )


include "svg/draw.inc";
$output = draw();


// Output content header
if ( $options['asFile'] ) {
    $name = $options['filename'];
    if ($name == '') $name = 'shield';
    $pageWidth = $pageHeight = false;
    if ($options['units'] == 'in') {
	$options['printSize'] *= 90;
    } elseif ($options['units'] == 'cm') {
	$options['printSize'] *= 35;
    }
  $proportion = ($options['shape'] == 'flag') ? $options['aspectRatio'] : 1.2;
  switch ($options['saveFormat']) {
    case 'svg':
        if (substr($name,-4) != '.svg') $name .= '.svg';
     header("Content-type: application/force-download");
     header('Content-Disposition: inline; filename="' . $name );
     header("Content-Transfer-Encoding: text");
     header('Content-Disposition: attachment; filename="' . $name);
     header('Content-Type: image/svg+xml');
     echo $output;
     break;
      case 'pdfLtr':
          $pageWidth = 765;
          $pageHeight = 990;
          // flowthrough
    case 'pdfA4':
        if (!$pageWidth) $pageWidth = 744;
        if (!$pageHeight) $pageHeight = 1051;
    $im = new Imagick();
    $im->readimageblob($output);
    $im->setimageformat('pdf');
    $margin = 40; // bit less than 1/2"
    $maxWidth = $pageWidth - $margin - $margin;
    $imageWidth = $options['printSize'];
    if ($imageWidth > $maxWidth) $imageWidth = $maxWidth;
    $imageHeight = $imageWidth * $proportion;
    $im->scaleImage($imageWidth, $imageHeight);
    $fromBottom = $pageHeight - $margin - $margin - $imageHeight;
    $fromSide = $margin + (($pageWidth - $margin - $margin - $imageWidth) / 2);
    $im->setImagePage($pageWidth,$pageHeight,$fromSide * 0.9,$fromBottom * 0.9);
     // error_log("s=" . $options['size'] . " ps=" . $options['printSize'] . " un=" . $options['units'] . " m=$margin, mw=$maxWidth, pw=$pageWidth, ph=$pageHeight, iw=$imageWidth, ih=$imageHeight, fs=$fromSide, fb=$fromBottom\n");
    //$im->setImageResolution(150);
        if (substr($name,-4) != '.pdf') $name .= '.pdf';
    header("Content-type: application/force-download");
    header('Content-Disposition: inline; filename="' . $name);
    header("Content-Transfer-Encoding: 8bit");
    header('Content-Disposition: attachment; filename="' . $name);
    header('Content-Type: application/pdf');
    echo $im->getimageblob();
    break;
    case 'jpg':
      $im = new Imagick();
      $im->readimageblob($output);
      $im->setimageformat('jpeg');
      $im->setimagecompressionquality(90);
    $imageWidth = $options['printSize'];
    $imageHeight = $imageWidth * $proportion;
      $im->scaleimage($imageWidth,$imageHeight);
        if (substr($name,-4) != '.jpg') $name .= '.jpg';
      header("Content-type: application/force-download");
      header('Content-Disposition: inline; filename="' . $name);
      header("Content-Transfer-Encoding: binary");
      header('Content-Disposition: attachment; filename="' . $name);
      header('Content-Type: image/jpg');
      echo $im->getimageblob();
      break;
    case 'png':
    default:
     $im = new Imagick();
     $im->setBackgroundColor(new ImagickPixel('transparent'));
     $im->readimageblob($output);
     $im->setimageformat('png32');
    $imageWidth = $options['printSize'];
    $imageHeight = $imageWidth * $proportion;
      $im->scaleimage($imageWidth,$imageHeight);
      if (substr($name,-4) != '.png') $name .= '.png';
      header("Content-type: application/force-download");
     header('Content-Disposition: inline; filename="' . $name);
     header("Content-Transfer-Encoding: binary");
     header('Content-Disposition: attachment; filename="' . $name);
     header('Content-Type: image/png');
     echo $im->getimageblob();
     break;
   }
} else {
  switch ($options['outputFormat']) {
    case 'jpg':
      $im = new Imagick();
      $im->readimageblob($output);
      $im->setimageformat('jpeg');
      $im->setimagecompressionquality(90);
      // $im->scaleimage(1000,1200);
      header('Content-Type: image/jpg');
      echo $im->getimageblob();
      break;
    case 'png':
      $im = new Imagick();
      $im->setBackgroundColor(new ImagickPixel('transparent'));
      $im->readimageblob($output);
      $im->setimageformat('png32');
      // $im->scaleimage(1000,1200);
      header('Content-Type: image/png');
      echo $im->getimageblob();
      break;
    default:
    case 'svg':
      header('Content-Type: text/xml; charset=utf-8');
      echo $output;
      break;
  }
}

