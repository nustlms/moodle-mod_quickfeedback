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

/** @global int $CHOICE_COLUMN_HEIGHT */
global $CHOICE_COLUMN_HEIGHT;
$CHOICE_COLUMN_HEIGHT = 300;

/** @global int $CHOICE_COLUMN_WIDTH */
global $CHOICE_COLUMN_WIDTH;
$CHOICE_COLUMN_WIDTH = 300;

define('CHOICE_PUBLISH_ANONYMOUS', '0');
define('CHOICE_PUBLISH_NAMES',     '1');

define('CHOICE_SHOWRESULTS_NOT',          '0');
define('CHOICE_SHOWRESULTS_AFTER_ANSWER', '1');
define('CHOICE_SHOWRESULTS_AFTER_CLOSE',  '2');
define('CHOICE_SHOWRESULTS_ALWAYS',       '3');

define('CHOICE_DISPLAY_HORIZONTAL',  '0');
define('CHOICE_DISPLAY_VERTICAL',    '1');

/** @global array $CHOICE_PUBLISH */
global $CHOICE_PUBLISH;
$CHOICE_PUBLISH = array (CHOICE_PUBLISH_ANONYMOUS  => get_string('publishanonymous', 'quickfeedback'),
CHOICE_PUBLISH_NAMES      => get_string('publishnames', 'quickfeedback'));

/** @global array $CHOICE_SHOWRESULTS */
global $CHOICE_SHOWRESULTS;
$CHOICE_SHOWRESULTS = array (CHOICE_SHOWRESULTS_NOT          => get_string('publishnot', 'quickfeedback'),
CHOICE_SHOWRESULTS_AFTER_ANSWER => get_string('publishafteranswer', 'quickfeedback'),
CHOICE_SHOWRESULTS_AFTER_CLOSE  => get_string('publishafterclose', 'quickfeedback'),
CHOICE_SHOWRESULTS_ALWAYS       => get_string('publishalways', 'quickfeedback'));

/** @global array $CHOICE_DISPLAY */
global $CHOICE_DISPLAY;
$CHOICE_DISPLAY = array (CHOICE_DISPLAY_HORIZONTAL   => get_string('displayhorizontal', 'quickfeedback'),
CHOICE_DISPLAY_VERTICAL     => get_string('displayvertical','quickfeedback'));

/// Standard functions /////////////////////////////////////////////////////////

/**
 * @global object
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $quickfeedback
 * @return object|null
 */

/**
 * @global object
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $quickfeedback
 * @return string|void
 */


/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @global object
 * @param object $quickfeedback
 * @return int
 */
function quickfeedback_add_instance($quickfeedback) {
	global $DB;

	$quickfeedback->timemodified = time();

	if (empty($quickfeedback->timerestrict)) {
		$quickfeedback->timeopen = 0;
		$quickfeedback->timeclose = 0;
	}

	//insert answers
	$quickfeedback->id = $DB->insert_record("quickfeedback", $quickfeedback);
	foreach ($quickfeedback->option as $key => $value) {
		$value = trim($value);
		if (isset($value) && $value <> '') {
			$option = new stdClass();
			$option->text = $value;
			$option->quickfeedbackid = $quickfeedback->id;
			if (isset($quickfeedback->limit[$key])) {
				$option->maxanswers = $quickfeedback->limit[$key];
			}
			$option->timemodified = time();
			$DB->insert_record("quickfeedback_options", $option);
		}
	}

	return $quickfeedback->id;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @global object
 * @param object $quickfeedback
 * @return bool
 */
function quickfeedback_update_instance($quickfeedback) {
	global $DB;

	$quickfeedback->id = $quickfeedback->instance;
	$quickfeedback->timemodified = time();


	if (empty($quickfeedback->timerestrict)) {
		$quickfeedback->timeopen = 0;
		$quickfeedback->timeclose = 0;
	}

	//update, delete or insert answers
	foreach ($quickfeedback->option as $key => $value) {
		$value = trim($value);
		$option = new stdClass();
		$option->text = $value;
		$option->quickfeedbackid = $quickfeedback->id;
		if (isset($quickfeedback->limit[$key])) {
			$option->maxanswers = $quickfeedback->limit[$key];
		}
		$option->timemodified = time();
		if (isset($quickfeedback->optionid[$key]) && !empty($quickfeedback->optionid[$key])){//existing quickfeedback record
			$option->id=$quickfeedback->optionid[$key];
			if (isset($value) && $value <> '') {
				$DB->update_record("quickfeedback_options", $option);
			} else { //empty old option - needs to be deleted.
				$DB->delete_records("quickfeedback_options", array("id"=>$option->id));
			}
		} else {
			if (isset($value) && $value <> '') {
				$DB->insert_record("quickfeedback_options", $option);
			}
		}
	}

	return $DB->update_record('quickfeedback', $quickfeedback);

}

/**
 * @global object
 * @param object $quickfeedback
 * @param object $user
 * @param object $coursemodule
 * @param array $allresponses
 * @return array
 */

/**
 * @global object
 * @param int $formanswer
 * @param object $quickfeedback
 * @param int $userid
 * @param object $course Course object
 * @param object $cm
 */
function quickfeedback_user_submit_response($course,$data,$userid, $cm) {
	global $DB, $CFG;
	$feedback = new stdClass();
	$feedback->userid = $userid;
	$feedback->timemodified = time();
	$feedback->quickfeedbackid = $cm->id;
	$feedback->response = " ".strip_tags(format_string($data->text,true));
	$DB->insert_record("quickfeedback_response", $feedback);
	
}

function quickfeedback_is_already_submitted($userid, $cm) {

	global $USER, $DB;

	if (!$response = $DB->get_record_sql("select * from {quickfeedback_response} where userid=$userid and quickfeedbackid= $cm->id ")) {
		return false;
	}
	return true;
}

/**
 * @param array $user
 * @param object $cm
 * @return void Output is echo'd
 */

/**
 * @global object
 * @param object $quickfeedback
 * @param object $course
 * @param object $coursemodule
 * @param array $allresponses

 *  * @param bool $allresponses
 * @return object
 */

/**
 * @global object
 * @param array $attemptids
 * @param object $quickfeedback Choice main table row
 * @param object $cm Course-module object
 * @param object $course Course object
 * @return bool
 */


/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @global object
 * @param int $id
 * @return bool
 */
function quickfeedback_delete_instance($id) {
	global $DB;

	if (! $quickfeedback = $DB->get_record("quickfeedback", array("id"=>"$id"))) {
		return false;
	}

	$result = true;

	if (! $DB->delete_records("quickfeedback", array("id"=>"$quickfeedback->id"))) {
		$result = false;
	}

	return $result;
}


/**
 * Returns text string which is the answer that matches the id
 *
 * @global object
 * @param object $quickfeedback
 * @param int $id
 * @return string
 */

/**
 * Gets a full quickfeedback record
 *
 * @global object
 * @param int $quickfeedbackid
 * @return object|bool The quickfeedback or false
 */


/**
 * @return array
 */
function quickfeedback_get_view_actions() {
	return array('view','view all','report');
}

/**
 * @return array
 */
function quickfeedback_get_post_actions() {
	return array('choose','choose again');
}


/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the quickfeedback.
 *
 * @param object $mform form passed by reference
 */
function quickfeedback_reset_course_form_definition(&$mform) {
	$mform->addElement('header', 'quickfeedbackheader', get_string('modulenameplural', 'quickfeedback'));
	$mform->addElement('advcheckbox', 'reset_quickfeedback', get_string('removeresponses','quickfeedback'));
}

/**
 * Course reset form defaults.
 *
 * @return array
 */
function quickfeedback_reset_course_form_defaults($course) {
	return array('reset_quickfeedback'=>1);
}

/**
 * Actual implementation of the reset course functionality, delete all the
 * quickfeedback responses for course $data->courseid.
 *
 * @global object
 * @global object
 * @param object $data the data submitted from the reset course.
 * @return array status array
 */


/**
 * Returns all other caps used in module
 *
 * @return array
 */
function quickfeedback_get_extra_capabilities() {
	return array('moodle/site:accessallgroups');
}

/**
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_GROUPMEMBERSONLY
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function quickfeedback_supports($feature) {
	switch($feature) {
		case FEATURE_GROUPS:                  return true;
		case FEATURE_GROUPINGS:               return true;
		case FEATURE_GROUPMEMBERSONLY:        return true;
		case FEATURE_MOD_INTRO:               return true;
		case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
		case FEATURE_COMPLETION_HAS_RULES:    return true;
		case FEATURE_GRADE_HAS_GRADE:         return false;
		case FEATURE_GRADE_OUTCOMES:          return false;
		case FEATURE_BACKUP_MOODLE2:          return true;

		default: return null;
	}
}

/**
 * Adds module specific settings to the settings block
 *
 * @param settings_navigation $settings The settings navigation object
 * @param navigation_node $quickfeedbacknode The node to add module settings to
 */

