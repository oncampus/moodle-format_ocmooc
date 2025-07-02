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

/**
 * Participants management class.
 *
 * @package    format_ocmooc
 * @copyright  2025 oncampus GmbH <support@oncampus.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ocmooc;

use coding_exception;
use core_group\output\group_details;
use dml_exception;
use format_ocmooc\forms\participantsform;
use format_ocmooc\forms\searchform;
use moodle_exception;
use moodle_url;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/enrol/locallib.php');

require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->dirroot . '/blocks/online_users/lib.php');

/**
 * Handles the participant listing and related operations.
 *
 * This class manages the rendering of the participants list,
 * search functionality, and user actions such as unenrolling.
 *
 * @package    format_ocmooc
 */
class participants extends base {
    /**
     * List of enrolment methods that allow self-unenrolment.
     *
     * This array defines which enrolment methods support self-unenrolment
     * and specifies the required capability and the unenrolment URL.
     *
     * @var array
     */
    const UNENROLS = [
            'manual' => [
                    'capability' => 'enrol/manual:unenrolself',
                    'url' => '/enrol/manual/unenrolself.php',
            ],
            'autoenrol' => [
                    'capability' => 'enrol/autoenrol:unenrolself',
                    'url' => '/enrol/autoenrol/unenrolself.php',
            ],
    ];

    /**
     * Holds participant data retrieved from the database.
     *
     * @var stdClass
     */
    private $data;

    /**
     * Counter used for numbering participants in the list.
     *
     * @var int
     */
    private $counter = 1;

    /**
     * Stores time-related strings for formatting last access time.
     *
     * @var stdClass
     */
    private $datestring;

    /**
     * Instance of the search form used to filter participants.
     *
     * @var searchform
     */
    private $searchform;

    /**
     * Constructor for the participants class.
     *
     * Initializes the participants form, loads language strings for time formatting,
     * and sets up the search form.
     *
     * @param int $courseid The ID of the course.
     * @param moodle_url $url The URL for navigation.
     */
    public function __construct($courseid, $url) {
        parent::__construct($courseid, $url);
        $this->mform = new participantsform($this->url, ['courseid' => $this->courseid]);
        $this->setdatadb = 'format_ocmooc_parts';

        $this->datestring = new \stdClass();
        $this->datestring->year = get_string('year');
        $this->datestring->years = get_string('years');
        $this->datestring->day = get_string('day');
        $this->datestring->days = get_string('days');
        $this->datestring->hour = get_string('hour');
        $this->datestring->hours = get_string('hours');
        $this->datestring->min = get_string('min');
        $this->datestring->mins = get_string('mins');
        $this->datestring->sec = get_string('sec');
        $this->datestring->secs = get_string('secs');

        $this->searchform = new searchform($this->url);

        $this->title = get_string('participants', 'format_ocmooc');
    }

    /**
     * Checks if the current user is a guest and redirects if access is not allowed.
     *
     * This function verifies if the user is a guest in the course context.
     * If guest access is disabled, the user is redirected to the course page.
     * @throws moodle_exception If guest access is not allowed and the user is redirected.
     */
    public function check_guest_access() {
        $coursecontext = \context_course::instance($this->courseid);
        if (is_guest($coursecontext)) {
            $allowed = get_config('format_ocmooc', 'participants_guest');
            if (!$allowed) {
                redirect(new moodle_url('/course/view.php', ['id' => $this->courseid]));
            }
        }
    }

    /**
     * Renders the custom participant list view.
     *
     * This method retrieves participant data, applies search filters,
     * and displays the list in a flexible table. It also includes
     * pagination and the option to show a world map.
     */
    protected function render_view_custom() {
        global $PAGE, $COURSE, $DB, $OUTPUT, $CFG, $USER;

        $showlist = get_config('format_ocmooc', 'displaylist');
        $showmap = get_config('format_ocmooc', 'displayworldmap');

        if ($showmap) {
            echo $OUTPUT->render_from_template('format_ocmooc/map/map', ['courseid' => $this->courseid]);
            if (!$showlist) {
                $context = \context_course::instance($this->courseid);
                $usercount = count_enrolled_users($context);
                echo \html_writer::tag('p', get_string('enroleduserscount', 'format_ocmooc', $usercount));
            }
        }
        if ($showlist) {
            $page = optional_param('page', 0, PARAM_INT);
            $perpage = optional_param('perpage', 10, PARAM_INT);

            $data = $this->get_data();

            $manager = new \course_enrolment_manager($PAGE, $COURSE);

            [$header, $titles, $nosort] = $this->prepare_table_header();

            $table = new \flexible_table('user-index-participants-' . $this->courseid);
            $table->define_columns($header);
            $table->define_headers($titles);
            $table->define_baseurl($this->url);

            $table->set_attribute('cellspacing', '0');
            $table->set_attribute('id', 'participants');
            $table->set_attribute('class', 'generaltable generalbox ');

            foreach ($nosort as $col) {
                $table->no_sorting($col);
            }

            $table->sortable(true);

            $table->setup();

            $instances = $manager->get_enrolment_instances();
            $ids = [];
            foreach ($instances as $instance) {
                $ids[] = $instance->id;
            }

            unset($instance);
            unset($instances);

            [$insql, $params] = $DB->get_in_or_equal($ids);
            // Raise memory limit for big courses.
            raise_memory_limit(MEMORY_EXTRA);
            $sql = "SELECT u.* FROM {user} u
                INNER JOIN {user_enrolments} ue
                    ON u.id = ue.userid
                WHERE enrolid $insql";

            [$sql, $paramssearch] = $this->handle_search_data($sql);

            if ($paramssearch) {
                $params = array_merge($params, $paramssearch);
            }

            $sort = $table->get_sql_sort();
            if ($sort) {
                $sql .= " ORDER BY $sort";
            }

            $userscount = $DB->get_records_sql($sql, $params);
            $userscount = count($userscount);
            $users = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

            foreach ($users as $user) {
                $user = $this->prepare_user_entry($user);
                $table->add_data($user);
                unset($user);
            }

            // There was a problem with count_records_sql. The count was incorrect.
            $total = $DB->get_records_sql($sql, $params);
            $total = count($total);

            $this->searchform->display();

            echo $OUTPUT->paging_bar($total, $page, $perpage, $this->url);

            echo \html_writer::tag('p', get_string('countparticipantsfound', 'core_user', $userscount));
            $table->finish_html();

            echo $OUTPUT->paging_bar($total, $page, $perpage, $this->url);

            unset($table);
        }
        $this->show_unenrol_button();
    }

    /**
     * Prepares the table header for the participant list.
     *
     * This method defines the columns, headers, and sortable properties
     * for the participant list table. It dynamically adjusts based on
     * available user data settings.
     *
     * @return array Contains three arrays:
     *               - List of column identifiers
     *               - List of column headers
     *               - List of non-sortable columns
     * @throws coding_exception If a required language string is missing.
     */
    private function prepare_table_header(): array {
        $data = $this->get_data();

        $header = [];
        $titles = [];
        $notsortable = [];

        if ($data->profilepicture) {
            $notsortable[] = 'profilepicture';
            $header[] = 'profilepicture';
            $titles[] = get_string('userpic', );
        }

        switch ($data->namedisplay) {
            case 1:
                $header[] = 'username';
                $titles[] = get_string('username');
                break;
            case 2:
            case 3:
                $notsortable[] = 'name';
                $header[] = 'name';
                $titles[] = get_string('name');
                break;
            default:
                $header[] = 'fullname';
                $titles[] = get_string('fullname');
                break;
        }

        if ($data->email) {
            $header[] = 'email';
            $titles[] = get_string('email');
        }

        if ($data->town) {
            $header[] = 'city';
            $titles[] = get_string('city');
        }

        if ($data->country) {
            $header[] = 'country';
            $titles[] = get_string('country');
        }

        if ($data->roles) {
            $notsortable[] = 'roles';
            $header[] = 'roles';
            $titles[] = get_string('roles');
        }

        if ($data->groups) {
            $notsortable[] = 'groups';
            $header[] = 'groups';
            $titles[] = get_string('groups');
        }

        if ($data->badges) {
            $notsortable[] = 'badges';
            $header[] = 'badges';
            $titles[] = get_string('badges');
        }

        if ($data->lastaccess) {
            $header[] = 'lastaccess';
            $titles[] = get_string('lastaccess');
        }

        return [$header, $titles, $notsortable];
    }

    /**
     * Displays the unenrolment button if the user has permission.
     *
     * This method checks if the current user has the capability to unenrol
     * themselves from the course. If so, an unenrolment button is rendered.
     */
    private function show_unenrol_button() {
        global $OUTPUT;

        if ($params = $this->get_unenrol_url()) {
            list($url, $string) = $params;

            echo \html_writer::start_div('mt-2 text-right');
            echo $OUTPUT->single_button($url, $string, 'post');
            echo \html_writer::end_div();
        }
    }

    /**
     * Retrieves the unenrolment URL for the current user if allowed.
     *
     * This method checks whether the user is enrolled via a method that allows
     * self-unenrolment. If the user has the necessary capability, it returns
     * the appropriate unenrolment URL and button text.
     *
     * @return array|false An array containing:
     *                     - moodle_url The unenrolment URL
     *                     - string The button label for unenrolment
     *                     Returns false if unenrolment is not allowed.
     * @throws coding_exception If a required language string cannot be retrieved.
     * @throws dml_exception If an error occurs while querying the database.
     * @throws moodle_exception If the user does not have permission to unenrol.
     */
    public function get_unenrol_url() {
        global $DB, $USER;

        if ($enrols = $DB->get_records('enrol', ['courseid' => $this->courseid, 'status' => 0])) {
            foreach ($enrols as $enrol) {
                $params = [
                        'enrolid' => $enrol->id,
                        'status' => 0,
                        'userid' => $USER->id,
                ];
                if ($DB->record_exists('user_enrolments', $params)) {
                    if (array_key_exists($enrol->enrol, self::UNENROLS)) {
                        $unenrolparams = self::UNENROLS[$enrol->enrol];

                        if (has_capability($unenrolparams['capability'], $this->context)) {
                            $string = get_string('unenrolme', 'enrol', $this->course->fullname ?? $this->course->shortname);
                            $url = new moodle_url($unenrolparams['url'], ['enrolid' => $enrol->id]);
                            return [$url, $string];
                        }
                    }
                }
            }
        }
        return false;
    }

    /**
     * Prepares the user entry for display in the participants table.
     *
     * This method retrieves relevant user information, formats it according to
     * the selected display settings, and returns an array containing the
     * formatted user data.
     *
     * @param stdClass $user The user object containing profile information.
     * @return array An array containing formatted user data for table display.
     *
     * @throws dml_exception If there is an error retrieving user roles or groups.
     * @throws coding_exception If a required language string is missing.
     */
    private function prepare_user_entry($user): array {
        global $OUTPUT, $DB;

        $page = optional_param('page', 0, PARAM_INT);
        $perpage = optional_param('perpage', 10, PARAM_INT);

        $data = $this->get_data();

        $userdata = [];

        switch ($data->namedisplay) {
            case 1:
                if ($data->profilepicture) {
                    $userdata[] = $OUTPUT->user_picture($user, [
                        'size' => 35,
                        'courseid' => $this->courseid,
                    ]);
                }
                $userdata[] = $user->username;
                break;
            case 2:
                if ($data->profilepicture) {
                    $picture = $OUTPUT->user_picture($user, [
                        'size' => 35,
                        'courseid' => $this->courseid,
                        'link' => false,
                    ]);
                    $picture = preg_replace('/\s(title|aria-label)="[^"]*"/', '', $picture);
                    $userdata[] = $picture;
                }
                $userdata[] = "User " . ($this->counter + ($page * $perpage));
                $this->counter++;
                break;
            case 3:
                if ($data->profilepicture) {
                    $userdata[] = $OUTPUT->user_picture($user, [
                        'size' => 35,
                        'courseid' => $this->courseid,
                    ]);
                }
                $userdata[] = $user->firstname . ' ' . mb_substr($user->lastname, 0, 1) . '.';
                break;
            default:
                if ($data->profilepicture) {
                    $userdata[] = $OUTPUT->user_picture($user, [
                        'size' => 35,
                        'courseid' => $this->courseid,
                    ]);
                }
                $userdata[] = fullname($user);
        }

        if ($data->email) {
            $userdata[] = $user->email;
        }

        if ($data->town) {
            $userdata[] = $user->city;
        }

        if ($data->country) {
            $userdata[] = $user->country;
        }

        if ($data->roles) {
            $roles = get_user_roles(\context_course::instance($this->courseid), $user->id, false);
            $rolesdata = [];
            foreach ($roles as $role) {
                $rolesdata[] = \html_writer::tag('span', $role->fullname ?? $role->shortname, ['class' => '']);
            }
            $seperator = \html_writer::tag('span', ', ', ['class' => '']);
            $userdata[] = implode($seperator, $rolesdata);
        }

        if ($data->groups) {
            $groupsarr = \groups_get_user_groups($this->courseid, $user->id)[0];
            $groupsdata = [];
            foreach ($groupsarr as $group) {
                if ($group) {
                    $group = \groups_get_group($group);
                    $groupsdata[] = \html_writer::tag('span', $group->name, ['class' => '']);
                }
            }

            $seperator = \html_writer::tag('span', ', ', ['class' => '']);
            $userdata[] = implode($seperator, $groupsdata);
        }

        if ($data->badges) {
            $badges = badges_get_user_badges($user->id, $this->courseid);
            $images = [];
            foreach ($badges as $badge) {
                $imageurl = moodle_url::make_pluginfile_url(\context_course::instance($this->courseid)->id, 'badges', 'badgeimage',
                        $badge->id, '/', 'f1', false);
                $images[] = \html_writer::img($imageurl, $badge->name, ['style' => 'width: 30px; heigth: 30px;']);
            }

            $seperator = \html_writer::tag('span', ', ', ['class' => '']);
            $userdata[] = implode($seperator, $images);
        }

        if ($data->lastaccess) {
            $userdata[] = format_time(time() - $user->lastaccess, $this->datestring);
        }

        return $userdata;
    }

    /**
     * Retrieves and caches participant data for the current course.
     *
     * This method fetches participant-related settings from the database
     * and caches them for subsequent use. If the user is a guest,
     * it retrieves default guest settings instead.
     *
     * @return stdClass An object containing participant-related settings.
     *
     * @throws dml_exception If an error occurs while querying the database.
     */
    private function get_data(): stdClass {
        global $DB;

        if (!isset($this->data)) {
            $context = \context_course::instance($this->courseid);
            if (is_guest(($context))) {
                $data = new \stdClass();

                $data->profilepicture = get_config('format_ocmooc', 'profilepicture_guest');
                $data->namedisplay = get_config('format_ocmooc', 'namedisplay_guest');
                $data->email = get_config('format_ocmooc', 'email_guest');
                $data->town = get_config('format_ocmooc', 'town_guest');
                $data->country = get_config('format_ocmooc', 'country_guest');
                $data->badges = get_config('format_ocmooc', 'badges_guest');
                $data->roles = get_config('format_ocmooc', 'roles_guest');
                $data->groups = get_config('format_ocmooc', 'groups_guest');
                $data->lastaccess = get_config('format_ocmooc', 'lastaccess_guest');
            } else {
                $data = $DB->get_record($this->setdatadb, ['courseid' => $this->courseid]);
                if (!$data) {
                    $data = new \stdClass();
                    $data->courseid = $this->courseid;
                    $data->profilepicture = get_config('format_ocmooc', 'profilepicture');
                    $data->namedisplay = get_config('format_ocmooc', 'namedisplay');
                    $data->email = get_config('format_ocmooc', 'email');
                    $data->town = get_config('format_ocmooc', 'town');
                    $data->country = get_config('format_ocmooc', 'country');
                    $data->badges = get_config('format_ocmooc', 'badges');
                    $data->roles = get_config('format_ocmooc', 'roles');
                    $data->groups = get_config('format_ocmooc', 'groups');
                    $data->lastaccess = get_config('format_ocmooc', 'lastaccess');
                    $data->created = time();
                    $data->edited = time();

                    $DB->insert_record('format_ocmooc_parts', $data);
                }
            }
            $this->data = $data;
        }

        return $this->data;
    }

    /**
     * Handles search data for filtering participants.
     *
     * This method processes the submitted search form data and modifies the given SQL query
     * to include filtering conditions based on the user's input. It also prepares the necessary
     * query parameters for secure SQL execution.
     *
     * @param string $sql The base SQL query to be modified.
     * @return array An array containing:
     *               - string The modified SQL query with search conditions.
     *               - array The parameters for the modified SQL query.
     *
     * @throws coding_exception If a required language string cannot be retrieved.
     * @throws dml_exception If there is an error executing the database query.
     * @throws moodle_exception If there is an issue with user permissions or form processing.
     */
    private function handle_search_data(string $sql): array {
        if ($this->searchform->is_cancelled()) {
            redirect($this->url);
        } else if ($fromform = $this->searchform->get_data()) {
            $data = $this->get_data();
            $searchtext = "%$fromform->searchtext%";

            $sql .= " AND ";

            $sqlwhere = [];

            $params = [];

            switch ($data->namedisplay) {
                case 1:
                    $sqlwhere[] = 'u.username LIKE ?';
                    $params[] = $searchtext;
                    break;
                case 2:
                    break;
                default:
                    $sqlwhere[] = 'u.firstname LIKE ? OR u.lastname LIKE ?';
                    $params[] = $searchtext;
                    $params[] = $searchtext;
                    break;
            }

            if ($data->email) {
                $sqlwhere[] = 'u.email LIKE ?';
                $params[] = $searchtext;
            }

            if ($data->town) {
                $sqlwhere[] = 'u.city LIKE ?';
                $params[] = $searchtext;
            }

            if ($data->country) {
                $sqlwhere[] = 'u.city LIKE ?';
                $params[] = $searchtext;
            }

            $sql .= '(' . implode(' OR ', $sqlwhere) . ')';
        }

        return [$sql, $params ?? false];
    }

    /**
     * Renders a custom editor view.
     *
     * This method is currently not implemented but can be extended
     * to provide a customized editor interface for participants.
     */
    protected function render_editor_custom() {

    }

    /**
     * Renders an overview of participants.
     *
     * This method is currently not implemented but can be extended
     * to provide a summary or dashboard view of course participants.
     */
    public function render_overview() {

    }

    /**
     * Processes and stores participant settings data.
     *
     * This method saves or updates participant-related settings in the database.
     * If a record for the current course exists, it updates it; otherwise,
     * a new record is inserted.
     *
     * @param stdClass $data The data object containing participant settings.
     * @return bool|int Returns true if the record was updated, or the new record ID if inserted.
     *
     * @throws dml_exception If there is an error while interacting with the database.
     */
    protected function handle_data($data) {
        global $DB;

        $id = $DB->get_field('format_ocmooc_parts', 'id', ['courseid' => $this->courseid]);

        $record = new \stdClass();
        if ($id) {
            $record->id = $id;
        }
        $record->courseid = $this->courseid;
        $record->profilepicture = $data->profilepicture ?? false;
        $record->namedisplay = $data->namedisplay;
        $record->email = $data->email ?? false;
        $record->town = $data->town ?? false;
        $record->country = $data->country ?? false;
        $record->badges = $data->badges ?? false;
        $record->roles = $data->roles ?? false;
        $record->groups = $data->groups ?? false;
        $record->lastaccess = $data->lastaccess ?? false;
        if (!$id) {
            $record->created = time();
        }
        $record->edited = time();

        if ($id) {
            return $DB->update_record('format_ocmooc_parts', $record);
        } else {
            return $DB->insert_record('format_ocmooc_parts', $record);
        }
    }
}
