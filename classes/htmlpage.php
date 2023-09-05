<?php

namespace format_ocmooc;

use core\output\notification;
use format_ocmooc\forms\htmlpageform;

require_once($CFG->libdir . '/tablelib.php');

class htmlpage extends base {

    private $action;

    private $id;

    public function __construct($courseid, $url, $action = null, $id = null) {
        global $DB;

        parent::__construct($courseid, $url);
        $this->mform = new htmlpageform($url);

        $this->id = $id;
        $this->action = $action;

        if (isset($action)) {
            $this->setdatadb = 'format_ocmooc_htmlsite';
        }

        if (isset($id)) {
            $this->setdataparams = ['id' => $id];

            $title = $DB->get_field('format_ocmooc_htmlsite', 'title', ['id' => $id, 'courseid' => $courseid]);

            $this->title = $title;
        } else {
            $this->title = get_string('htmlpage', 'format_ocmooc');
            $this->heading = get_string('htmlpage', 'format_ocmooc');
        }
    }

    protected function render_view_custom() {
        global $DB;

        if (has_capability('format/ocmooc:edit', $this->context)) {
            $urlparams = [
                    'courseid' => $this->courseid,
                    'id' => $this->id,
                    'action' => 'edit',
                    'backurl' => $this->url,
            ];

            $editurl = new \moodle_url('/course/format/ocmooc/edit/htmlpage.php', $urlparams);
            echo \html_writer::link($editurl, get_string('edit', 'format_ocmooc'), ['class' => 'btn btn-primary my-2']);
        }

        $htmlsite = $DB->get_record('format_ocmooc_htmlsite', ['id' => $this->id, 'courseid' => $this->courseid]);

        if (!$htmlsite) {
            return;
        }
        echo \html_writer::tag('h2', $htmlsite->title);
        echo \html_writer::tag('div', $htmlsite->content, ['class' => '']);
    }

    protected function render_editor_custom() {

    }

    public function render_overview() {
        global $DB;

        echo \html_writer::tag('h2', get_string('overview', 'format_ocmooc'));

        $createurl = new \moodle_url($this->url);
        $createurl->remove_all_params();
        $createurl->params(['courseid' => $this->courseid, 'action' => 'create']);
        echo \html_writer::link($createurl, get_string('create', 'format_ocmooc'), ['class' => 'btn btn-primary my-2']);

        $table = new \flexible_table('format_ocmooc_htmlsites_overview');
        $table->define_columns(array(
                'title',
                'created',
                'updated',
                'actions'
        ));
        $table->define_headers(array(
                get_string('title', 'format_ocmooc'),
                get_string('created', 'format_ocmooc'),
                get_string('updated', 'format_ocmooc'),
                get_string('actions', 'format_ocmooc'),
        ));
        $table->define_baseurl($this->url);
        $table->sortable(true, 'title', SORT_ASC);
        $table->initialbars(true);
        $table->no_sorting('actions');
        $table->setup();

        $records = $DB->get_records('format_ocmooc_htmlsite', ['courseid' => $this->courseid]);
        foreach ($records as $record) {
            $viewurl = new \moodle_url('/course/format/ocmooc/views/htmlpage.php',
                    ['courseid' => $this->courseid, 'id' => $record->id]);
            $actions = \html_writer::link($viewurl, get_string('view', 'format_ocmooc'), ['class' => 'btn btn-primary mx-1']);

            $editurl = new \moodle_url('/course/format/ocmooc/edit/htmlpage.php',
                    ['action' => 'edit', 'courseid' => $this->courseid, 'id' => $record->id]);
            $actions .= \html_writer::link($editurl, get_string('edit', 'format_ocmooc'), ['class' => 'btn btn-secondary mx-1']);

            $deleteurl = new \moodle_url('/course/format/ocmooc/edit/htmlpage.php',
                    ['action' => 'delete', 'courseid' => $this->courseid, 'id' => $record->id]);
            $actions .= \html_writer::link($deleteurl, get_string('delete', 'format_ocmooc'),
                    ['class' => 'btn btn-secondary mx-1']);

            $table->add_data([
                    $record->title,
                    date('H:i:s d.m.Y', $record->created),
                    date('H:i:s d.m.Y', $record->updated),
                    $actions
            ]);
        }

        $table->finish_html();
    }

    protected function set_data() {
        global $DB;

        if (!isset($this->setdatadb) || !isset($this->mform)) {
            return;
        }

        $params = ['courseid' => $this->courseid];
        if (isset($this->setdataparams)) {
            $params = array_merge($params, $this->setdataparams);
        }

        $data = $DB->get_record($this->setdatadb, $params);
        if($data){
        $data->content = ['text' => $data->content, 'format' => 1];
        $this->mform->set_data($data);
        }
    }

    protected function handle_data($data) {
        global $DB;

        if ($this->action == 'edit' && isset($this->id)) {
            $update = new \stdClass();
            $update->id = $this->id;
            $update->title = $data->title;
            $update->content = $data->content['text'];

            return $DB->update_record($this->setdatadb, $update);
        } else {
            $insert = new \stdClass();
            $insert->courseid = $this->courseid;
            $insert->title = $data->title;
            $insert->content = $data->content['text'];
            $insert->sortorder = 0;
            $insert->created = time();
            $insert->updated = time();

            return $DB->insert_record($this->setdatadb, $insert);
        }
    }

    public function delete() {
        global $DB;

        if (isset($this->setdatadb) && isset($this->id)) {
            $DB->delete_records($this->setdatadb, ['id' => $this->id, 'courseid' => $this->courseid]);

            $backurl = new \moodle_url($this->url);
            $backurl->remove_all_params();
            $backurl->params(['courseid' => $this->courseid]);
            redirect($backurl, get_string('deleted', 'format_ocmooc'), null, notification::NOTIFY_SUCCESS);
        }
    }
}