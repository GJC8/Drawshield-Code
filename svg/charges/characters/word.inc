<?php /* Copyright 2010-2021 Karl Wilcox, Mattias Basaglia

This file is part of the DrawShield.net heraldry image creation program

    DrawShield is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

     DrawShield is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with  DrawShield.  If not, see https://www.gnu.org/licenses/.
 */

// word

$charge['default_colour'] = 'sable';

$textOptions = array();
// defaults
$fontWeight = 'normal';
$fontStyle = 'normal';
$underline = false;
$fontFamily = null;
$textAlign = 'middle';
$textLayout = WordBuilder::LEFT;

if ( existModifierWithKeyterm($node,'bold') ) $fontWeight = 'bold';
if ( existModifierWithKeyterm($node,'italic') ) $fontStyle = 'italic';
if ( $font = getModifierValueByKeyterm($node,'font') ) {
  $fontFamily = $font;
} 



$item = getModifierValueByKeyterm( $node, 'value' );

if ( $item == null ) $item = 'huh';

$builder = new WordBuilder($fontFamily);
if ( existModifierWithKeyterm($node,'underlined') ) 
    $builder->option(WordBuilder::UNDERLINE);
  if (existModifierWithKeyterm($node, 'to-dexter'))
      $builder->option(WordBuilder::ALIGN, WordBuilder::LEFT);
  if (existModifierWithKeyterm($node, 'to-sinister')) 
      $builder->option(WordBuilder::ALIGN, WordBuilder::RIGHT);
  if (existModifierWithKeyterm($node, 'to-centre')) 
      $builder->option(WordBuilder::ALIGN, WordBuilder::CENTRE);
  if (existModifierWithKeyterm($node,'encircled')) { // This is first, so any modifier added for "encircled-by" overrides others
    $textLayout = 'encircled';
  } elseif ( existModifierWithKeyterm($node,'text-inline')) {
      $builder->option(WordBuilder::LAYOUT, WordBuilder::INLINE);
  } elseif (existModifierWithKeyterm($node,'text-stacked')) {
      $builder->option(WordBuilder::LAYOUT, WordBuilder::STACKED);
  } elseif (existModifierWithKeyterm($node,'text-block')) {
    $textLayout = 'block';
  } elseif (existModifierWithKeyterm($node,'enarched')) {
      $builder->option(WordBuilder::LAYOUT, WordBuilder::ENARCHED);
  } elseif (existModifierWithKeyterm($node,'embowed')) {
    $textLayout = 'embowed';
  } elseif (existModifierWithKeyterm($node,'wavy')) {
    $textLayout = 'wavy';
}
$charge['svgCode'] = $builder->getSVG($item);

/* $height = 100;
$width = 100;
$svgText = '';
switch ($textLayout) {
  case 'stacked': //-----------------------------------------------------
    $width = 25 * $longestWord;
    $height = 44 * count($words);
    $y = 44;
    switch ($textAlign) {
      case 'start':
        $x = 0;
        break;
      case 'end':
        $x = $width;
        break;
      case 'middle':
        $x = $width / 2;
        break;
    }
    $svgText .= '<text x="0" y="0" font-size="44" font-style="' .
      $fontStyle . '" font-family="' . $fontFamily . '" text-decoration="' . $textDecoration .
      '" font-weight="' . $fontWeight . '">';
    foreach ($words as $word) {
      $svgText .= '<tspan x="' . $x . '" y="' .  $y . '" text-anchor="' . $textAlign . '" >' . $word . '</tspan>';
      $y += 44;
    }
    $svgText .= '</text>';
    break;
  case 'inline': //----------------------------------------------------------
    $width = 25 * strlen($item);
    $height = 44;
    $svgText .= '<text x="0" y="0" font-size="44" font-style="' .
      $fontStyle . '" font-family="' . $fontFamily . '" text-decoration="' . $textDecoration .
      '" font-weight="' . $fontWeight . '">';
    $svgText .= '<tspan x="' . ($width / 2) . '" y="44" text-anchor="middle" >' . $item . '</tspan>';
    $svgText .= '</text>';
    break;
  case 'block': //--------------------------------------------------------------
    $totalLength = strlen($item);
    $idealLength = intval($totalLength / sqrt($totalLength/2)); // roughly n lines of 2n characters
    if ($longestWord > $idealLength) $idealLength = $longestWord; // unless a word is too long!
    $lines = array();
    $line = '';
    $lineLength = 0;
    $longestLine = 0;
    foreach ( $words as $word ) {
      if ( $lineLength + strlen($word) > $idealLength ) {
        $line = trim($line);
        if (strlen($line) > $longestLine ) $longestLine = strlen($line);
        $lines[] = $line;
        $line = $word . ' ';
        $lineLength = strlen($word);
      } else {
        $line .= $word . ' ';
        $lineLength += strlen($word) + 1;
      }
    }
    $line = trim($line);
    if ($line) {
      $lines[] = $line;
      if (strlen($line) > $longestLine ) $longestLine = strlen($line);
    }
    $width = 25 * $longestLine;
    $height = 44 * count($lines);
    $y = 44;
    switch ($textAlign) {
      case 'start':
        $x = 0;
        break;
      case 'end':
        $x = $width;
        break;
      case 'middle':
        $x = $width / 2;
        break;
    }
    $svgText .= '<text x="0" y="0" font-size="44" font-style="' .
      $fontStyle . '" font-family="' . $fontFamily . '" text-decoration="' . $textDecoration .
      '" font-weight="' . $fontWeight . '">';
    foreach ($lines as $line) {
      $svgText .= '<tspan x="' . $x . '" y="' .  $y . '" text-anchor="' . $textAlign . '" >' . $line . '</tspan>';
      $y += 44;
    }
    $svgText .= '</text>';
    break;
  case 'enarched': //-----------------------------------------------------------
  case 'embowed':
  $width = 450;
  $height = 200;
  $fontSize = 1000 / strlen($item); // rough font scaling for the arc below
  if ( array_key_exists($fontFamily,$fontAdjustments)) $fontSize *= $fontAdjustments[$fontFamily];
  if ( $fontSize > 150 ) $fontSize = 150; // but not too large!
  if ( $textAlign == 'middle')
    $startOffset = '50%';
  elseif ( $textAlign == 'start')
    $startOffset = '0%';
  else
    $startOffset = '100%';
  if ( $textLayout == 'enarched')
    $arch = add_def('path d="m 25,200 a1,0.7 0 0,1 400,0"',null);
  else
    $arch = add_def('path d="m 25,0 a1,0.7 0 0,0 400,0"',null);
  $svgText .= '<defs><path id="#' . $arch .  '" d="m 0,0 "/></defs>'; // dummy defs to make svgCode parse in simplexml_load
  $svgText .= '<text text-anchor="' . $textAlign . '" font-weight="' . $fontWeight . '" font-style="' . $fontStyle . '" font-family="' . $fontFamily . '" font-size="' . $fontSize . '" x="0" y="400"><textPath startOffset="' . $startOffset . '" href="#' . $arch . '">' . $item . '</textPath></text>';
    break;
  case 'wavy':
    $width = 800;
    $height = 320;
    if ( $textAlign == 'middle')
      $startOffset = '50%';
    elseif ( $textAlign == 'start')
      $startOffset = '0%';
    else // 'end'
      $startOffset = '100%';
    $fontSize = 2400 / strlen($item); // rough font scaling for the arc below
    if ( array_key_exists($fontFamily,$fontAdjustments)) $fontSize *= $fontAdjustments[$fontFamily];
    if ( $fontSize > 150 ) $fontSize = 150; // but not too large!
    $arch = add_def('path d="m 25,160 a1,0.7 0 0,1 400,0 a1,0.7 0 0,0 400,0"',null);
    $svgText .= '<defs><path id="#' . $arch .  '" d="m 0,0 "/></defs>'; // dummy defs to make svgCode parse in simplexml_load
    $svgText .= '<text text-anchor="' . $textAlign . '" font-weight="' . $fontWeight . '" font-style="' . $fontStyle . '" font-family="' . $fontFamily . '" font-size="' . $fontSize . '" x="0" y="400"><textPath startOffset="' . $startOffset . '" href="#' . $arch . '">' . $item . '</textPath></text>';
    break;
  case 'encircled':
    $width = 500;
    $height = 400;
    $fontSize = 3000 / strlen($item); // rough font scaling for the arc below
    if ( array_key_exists($fontFamily,$fontAdjustments)) $fontSize *= $fontAdjustments[$fontFamily];
    if ( $fontSize > 150 ) $fontSize = 150; // but not too large!
    $arch = add_def('path d="m 50,200 a200,200 0 1,1 400,0 200,200 0 1,1 -400,0"',null);
    $svgText .= '<defs><path id="#' . $arch .  '" d="m 0,0 "/></defs>'; // dummy defs to make svgCode parse in simplexml_load
    $svgText .= '<text text-anchor="middle" font-weight="' . $fontWeight . '" font-style="' . $fontStyle . '" font-family="' . $fontFamily . '" font-size="' . $fontSize . '" x="0" y="400"><textPath startOffset="50%" href="#' . $arch . '">' . htmlentities($item, ENT_XML1) . '</textPath></text>';
    break;
} */



