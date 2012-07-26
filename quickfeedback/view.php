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

require_once("../../config.php");
require_once("lib.php");
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/mod/assignment/lib.php');
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir . '/portfoliolib.php');
require ("$CFG->dirroot/mod/quickfeedback/edit_form.php");
require_once($CFG->libdir . '/filelib.php');
require_once ("box.php");
require_once ( "mask.php");
require_once ("frequency_table.php");
require_once ("palette.php");
require_once ("word_cloud.php");


$id         = required_param('id', PARAM_INT);                 // Course Module ID
$action     = optional_param('action', '', PARAM_ALPHA);

$url = new moodle_url('/mod/quickfeedback/view.php', array('id'=>$id));
if ($action !== '') {
	$url->param('action', $action);
}
$PAGE->set_url($url);

if (! $cm = get_coursemodule_from_id('quickfeedback', $id)) {
	print_error('invalidcoursemodule');
}
if (! $quickfeedback = $DB->get_record("quickfeedback", array("id"=>$cm->instance))) {
	print_error('invalidcoursemodule');
}

if (! $course = $DB->get_record("course", array("id" => $cm->course))) {
	print_error('coursemisconf');
}


require_course_login($course, false, $cm);
///////////////

$editoroptions = array('noclean'=>false, 'maxfiles'=>EDITOR_UNLIMITED_FILES, 'maxbytes'=>$course->maxbytes);
echo '<form method="post" style="display: inline; margin: 0; padding: 0;">';
echo '<input type="hidden" name="id" value="'.$id.'" />';
echo '</form>';


$PAGE->set_title(format_string($quickfeedback->name));
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
$context = get_context_instance(CONTEXT_MODULE, $cm->id);
$data = new stdClass();
$data->id         = $cm->id;
$data->edit       = 1;
$data->textformat      = 0;
$data->sid        = NULL;

if (has_capability('mod/quickfeedback:viewresponses', $context)) {

	$link = html_writer::link(new moodle_url('/mod/quickfeedback/view.php', array('id'=>$cm->id,'action'=>'showresponse')), format_string("View Responses"), $linkcss);
	echo "<div align='right'>".$link."</div>";
}
echo $OUTPUT->box_start('boxaligncenter boxwidthwide');
echo $groupselect.'<div class="clearer">&nbsp;</div>';
echo $OUTPUT->heading(format_text($quickfeedback->name));

if($quickfeedback->timeopen) {
	echo $OUTPUT->box_start('feedback_info');
	echo '<span class="feedback_info">'.get_string('quickfeedbackopen', 'quickfeedback').': </span><span class="feedback_info_value">' .UserDate($quickfeedback->timeopen). '</span>';
	echo $OUTPUT->box_end();
}
if($quickfeedback->timeclose) {
	echo $OUTPUT->box_start('feedback_info');
	echo '<span class="feedback_info">'.get_string('quickfeedbackclose', 'quickfeedback').': </span><span class="feedback_info_value">' .UserDate($quickfeedback->timeclose). '</span>';
	echo $OUTPUT->box_end();
}

   if (has_capability('mod/quickfeedback:postfeedback', $context) || has_capability('mod/quickfeedback:viewresponses', $context)) {

	echo $OUTPUT->box_start('feedback_info');
	echo '<br/><span class="feedback_info">Question: </span><span class="feedback_info_value">' .strip_tags(format_string($quickfeedback->intro,true)). '</span>';
	echo $OUTPUT->box_end();
}



echo $OUTPUT->box_end();


if($action=="showresponse"){
	if (has_capability('mod/quickfeedback:viewresponses', $context)) {

		//echo "Responses";
		$responses=$DB->get_records_sql("Select * from {quickfeedback_response} where quickfeedbackid= $cm->id");
		foreach($responses as $response){
			$full_text.=$response->response;
			
		}

		
		$font = dirname(__FILE__).'/Arial.ttf';
		$width = 600;
		$height = 600;
		$cloud = new WordCloud($width, $height, $font, $full_text);
		$palette = Palettee::get_palette_from_hex($cloud->get_image(), array('FFA700', 'FFDF00', 'FF4F00', 'FFEE73'));
		$cloud->render($palette);

		// Render the cloud in a temporary file, and return its base64-encoded content
		$file = tempnam(getcwd(), 'img');
		imagepng($cloud->get_image(), $file);
		$img64 = base64_encode(file_get_contents($file));
		unlink($file);
		imagedestroy($cloud->get_image());
		 add_to_log($course->id, 'quickfeedback', ' view result', 'view.php?id='.$cm->id, $course->fullname,$cm->id);
		
		echo '<h2>Word map of students feedback.</h2>';


		if($full_text==""){
			echo "<font color='red'>No results</font>";
		}
			
		?>

<div align="center">
	<img usemap="#mymap" src="data:image/png;base64,<?php echo $img64 ?>"
		border="0" />
	<map name="mymap">
	<?php foreach($cloud->get_image_map() as $map): ?>
		<area shape="rect" coords="<?php echo $map[1]->get_map_coords() ?>"
			onclick="alert('You clicked: <?php echo $map[0] ?>');" />
			<?php endforeach ?>
	</map>
</div>
			<?php }}
			else{/////wordle



				$data = file_prepare_standard_editor($data, 'text', $editoroptions,$context, 'mod_quickfeedback', 'submission', $data->sid);

				$mform = new mod_quickfeedback_edit_form(null, array($data, $editoroptions,$id));

				if ($mform->is_cancelled()) {
					redirect($PAGE->url);
				}
				if ($data = $mform->get_data()) {
					$data = file_postupdate_standard_editor($data, 'text', $editoroptions,$context, 'mod_quickfeedback', 'submission', $data->sid);
					quickfeedback_user_submit_response($course,$data,$USER->id, $cm);
					add_to_log($course->id, "quickfeedback", "add", "view.php?id=$cm->id", $course->fullname, $cm->id);
					echo "<font size='4'><b>Thankyou for your feedback</b></font>";
					redirect("$CFG->wwwroot/course/view.php?id=$course->id");
				}
				else{
					if (has_capability('mod/quickfeedback:postfeedback', $context)) {
					 $checktime = time();
					 if(($quickfeedback->timeopen > $checktime) OR ($quickfeedback->timeclose < $checktime AND $quickfeedback->timeclose > 0)) {
					 	echo $OUTPUT->box_start('generalbox boxaligncenter');
					 	echo '<h2><font color="red">'.get_string('feedback_is_not_open', 'quickfeedback').'</font></h2>';
					 	echo $OUTPUT->continue_button($CFG->wwwroot.'/course/view.php?id='.$course->id);
					 	echo $OUTPUT->box_end();
					 	echo $OUTPUT->footer();
					 	exit;
					 }
						$feedback_can_submit = true;
					 if(quickfeedback_is_already_submitted($USER->id, $cm)) {
					 	$feedback_can_submit = false;
					 }
					 if($feedback_can_submit) {
					 	 add_to_log($course->id, 'quickfeedback', 'view', 'view.php?id='.$cm->id, $course->fullname,$cm->id);
					 	
					 	$mform->display();
					 }
					 else{

					 	echo '<h2><font color="red">'.get_string('this_feedback_is_already_submitted', 'quickfeedback').'</font></h2>';

					 }
					}
				}
			}

			
			echo $OUTPUT->footer();



