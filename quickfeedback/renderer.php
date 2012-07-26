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

define ('DISPLAY_HORIZONTAL_LAYOUT', 0);
define ('DISPLAY_VERTICAL_LAYOUT', 1);

class mod_quickfeedback_renderer extends plugin_renderer_base {

    /**
     * Returns HTML to display quickfeedbacks of option
     * @param object $options
     * @param int  $coursemoduleid
     * @param bool $vertical
     * @return string
     */
    public function display_options($options, $coursemoduleid, $vertical = false) {
        $layoutclass = 'horizontal';
        if ($vertical) {
            $layoutclass = 'vertical';
        }
        $target = new moodle_url('/mod/quickfeedback/view.php');
        $attributes = array('method'=>'POST', 'action'=>$target, 'class'=> $layoutclass);

        $html = html_writer::start_tag('form', $attributes);
        $html .= html_writer::start_tag('ul', array('class'=>'quickfeedbacks' ));

        $availableoption = count($options['options']);
        foreach ($options['options'] as $option) {
            $html .= html_writer::start_tag('li', array('class'=>'option'));
            $option->attributes->name = 'answer';
            $option->attributes->type = 'radio';

            $labeltext = $option->text;
            if (!empty($option->attributes->disabled)) {
                $labeltext .= ' ' . get_string('full', 'quickfeedback');
                $availableoption--;
            }

            $html .= html_writer::empty_tag('input', (array)$option->attributes);
            $html .= html_writer::tag('label', $labeltext, array('for'=>$option->attributes->name));
            $html .= html_writer::end_tag('li');
        }
        $html .= html_writer::tag('li','', array('class'=>'clearfloat'));
        $html .= html_writer::end_tag('ul');
        $html .= html_writer::tag('div', '', array('class'=>'clearfloat'));
        $html .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'sesskey', 'value'=>sesskey()));
        $html .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'id', 'value'=>$coursemoduleid));

        if (!empty($options['hascapability']) && ($options['hascapability'])) {
            if ($availableoption < 1) {
               $html .= html_writer::tag('label', get_string('quickfeedbackfull', 'quickfeedback'));
            } else {
                $html .= html_writer::empty_tag('input', array('type'=>'submit', 'value'=>get_string('savemyquickfeedback','quickfeedback'), 'class'=>'button'));
            }

            if (!empty($options['allowupdate']) && ($options['allowupdate'])) {
                $url = new moodle_url('view.php', array('id'=>$coursemoduleid, 'action'=>'delquickfeedback', 'sesskey'=>sesskey()));
                $html .= html_writer::link($url, get_string('removemyquickfeedback','quickfeedback'));
            }
        } else {
            $html .= html_writer::tag('label', get_string('havetologin', 'quickfeedback'));
        }

        $html .= html_writer::end_tag('ul');
        $html .= html_writer::end_tag('form');

        return $html;
    }

    /**
     * Returns HTML to display quickfeedbacks result
     * @param object $quickfeedbacks
     * @param bool $forcepublish
     * @return string
     */
    public function display_result($quickfeedbacks, $forcepublish = false) {
        if (empty($forcepublish)) { //allow the publish setting to be overridden
            $forcepublish = $quickfeedbacks->publish;
        }

        $displaylayout = $quickfeedbacks->display;

        if ($forcepublish) {  //CHOICE_PUBLISH_NAMES
            return $this->display_publish_name_vertical($quickfeedbacks);
        } else { //CHOICE_PUBLISH_ANONYMOUS';
            if ($displaylayout == DISPLAY_HORIZONTAL_LAYOUT) {
                return $this->display_publish_anonymous_horizontal($quickfeedbacks);
            }
            return $this->display_publish_anonymous_vertical($quickfeedbacks);
        }
    }

    /**
     * Returns HTML to display quickfeedbacks result
     * @param object $quickfeedbacks
     * @param bool $forcepublish
     * @return string
     */
    public function display_publish_name_vertical($quickfeedbacks) {
        global $PAGE;
        $html ='';
        $html .= html_writer::tag('h2',format_string(get_string("responses", "quickfeedback")), array('class'=>'main'));

        $attributes = array('method'=>'POST');
        $attributes['action'] = new moodle_url($PAGE->url);
        $attributes['id'] = 'attemptsform';

        if ($quickfeedbacks->viewresponsecapability) {
            $html .= html_writer::start_tag('form', $attributes);
            $html .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'id', 'value'=> $quickfeedbacks->coursemoduleid));
            $html .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'sesskey', 'value'=> sesskey()));
            $html .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'mode', 'value'=>'overview'));
        }

        $table = new html_table();
        $table->cellpadding = 0;
        $table->cellspacing = 0;
        $table->attributes['class'] = 'results names ';
        $table->tablealign = 'center';
        $table->data = array();

        $count = 0;
        ksort($quickfeedbacks->options);

        $columns = array();
        foreach ($quickfeedbacks->options as $optionid => $options) {
            $coldata = '';
            if ($quickfeedbacks->showunanswered && $optionid == 0) {
                $coldata .= html_writer::tag('div', format_string(get_string('notanswered', 'quickfeedback')), array('class'=>'option'));
            } else if ($optionid > 0) {
                $coldata .= html_writer::tag('div', format_string($quickfeedbacks->options[$optionid]->text), array('class'=>'option'));
            }
            $numberofuser = 0;
            if (!empty($options->user) && count($options->user) > 0) {
                $numberofuser = count($options->user);
            }

            $coldata .= html_writer::tag('div', ' ('.$numberofuser. ')', array('class'=>'numberofuser', 'title' => get_string('numberofuser', 'quickfeedback')));
            $columns[] = $coldata;
        }

        $table->head = $columns;

        $coldata = '';
        $columns = array();
        foreach ($quickfeedbacks->options as $optionid => $options) {
            $coldata = '';
            if ($quickfeedbacks->showunanswered || $optionid > 0) {
                if (!empty($options->user)) {
                    foreach ($options->user as $user) {
                        $data = '';
                        if (empty($user->imagealt)){
                            $user->imagealt = '';
                        }

                        if ($quickfeedbacks->viewresponsecapability && $quickfeedbacks->deleterepsonsecapability  && $optionid > 0) {
                            $attemptaction = html_writer::checkbox('attemptid[]', $user->id,'');
                            $data .= html_writer::tag('div', $attemptaction, array('class'=>'attemptaction'));
                        }
                        $userimage = $this->output->user_picture($user, array('courseid'=>$quickfeedbacks->courseid));
                        $data .= html_writer::tag('div', $userimage, array('class'=>'image'));

                        $userlink = new moodle_url('/user/view.php', array('id'=>$user->id,'course'=>$quickfeedbacks->courseid));
                        $name = html_writer::tag('a', fullname($user, $quickfeedbacks->fullnamecapability), array('href'=>$userlink, 'class'=>'username'));
                        $data .= html_writer::tag('div', $name, array('class'=>'fullname'));
                        $data .= html_writer::tag('div','', array('class'=>'clearfloat'));
                        $coldata .= html_writer::tag('div', $data, array('class'=>'user'));
                    }
                }
            }

            $columns[] = $coldata;
            $count++;
        }

        $table->data[] = $columns;
        foreach ($columns as $d) {
            $table->colclasses[] = 'data';
        }
        $html .= html_writer::tag('div', html_writer::table($table), array('class'=>'response'));

        $actiondata = '';
        if ($quickfeedbacks->viewresponsecapability && $quickfeedbacks->deleterepsonsecapability) {
            $selecturl = new moodle_url('#');

            $selectallactions = new component_action('click',"select_all_in", array('div',null,'tablecontainer'));
            $selectall = new action_link($selecturl, get_string('selectall', 'quiz'), $selectallactions);
            $actiondata .= $this->output->render($selectall) . ' / ';

            $deselectallactions = new component_action('click',"deselect_all_in", array('div',null,'tablecontainer'));
            $deselectall = new action_link($selecturl, get_string('selectnone', 'quiz'), $deselectallactions);
            $actiondata .= $this->output->render($deselectall);

            $actiondata .= html_writer::tag('label', ' ' . get_string('withselected', 'quiz') . ' ', array('for'=>'menuaction'));

            $actionurl = new moodle_url($PAGE->url, array('sesskey'=>sesskey(), 'action'=>'delete_confirmation()'));
            $select = new single_select($actionurl, 'action', array('delete'=>get_string('delete')), null, array(''=>get_string('chooseaction', 'quickfeedback')), 'attemptsform');

            $actiondata .= $this->output->render($select);
        }
        $html .= html_writer::tag('div', $actiondata, array('class'=>'responseaction'));

        if ($quickfeedbacks->viewresponsecapability) {
            $html .= html_writer::end_tag('form');
        }

        return $html;
    }


    /**
     * Returns HTML to display quickfeedbacks result
     * @param object $quickfeedbacks
     * @return string
     */
    public function display_publish_anonymous_vertical($quickfeedbacks) {
        global $CHOICE_COLUMN_HEIGHT;

        $html = '';
        $table = new html_table();
        $table->cellpadding = 5;
        $table->cellspacing = 0;
        $table->attributes['class'] = 'results anonymous ';
        $table->data = array();
        $count = 0;
        ksort($quickfeedbacks->options);
        $columns = array();
        $rows = array();

        foreach ($quickfeedbacks->options as $optionid => $options) {
            $numberofuser = 0;
            if (!empty($options->user)) {
               $numberofuser = count($options->user);
            }
            $height = 0;
            $percentageamount = 0;
            if($quickfeedbacks->numberofuser > 0) {
               $height = ($CHOICE_COLUMN_HEIGHT * ((float)$numberofuser / (float)$quickfeedbacks->numberofuser));
               $percentageamount = ((float)$numberofuser/(float)$quickfeedbacks->numberofuser)*100.0;
            }

            $displaydiagram = html_writer::tag('img','', array('style'=>'height:'.$height.'px;width:49px;', 'alt'=>'', 'src'=>$this->output->pix_url('column', 'quickfeedback')));

            $cell = new html_table_cell();
            $cell->text = $displaydiagram;
            $cell->attributes = array('class'=>'graph vertical data');
            $columns[] = $cell;
        }
        $rowgraph = new html_table_row();
        $rowgraph->cells = $columns;
        $rows[] = $rowgraph;

        $columns = array();
        $printskiplink = true;
        foreach ($quickfeedbacks->options as $optionid => $options) {
            $columndata = '';
            $numberofuser = 0;
            if (!empty($options->user)) {
               $numberofuser = count($options->user);
            }

            if ($printskiplink) {
                $columndata .= html_writer::tag('div', '', array('class'=>'skip-block-to', 'id'=>'skipresultgraph'));
                $printskiplink = false;
            }

            if ($quickfeedbacks->showunanswered && $optionid == 0) {
                $columndata .= html_writer::tag('div', format_string(get_string('notanswered', 'quickfeedback')), array('class'=>'option'));
            } else if ($optionid > 0) {
                $columndata .= html_writer::tag('div', format_string($quickfeedbacks->options[$optionid]->text), array('class'=>'option'));
            }
            $columndata .= html_writer::tag('div', ' ('.$numberofuser.')', array('class'=>'numberofuser', 'title'=> get_string('numberofuser', 'quickfeedback')));

            if($quickfeedbacks->numberofuser > 0) {
               $percentageamount = ((float)$numberofuser/(float)$quickfeedbacks->numberofuser)*100.0;
            }
            $columndata .= html_writer::tag('div', format_float($percentageamount,1). '%', array('class'=>'percentage'));

            $cell = new html_table_cell();
            $cell->text = $columndata;
            $cell->attributes = array('class'=>'data header');
            $columns[] = $cell;
        }
        $rowdata = new html_table_row();
        $rowdata->cells = $columns;
        $rows[] = $rowdata;

        $table->data = $rows;

        $header = html_writer::tag('h2',format_string(get_string("responses", "quickfeedback")));
        $html .= html_writer::tag('div', $header, array('class'=>'responseheader'));
        $html .= html_writer::tag('a', get_string('skipresultgraph', 'quickfeedback'), array('href'=>'#skipresultgraph', 'class'=>'skip-block'));
        $html .= html_writer::tag('div', html_writer::table($table), array('class'=>'response'));

        return $html;
    }

    /**
     * Returns HTML to display quickfeedbacks result
     * @param object $quickfeedbacks
     * @return string
     */
    public function display_publish_anonymous_horizontal($quickfeedbacks) {
        global $CHOICE_COLUMN_WIDTH;

        $table = new html_table();
        $table->cellpadding = 5;
        $table->cellspacing = 0;
        $table->attributes['class'] = 'results anonymous ';
        $table->data = array();

        $count = 0;
        ksort($quickfeedbacks->options);

        $rows = array();
        foreach ($quickfeedbacks->options as $optionid => $options) {
            $numberofuser = 0;
            $graphcell = new html_table_cell();
            if (!empty($options->user)) {
               $numberofuser = count($options->user);
            }

            $width = 0;
            $percentageamount = 0;
            $columndata = '';
            if($quickfeedbacks->numberofuser > 0) {
               $width = ($CHOICE_COLUMN_WIDTH * ((float)$numberofuser / (float)$quickfeedbacks->numberofuser));
               $percentageamount = ((float)$numberofuser/(float)$quickfeedbacks->numberofuser)*100.0;
            }
            $displaydiagram = html_writer::tag('img','', array('style'=>'height:50px; width:'.$width.'px', 'alt'=>'', 'src'=>$this->output->pix_url('row', 'quickfeedback')));

            $skiplink = html_writer::tag('a', get_string('skipresultgraph', 'quickfeedback'), array('href'=>'#skipresultgraph'. $optionid, 'class'=>'skip-block'));
            $skiphandler = html_writer::tag('span', '', array('class'=>'skip-block-to', 'id'=>'skipresultgraph'.$optionid));

            $graphcell->text = $skiplink . $displaydiagram . $skiphandler;
            $graphcell->attributes = array('class'=>'graph horizontal');

            $datacell = new html_table_cell();
            if ($quickfeedbacks->showunanswered && $optionid == 0) {
                $columndata .= html_writer::tag('div', format_string(get_string('notanswered', 'quickfeedback')), array('class'=>'option'));
            } else if ($optionid > 0) {
                $columndata .= html_writer::tag('div', format_string($quickfeedbacks->options[$optionid]->text), array('class'=>'option'));
            }
            $columndata .= html_writer::tag('div', ' ('.$numberofuser.')', array('title'=> get_string('numberofuser', 'quickfeedback'), 'class'=>'numberofuser'));

            if($quickfeedbacks->numberofuser > 0) {
               $percentageamount = ((float)$numberofuser/(float)$quickfeedbacks->numberofuser)*100.0;
            }
            $columndata .= html_writer::tag('div', format_float($percentageamount,1). '%', array('class'=>'percentage'));

            $datacell->text = $columndata;
            $datacell->attributes = array('class'=>'header');

            $row = new html_table_row();
            $row->cells = array($datacell, $graphcell);
            $rows[] = $row;
        }

        $table->data = $rows;

        $html = '';
        $header = html_writer::tag('h2',format_string(get_string("responses", "quickfeedback")));
        $html .= html_writer::tag('div', $header, array('class'=>'responseheader'));
        $html .= html_writer::table($table);

        return $html;
    }
}

