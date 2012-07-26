<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package   mod-quickfeedback
 * @copyright 2012 Hina Yousuf
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class Palettee {

  private static $palettes = array(
    'aqua' => array('BED661', '89E894', '78D5E3', '7AF5F5', '34DDDD', '93E2D5'),
    'yellow/blue' => array('FFCC00', 'CCCCCC', '666699'),
    'grey' => array('87907D', 'AAB6A2', '555555', '666666'), 
    'brown' => array('CC6600', 'FFFBD0', 'FF9900', 'C13100'), 
    'army' => array('595F23', '829F53', 'A2B964', '5F1E02', 'E15417', 'FCF141'),
    'pastel' => array('EF597B', 'FF6D31', '73B66B', 'FFCB18', '29A2C6'),
    'red' => array('FFFF66', 'FFCC00', 'FF9900', 'FF0000'), 
  );

  /**
   * Construct a random color palette
   * @param object $im The GD image
   * @param integer $count The number of colors in the palette
   */
  public static function get_random_palette($im, $count = 5) {
    $palette = array();
    for ($i = 0; $i < $count; $i++) {
      $palette[] = imagecolorallocate($im, rand(0, 255), rand(0, 255), rand(0, 255));
    }
    return $palette;
  }

  /**
   * Construct a color palette from a list of hexadecimal colors (RRGGBB)
   * @param object $im The GD image
   * @param array $hex_array An array of hexadecimal color strings
   */
  public static function get_palette_from_hex($im, $hex_array) {
    $palette = array();
    foreach($hex_array as $hex) {
    if (strlen($hex) != 6) throw new Exception("Invalid palette color '$hex'");
      $palette[] = imagecolorallocate($im,
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2)));
    }
    return $palette;
  }
  
  public static function get_named_palette($im, $name) {
    if (array_key_exists($name, self::$palettes)) {
      return self::get_palette_from_hex($im, self::$palettes[$name]);
    }
    return self::get_named_palette($im, 'grey');
  }
  
  public static function list_named_palettes() {
    return array_keys(self::$palettes);
  }
}
