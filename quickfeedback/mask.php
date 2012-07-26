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

class Mask {

  private $drawn_boxes = array();

  /**
   * Add a new box to the mask.
   * @param Box $box The new box to add
   */
  public function add(Box $box) {
    $this->drawn_boxes[] = $box;
  }

  public function get_table() { return $this->drawn_boxes; }

  /**
   * Test whether a box overlaps with the already drawn boxes.
   * @param Box $test_box The box to test
   * @return boolean True if the box overlaps with the already drawn boxes and false otherwise
   */
  public function overlaps(Box $test_box) {
    foreach($this->drawn_boxes as $box) {
      if ($box->intersects($test_box)) {
        return true;
      }
    }
    return false;
  }

  /**
   * Search a free place for a new box.
   *
   * @param object $im The GD image
   * @param float $ox The x coordinate of the starting search point
   * @param float $oy The y coordinate of the starting search point
   * @param array $box The 8 coordinates of the new box
   * @param Mask $mask The mask containing the already drawn boxes
   * @return array The x and y coordinates for the new box
   */
  function search_place($im, $ox, $oy, $box) {
    $place_found = false;
    $i = 0; $x = $ox; $y = $oy;
    while (! $place_found) {
      $x = $x + ($i / 2 * cos($i));
      $y = $y + ($i / 2 * sin($i));
      $new_box = new Box($x, $y, $box);
      // TODO: Check if the new coord is in the clip area
      $place_found = ! $this->overlaps($new_box);
      // Uncomment the next line to see the spiral used to search a free place
      //imagesetpixel($im, $x, $y, imagecolorallocate($im, 255, 0, 0));
      $i += 1;
    }
    return array($x, $y);
  }

  public function get_bounding_box($margin = 10) {
    $left = null; $right = null;
    $top = null; $bottom = null;
    foreach($this->drawn_boxes as $box) {
      if (($left == NULL) || ($box->left < $left)) $left = $box->left;
      if (($right == NULL) || ($box->right > $right)) $right = $box->right;
      if (($top == NULL) || ($box->top > $top)) $top = $box->top;
      if (($bottom == NULL) || ($box->bottom < $bottom)) $bottom = $box->bottom;
    }
    return array($left - $margin, $bottom - $margin, $right + $margin, $top + $margin);
  }

  public function adjust($dx, $dy) {
    foreach($this->drawn_boxes as $box) {
      $box->left += $dx;
      $box->right += $dx;
      $box->top += $dy;
      $box->bottom += $dy;
    }
  }
}

