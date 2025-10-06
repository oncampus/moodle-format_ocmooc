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

use format_ocmooc\forms\socialform;

require_once($CFG->libdir . '/tablelib.php');

/**
 * Social media links management class for OCMOOC format
 *
 * @package    format_ocmooc
 * @copyright  2025 oncampus GmbH <support@oncampus.de>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class social extends base {
    /**
     * Constructor for the social class
     *
     * @param int $courseid The ID of the course
     * @param \moodle_url $url The URL for the form
     */
    public function __construct($courseid, $url) {
        parent::__construct($courseid, $url);
        $this->mform = new socialform($this->url, ['courseid' => $this->courseid]);

        $this->title = get_string('social', 'format_ocmooc');
    }

    /**
     * Renders the custom view for social media links
     */
    protected function render_view_custom() {
        global $DB, $OUTPUT;

        $socials = $DB->get_records('format_ocmooc_social', ['courseid' => $this->courseid]);
        $socials = array_values($socials);

        echo $OUTPUT->render_from_template('format_ocmooc/social/socials', ['socials' => $socials]);
    }

    /**
     * Renders the custom editor for social media links
     */
    protected function render_editor_custom() {
    }

    /**
     * Handles the form data for social media links
     *
     * @param object $data The form data
     * @return int The ID of the newly created record
     */
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

    /**
     * Renders an overview of all social media links
     */
    public function render_overview() {
        global $DB;

        echo \html_writer::tag('h2', get_string('overview', 'format_ocmooc'));

        $table = new \flexible_table('format_ocmooc_social_overview');
        $table->define_columns([
                'title',
                'url',
                'type',
                'actions',
        ]);
        $table->define_headers([
                get_string('title', 'format_ocmooc'),
                get_string('url', 'format_ocmooc'),
                get_string('type', 'format_ocmooc'),
                get_string('actions', 'format_ocmooc'),
        ]);
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
                    $actions,
            ]);
        }

        $table->finish_html();
    }

    /**
     * Checks if there are any social media links for this course
     *
     * @return bool True if there are social media links, false otherwise
     */
    public function has_content() {
        global $DB;

        return $DB->record_exists('format_ocmooc_social', ['courseid' => $this->courseid]);
    }
}
