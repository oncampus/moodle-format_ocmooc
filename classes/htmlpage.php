<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace format_ocmooc;

defined('MOODLE_INTERNAL') || die();

use core\output\notification;
use format_ocmooc\forms\htmlpageform;
use context;

require_once($CFG->libdir . '/tablelib.php');

/**
 * HTML page management class for the OCMOOC format
 *
 * This class handles the display, creation, editing, and deletion of HTML pages
 * within the OCMOOC course format.
 *
 * @package   format_ocmooc
 * @copyright 2025 oncampus GmbH <info@oncampus.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class htmlpage extends base {
    /**
     * The current action being performed (create, edit, delete)
     * @var string|null
     */
    private $action;

    /**
     * The ID of the HTML page being viewed or edited
     * @var int|null
     */
    private $id;

    /**
     * Constructor for the htmlpage class
     *
     * @param int $courseid The ID of the course
     * @param \moodle_url $url The current URL
     * @param string|null $action The action to perform (create, edit, delete)
     * @param int|null $id The ID of the HTML page
     */
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

    /**
     * Render the view for a specific HTML page
     */
    protected function render_view_custom() {
        global $DB;

        if (has_capability('format/ocmooc:edit', context::instance_by_id($this->context->id))) {
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

    /**
     * Render the editor for HTML pages
     */
    protected function render_editor_custom() {
    }

    /**
     * Render an overview of all HTML pages in the course
     */
    public function render_overview() {
        global $DB;

        echo \html_writer::tag('h2', get_string('overview', 'format_ocmooc'));

        $createurl = new \moodle_url($this->url);
        $createurl->remove_all_params();
        $createurl->params(['courseid' => $this->courseid, 'action' => 'create']);
        echo \html_writer::link($createurl, get_string('create', 'format_ocmooc'), ['class' => 'btn btn-primary my-2']);

        $table = new \flexible_table('format_ocmooc_htmlsites_overview');
        $table->define_columns([
                'title',
                'created',
                'updated',
                'actions',
        ]);
        $table->define_headers([
                get_string('title', 'format_ocmooc'),
                get_string('created', 'format_ocmooc'),
                get_string('updated', 'format_ocmooc'),
                get_string('actions', 'format_ocmooc'),
        ]);
        $table->define_baseurl($this->url);
        $table->sortable(true, 'title', SORT_ASC);
        $table->initialbars(true);
        $table->no_sorting('actions');
        $table->setup();

        $records = $DB->get_records('format_ocmooc_htmlsite', ['courseid' => $this->courseid]);
        foreach ($records as $record) {
            $viewurl = new \moodle_url(
                '/course/format/ocmooc/views/htmlpage.php',
                ['courseid' => $this->courseid, 'id' => $record->id]
            );
            $actions = \html_writer::link($viewurl, get_string('view', 'format_ocmooc'), ['class' => 'btn btn-primary mx-1']);

            $editurl = new \moodle_url(
                '/course/format/ocmooc/edit/htmlpage.php',
                ['action' => 'edit', 'courseid' => $this->courseid, 'id' => $record->id]
            );
            $actions .= \html_writer::link($editurl, get_string('edit', 'format_ocmooc'), ['class' => 'btn btn-secondary mx-1']);

            $deleteurl = new \moodle_url(
                '/course/format/ocmooc/edit/htmlpage.php',
                ['action' => 'delete', 'courseid' => $this->courseid, 'id' => $record->id]
            );
            $actions .= \html_writer::link(
                $deleteurl,
                get_string('delete', 'format_ocmooc'),
                ['class' => 'btn btn-secondary mx-1']
            );

            $table->add_data([
                    $record->title,
                    date('H:i:s d.m.Y', $record->created),
                    date('H:i:s d.m.Y', $record->updated),
                    $actions,
            ]);
        }

        $table->finish_html();
    }

    /**
     * Set form data from the database record
     */
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
        if ($data) {
            $data->content = ['text' => $data->content, 'format' => 1];
            $this->mform->set_data($data);
        }
    }

    /**
     * Handle form data submission
     *
     * @param object $data The form data
     * @return bool|int Result of the database operation
     */
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

    /**
     * Delete an HTML page
     */
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
