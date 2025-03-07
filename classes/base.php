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

/**
 * Base abstract class for OCMOOC format components.
 *
 * This class provides common functionality for various components
 * of the OCMOOC course format, including page setup, rendering,
 * and form handling.
 *
 * @package    format_ocmooc
 * @copyright  2025 oncampus GmbH <support@oncampus.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base {
    /**
     * The course ID.
     *
     * @var int
     */
    protected $courseid;

    /**
     * The URL for the current page.
     *
     * @var \moodle_url
     */
    protected $url;

    /**
     * The form instance.
     *
     * @var \moodleform
     */
    protected $mform;

    /**
     * The database table name for retrieving data.
     *
     * @var string
     */
    protected $setdatadb;

    /**
     * Additional parameters for data retrieval.
     *
     * @var array|null
     */
    protected $setdataparams = null;

    /**
     * The page title.
     *
     * @var string
     */
    protected $title;

    /**
     * The page heading.
     *
     * @var string
     */
    protected $heading;

    /**
     * The course context.
     *
     * @var \context_course
     */
    protected $context;

    /**
     * The course object.
     *
     * @var \stdClass
     */
    protected $course;

    /**
     * Constructor for the base class.
     *
     * Initializes the base properties needed for all OCMOOC format components.
     *
     * @param int $courseid The ID of the course
     * @param \moodle_url $url The URL for the current page
     */
    public function __construct($courseid, $url) {
        $this->courseid = $courseid;
        $this->course = get_course($courseid);
        $this->context = \context_course::instance($courseid);
        $this->url = $url;
    }

    /**
     * Sets up the page with appropriate title, heading, and context.
     *
     * This method configures the global $PAGE object with the necessary
     * properties for rendering the page correctly.
     */
    public function setup_page() {
        global $PAGE, $DB;

        if (!isset($this->title)) {
            $this->title = get_string('default_title', 'format_ocmooc');
        }

        if (!isset($this->heading)) {
            $coursename = $DB->get_field('course', 'fullname', ['id' => $this->courseid]);
            $this->heading = format_string($coursename);
        }

        $PAGE->set_url($this->url);
        $PAGE->set_title($this->title);
        $PAGE->set_heading($this->heading);
        $PAGE->set_context($this->context);
    }

    /**
     * Renders the view for the component.
     *
     * This method displays the MOOC navigation bar and then calls
     * the custom rendering method that must be implemented by child classes.
     */
    public function render_view() {
        global $OUTPUT;

        $moocnav = \format_ocmooc\moocnav::get_moocnav_entries();
        $moocnavdropdown = \format_ocmooc\moocnav::get_drowdown_items();
        echo \html_writer::start_div('w-100', ['id' => 'moocnav']);
        echo $OUTPUT->render_from_template(
            'format_ocmooc/local/moocnav/headernav',
            ['moocnav' => $moocnav, 'moocnavdropdown' => $moocnavdropdown, 'showdropdown' => !empty($moocnavdropdown)]
        );
        echo \html_writer::end_div();

        $this->render_view_custom();
    }

    /**
     * Custom rendering method to be implemented by child classes.
     *
     * This method should contain the specific rendering logic for each component.
     */
    abstract protected function render_view_custom();

    /**
     * Renders the editor interface for the component.
     *
     * This method sets up the form data, displays a header (if requested),
     * calls the custom editor rendering method, and shows the form.
     *
     * @param bool $showheader Whether to show the editor header
     */
    public function render_editor($showheader = true) {
        $this->set_data();

        if ($showheader) {
            echo \html_writer::tag('h2', get_string('editor', 'format_ocmooc'));
        }

        $this->render_editor_custom();
        $this->show_form();
    }

    /**
     * Custom editor rendering method to be implemented by child classes.
     *
     * This method should contain the specific editor rendering logic for each component.
     */
    abstract protected function render_editor_custom();

    /**
     * Renders an overview of the component.
     *
     * This abstract method should be implemented by child classes to provide
     * a summary or dashboard view of the component.
     */
    abstract public function render_overview();

    /**
     * Handles form submission and redirects accordingly.
     *
     * This method processes form cancellations and submissions, calling the
     * handle_data method when form data is submitted successfully.
     *
     * @param bool $reseturl Whether to reset the URL parameters on redirect
     */
    public function handle_form($reseturl = false) {
        if (!isset($this->mform)) {
            return;
        }

        $backurl = optional_param('backurl', null, PARAM_URL);
        if (isset($backurl)) {
            $url = new \moodle_url($backurl);
        } else if ($reseturl) {
            $url = $this->url;
            $url->remove_all_params();
            $url->params(['courseid' => $this->courseid]);
        } else {
            $url = new \moodle_url('/course/view.php', ['id' => $this->courseid]);
        }

        if ($this->mform->is_cancelled()) {
            redirect($url);
        } else if ($fromform = $this->mform->get_data()) {
            $handled = $this->handle_data($fromform);
            if ($handled) {
                redirect(
                    $url,
                    get_string('success', 'format_ocmooc'),
                    null,
                    \core\output\notification::NOTIFY_SUCCESS
                );
            } else {
                redirect(
                    $url,
                    get_string('failed', 'format_ocmooc'),
                    null,
                    \core\output\notification::NOTIFY_ERROR
                );
            }
        }
    }

    /**
     * Processes and stores component data.
     *
     * This abstract method should be implemented by child classes to handle
     * the storage of form data in the database.
     *
     * @param \stdClass $data The form data to be processed
     * @return bool|int Returns true/false for success/failure or a record ID
     */
    abstract protected function handle_data($data);

    /**
     * Sets the form data from the database.
     *
     * This method retrieves data from the database table specified in $setdatadb
     * and sets it as the default values for the form fields.
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
        $this->mform->set_data($data);
    }

    /**
     * Displays the form.
     *
     * This private method renders the form if it has been initialized.
     */
    private function show_form() {
        if (!isset($this->mform)) {
            return;
        }

        $this->mform->display();
    }
}
