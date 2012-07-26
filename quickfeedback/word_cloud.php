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

class WordCloud {

  private $width, $height;
  private $font;
  private $mask;
  private $table;
  private $image;
  private $imagecolor;

  public function __construct($width, $height, $font, $text=null, $imagecolor=array( 0,0, 0, 127),$words_limit=null, $vertical_freq = FrequencyTable::WORDS_MAINLY_HORIZONTAL) {
    $this->width = $width;
    $this->height = $height;
    $this->font = $font;
    $this->imagecolor = $imagecolor;
    
    $this->mask = new Mask();
    if(is_array($text)){
      $this->table = new FrequencyTable($font,'',$vertical_freq,$words_limit);//, $text);
      foreach($text as $row){
      	if(!isset($row['title'])) $row['title'] = null;
      	$this->table->add_word($row['word'],$row['count'],$row['title']);
      }
    }else{
	    $this->table = new FrequencyTable($font, $text, $vertical_freq,$words_limit);
    }
    $this->table->setMinFontSize(10);
    $this->table->setMaxFontSize(72);
    // $this->table = new FrequencyTable($font);//, $text);
    // $this->table->add_word('word1');
    // $this->table->add_word('word2', 2);
    // $this->table->add_word('word3');
    // $this->table->add_word('word4', 4);
    // $this->table->add_word('word5');
    // for($i = 6; $i <= 20; $i++) $this->table->add_word('word'.$i, $i % 5);
    
    $this->image = imagecreatetruecolor($width, $height);
	//Set the flag to save full alpha channel information (as opposed to single-color transparency) when saving PNG images
    imagesavealpha($this->image, true);
	//behaves identically to imagecolorallocate() with the addition of the transparency parameter alpha
    $trans_colour = imagecolorallocatealpha($this->image,  $imagecolor[0],$imagecolor[1], $imagecolor[2], $imagecolor[3]);
    imagefill($this->image, 0, 0, $trans_colour);

  }

  public function get_image() {
    return $this->image;
  }

  public function render($palette) {
    $i = 0;
    $positions = array();
    
    foreach($this->table->get_table() as $key => $val) {
      // Set the center so that vertical words are better distributed
      if ($val->angle == 0) {
        $cx = $this->width /3;
        $cy = $this->height /2;
      }
      else {
        $cx = $this->width /3 + rand(0, $this->width / 10);
        $cy = $this->height /2 + rand(-$this->height/10, $this->height/10);
      }

      // Search the place for the next word
      list($cx, $cy) = $this->mask->search_place($this->image, $cx, $cy, $val->box);

      // Draw the word
      $res['words'][$key] = array(
        'x' => $cx,
        'y' => $cy,
        'angle' => $val->angle,
        'size' => $val->size,
        'color' => $palette[$i % count($palette)],
        'box' => isset($boxes[$key]) ? $boxes[$key] : '',
      );
      imagettftext($this->image, $val->size, $val->angle, $cx, $cy, $palette[$i % count($palette)], $this->font, $key);
      $this->mask->add(new Box($cx, $cy, $val->box));
      $i++;
    }

    // Crop the image
    list($x1, $y1, $x2, $y2) = $this->mask->get_bounding_box();
    $image2 = imagecreatetruecolor(abs($x2 - $x1), abs($y2 - $y1));
    
    //Set the flag to save full alpha channel information (as opposed to single-color transparency) when saving PNG images
    imagesavealpha($image2, true);
    //behaves identically to imagecolorallocate() with the addition of the transparency parameter alpha
    $trans_colour = imagecolorallocatealpha($image2, $this->imagecolor[0],$this->imagecolor[1], $this->imagecolor[2], $this->imagecolor[3]);
    imagefill($image2, 0, 0, $trans_colour);
    
    imagecopy($image2 ,$this->image, 0, 0, $x1, $y1, abs($x2 - $x1), abs($y2 - $y1));


    
    imagedestroy($this->image);
    $this->image = $image2;

    // Adjust the map to the cropped image
    $this->mask->adjust(-$x1, -$y1);

    foreach($boxes = $this->get_image_map() as $map) {
      $res['words'][$map[0]]['box'] = $map[1];
    }

    $res['adjust'] = array('dx' => -$x1, 'dy' => -$y1);
    return $res;
  }

  public function get_image_map() {

    $words = $this->table->get_table();
    $boxes = $this->mask->get_table();
    if (count($boxes) != count($words)) {
      throw new Exception('Error: mask count <> word count');
    }


    $map = array();
    $i = 0;
    foreach($words as $key => $val) {
      $map[] = array($key, $boxes[$i],$val->title);
      $i += 1;
    }

    return $map;
  }
}

