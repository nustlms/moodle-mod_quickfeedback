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

/**
 * An axis-aligned rectangle with collision detection
 */
class Box {

  public $left, $right, $top, $bottom;

  /**
   * Construct a new rectangle from a point and a bounding box
   * @param integer $x The point x coordinate
   * @param integer $y The point x coordinate
   * @param array $bb The bounding box given in an array of 8 coordinates
   */
  public function __construct($x, $y, $bb) {

    $x1 = $x + $bb[0];
    $y1 = $y + $bb[1];
    $x2 = $x + $bb[2];
    $y2 = $y + $bb[3];
    $x3 = $x + $bb[4];
    $y3 = $y + $bb[5];
    $x4 = $x + $bb[6];
    $y4 = $y + $bb[7];

    $this->left = min($x1, $x2, $x3, $x4);
    $this->right = max($x1, $x2, $x3, $x4);
    $this->bottom = min($y1, $y2, $y3, $y4);
    $this->top = max($y1, $y2, $y3, $y4);
  }

  /**
   * Detect box collision
   * This algorithm only works with Axis-Aligned boxes!
   * @param Box $box The other rectangle to test collision with
   * @return boolean True is the boxes collide, false otherwise
   */
  function intersects(Box $box) {
    if ($this->bottom > $box->top) return false;
    if ($this->top < $box->bottom) return false;
    if ($this->right < $box->left) return false;
    if ($this->left > $box->right) return false;

    return true;
  }

  /**
   * Return a string representing the HTML imagemap coords of the rect
   */
  public function get_map_coords() {
    return "{$this->left},{$this->top},{$this->right},{$this->bottom}";
  }
}

