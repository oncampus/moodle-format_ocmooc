<?php

namespace format_ocmooc;

use format_ocmooc\forms\socialform;

require_once($CFG->libdir . '/tablelib.php');

class social extends base {

    public function __construct($courseid, $url) {
        parent::__construct($courseid, $url);
        $this->mform = new socialform($this->url, ['courseid' => $this->courseid]);
    }

    protected function render_view_custom() {
        global $DB, $OUTPUT;

        $socials = $DB->get_records('format_ocmooc_social', ['courseid' => $this->courseid]);
        $socials = array_values($socials);

        echo $OUTPUT->render_from_template('format_ocmooc/social/socials', ['socials' => $socials]);
    }

    protected function render_editor_custom() {

    }

    protected function handle_data($data) {
        global $DB;

        $insert = new \stdClass();
        $insert->courseid = $this->courseid;
        $insert->title = $data->title;
        $insert->url = $data->url;
        $insert->type = $data->type;
        $insert->created = time();
        $insert->updated = time();

        return $DB->insert_record('format_ocmooc_social', $insert);
    }

    public function render_overview() {
        global $DB;

        echo \html_writer::tag('h2', get_string('overview', 'format_ocmooc'));

        $table = new \flexible_table('format_ocmooc_social_overview');
        $table->define_columns(array(
                'title',
                'url',
                'type',
                'actions'
        ));
        $table->define_headers(array(
                get_string('title', 'format_ocmooc'),
                get_string('url', 'format_ocmooc'),
                get_string('type', 'format_ocmooc'),
                get_string('actions', 'format_ocmooc'),
        ));
        $table->define_baseurl($this->url);
        $table->sortable(true, 'title', SORT_ASC);
        $table->initialbars(true);
        $table->no_sorting('actions');
        $table->setup();

        $records = $DB->get_records('format_ocmooc_social', ['courseid' => $this->courseid]);
        foreach ($records as $record) {
            $actions = '';

            $table->add_data([
                    $record->title,
                    $record->url,
                    $record->type,
                    $actions
            ]);
        }

        $table->finish_html();
    }

    public function has_content() {
        global $DB;

        return $DB->record_exists('format_ocmooc_social', ['courseid' => $this->courseid]);
    }
}