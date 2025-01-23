<?php

namespace format_ocmooc;

use core_group\output\group_details;
use dml_exception;
use format_ocmooc\forms\participantsform;
use format_ocmooc\forms\searchform;
use stdClass;

require_once($CFG->dirroot . '/enrol/locallib.php');

require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->dirroot . '/blocks/online_users/lib.php');

class participants extends base {

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

    private $data;

    private $counter = 1;

    private $datestring;

    private $searchform;

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

    public function check_guest_access() {
        $coursecontext = \context_course::instance($this->courseid);
        if (is_guest($coursecontext)) {
            $allowed = get_config('format_ocmooc', 'participants_guest');
            if (!$allowed) {
                redirect(new \moodle_url('/course/view.php', ['id' => $this->courseid]));
            }
        }
    }

    protected function render_view_custom() {
        global $PAGE, $COURSE, $DB, $OUTPUT, $CFG, $USER;

        $showlist = get_config('format_ocmooc', 'displaylist');
        $showmap = get_config('format_ocmooc', 'displayworldmap');

        $this->show_unenrol_button();

        if ($showlist) {
            $page = optional_param('page', 0, PARAM_INT);
            $perpage = optional_param('perpage', 10, PARAM_INT);

            $data = $this->get_data();

            if ($data->town && $data->country) {
                #$map = get_html_osmmap();
                #echo \html_writer::span($map, 'mb-2');
            }

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
            //raise memory limit for big courses
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

        if ($showmap) {
            $participantlocations = $this->fetch_course_participant_locations($this->courseid);

            $templatecontext = [
                'markers' => array_values($participantlocations),
            ];
            echo $OUTPUT->render_from_template('format_ocmooc/map/map', $templatecontext);
        }
    }

    private function prepare_table_header() {
        $data = $this->get_data();

        $header = [];
        $titles = [];
        $notsortable = [];

        if ($data->profilepicture) {
            $notsortable[] = 'profilepicture';
            $header[] = 'profilepicture';
            $titles[] = get_string('userpic',);
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

    private function show_unenrol_button() {
        global $OUTPUT;

        if ($params = $this->get_unenrol_url()) {
            list($url, $string) = $params;

            echo \html_writer::start_div('mt-2 text-right');
            echo $OUTPUT->single_button($url, $string, 'post');
            echo \html_writer::end_div();
        }
    }

    public function get_unenrol_url() {
        global $DB, $USER;

        if ($enrols = $DB->get_records('enrol', ['courseid' => $this->courseid, 'status' => 0])) {
            foreach ($enrols as $enrol) {
                $params = [
                        'enrolid' => $enrol->id,
                        'status' => 0,
                        'userid' => $USER->id
                ];
                if ($DB->record_exists('user_enrolments', $params)) {
                    if (array_key_exists($enrol->enrol, self::UNENROLS)) {
                        $unenrolparams = self::UNENROLS[$enrol->enrol];

                        if (has_capability($unenrolparams['capability'], $this->context)) {
                            $string = get_string('unenrolme', 'enrol', $this->course->fullname ?? $this->course->shortname);
                            $url = new \moodle_url($unenrolparams['url'], ['enrolid' => $enrol->id]);
                            return [$url, $string];
                        }
                    }
                }
            }
        }
        return false;
    }

    private function prepare_user_entry($user) {
        global $OUTPUT, $DB;

        $page = optional_param('page', 0, PARAM_INT);
        $perpage = optional_param('perpage', 10, PARAM_INT);

        $data = $this->get_data();

        $userdata = [];

        if ($data->profilepicture) {
            $userdata[] = $OUTPUT->user_picture($user, ['size' => 35, 'courseid' => $this->courseid]);
        }

        switch ($data->namedisplay) {
            case 1:
                $userdata[] = $user->username;
                break;
            case 2:
                $userdata[] = "User " . ($this->counter + ($page * $perpage));
                $this->counter++;
                break;
            case 3:
                $userdata[] = $user->firstname . ' ' . mb_substr($user->lastname, 0, 1) . '.';
                break;
            default:
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
                $imageurl = \moodle_url::make_pluginfile_url(\context_course::instance($this->courseid)->id, 'badges', 'badgeimage',
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

    private function get_data() {
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

    private function handle_search_data($sql) {
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

    protected function render_editor_custom() {

    }

    public function render_overview() {

    }

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

    /**
     * Fetches participant locations for a given course.
     *
     * This function retrieves the geographic locations of course participants based on their city data.
     * If certain cities are missing from the database, it processes and adds them using an external API.
     * Finally, it returns all markers (locations) with participant counts for the course.
     *
     * @param int $courseid The ID of the course.
     * @return array An array of markers, each containing:
     *               - 'latitude' (float): The latitude of the location.
     *               - 'longitude' (float): The longitude of the location.
     *               - 'location_name' (string): The name of the location (localized).
     *               - 'participants' (array): List of participants associated with the location.
     * @throws dml_exception If there are any database errors during execution.
     */
    public function fetch_course_participant_locations(int $courseid): array {
        // Identify cities that are missing from the database.
        // These cities are those not yet mapped to a geographic location.
        $missingcities = $this->get_missing_cities($courseid);

        // Process each missing city.
        // This includes fetching geographic data from an external API and adding the city to the database.
        foreach ($missingcities as $city) {
            $this->process_city($city->city);
        }

        // Retrieve all markers for the course participants.
        // This includes the geographic coordinates, location names, and participant counts.
        return $this->get_markers($courseid);
    }

    /**
     * Retrieves a list of cities missing from the database for a given course.
     *
     * This function identifies participant cities that are not yet mapped to a location
     * in the `format_ocmooc_locations` table or its aliases in the `format_ocmooc_aliases` table.
     * It ensures that only cities with a valid mapping or alias are considered as "existing."
     *
     * @param int $courseid The ID of the course.
     * @return array An array of missing cities, where each item contains:
     *               - 'city' (string): The name of the missing city.
     * @throws dml_exception If there are any database errors during execution.
     */
    private function get_missing_cities(int $courseid): array {
        global $DB;

        // SQL query to find cities that are:
        // 1. Distinct participant cities in the course.
        // 2. Not mapped to a location in `format_ocmooc_locations` with the same country.
        // 3. Not present as an alias in `format_ocmooc_aliases` for the same country.
        // 4. Non-empty strings.
        $sql = "SELECT DISTINCT u.city
                           FROM {user} u
                           JOIN {user_enrolments} ue ON ue.userid = u.id
                           JOIN {enrol} e ON e.id = ue.enrolid
                      LEFT JOIN {format_ocmooc_locations} loc
                                ON (loc.location_name_de = u.city OR loc.location_name_en = u.city)
                                AND loc.country = u.country
                      LEFT JOIN {format_ocmooc_aliases} alias
                                ON alias.alias = u.city
                                AND EXISTS (
                                    SELECT 1
                                      FROM {format_ocmooc_locations} loc_alias
                                     WHERE loc_alias.id = alias.location_id
                                       AND loc_alias.country = u.country
                                    )
                         WHERE e.courseid = :courseid
                               AND loc.id IS NULL
                               AND alias.id IS NULL
                               AND u.city <> ''";

        return $DB->get_records_sql($sql, ['courseid' => $courseid]);
    }

    /**
     * Processes a city and updates location data.
     *
     * This function retrieves geographic data for the given city from an external API.
     * If the location already exists in the database, it adds an alias if necessary.
     * Otherwise, it creates a new location record in the database and optionally adds an alias.
     *
     * @param string $city The name of the city to process.
     * @return void
     * @throws dml_exception If there are any database errors during execution.
     */
    private function process_city(string $city) {
        global $DB, $USER;

        // Retrieve the user's country from their profile.
        $usercountry = $USER->country;

        // Fetch geographic data for the city and country using the external API.
        $locationinfo = $this->get_location_info($city, $usercountry);

        // If no valid location data is returned, log a debug message and exit.
        if (!$locationinfo) {
            debugging("No valid location data found for: $city in $usercountry");
            return;
        }

        // Check if a location with the same latitude, longitude, and country already exists in the database.
        $sql = "SELECT loc.id
              FROM {format_ocmooc_locations} loc
             WHERE loc.latitude = :latitude
               AND loc.longitude = :longitude
               AND loc.country = :country";

        $existinglocation = $DB->get_record_sql($sql, [
            'latitude' => $locationinfo['latitude'],
            'longitude' => $locationinfo['longitude'],
            'country' => $usercountry,
        ]);

        // If the location already exists, check if the current city name needs to be added as an alias.
        if ($existinglocation) {
            // Add the city as an alias if it is not already one of the known names for the location.
            if (!in_array($city, [$locationinfo['location_name_de'], $locationinfo['location_name_en']])) {
                $this->add_location_alias($existinglocation->id, $city);
            }
            return; // Exit early since the location already exists.
        }

        // Create a new record for the location in the database.
        $record = new stdClass();
        $record->location_name_de = $locationinfo['location_name_de'] ?? $city;
        $record->location_name_en = $locationinfo['location_name_en'] ?? $city;
        $record->latitude = $locationinfo['latitude'];
        $record->longitude = $locationinfo['longitude'];
        $record->country = $usercountry;
        $record->last_checked = time();

        $locationid = $DB->insert_record('format_ocmooc_locations', $record);

        // If the city name is different from the API-provided names, add it as an alias.
        if (!in_array($city, [$locationinfo['location_name_de'], $locationinfo['location_name_en']])) {
            $this->add_location_alias($locationid, $city);
        }
    }

    /**
     * Retrieves location markers for course participants.
     *
     * This function generates a list of geographic markers representing the locations of participants
     * in a given course. It aggregates participant data based on their city information and resolves
     * locations using the `format_ocmooc_locations` table and its aliases. The results are grouped by
     * location and include the number of participants at each location, along with the geographic
     * coordinates (latitude and longitude).
     *
     * @param int $courseid The ID of the course for which participant locations should be fetched.
     * @return array An array of location markers, where each record contains:
     *               - 'location_name' (string): The localized name of the location.
     *               - 'participant_count' (int): The total number of participants at this location.
     *               - 'latitude' (float): The latitude of the location.
     *               - 'longitude' (float): The longitude of the location.
     * @throws dml_exception If there are any database errors during execution.
     */
    private function get_markers(int $courseid): array {
        global $DB;

        // Determine the appropriate location field based on the current language.
        // Use the German name ('location_name_de') for 'de', otherwise default to the English name ('location_name_en').
        $lang = current_language();
        $locationfield = ($lang === 'de') ? 'location_name_de' : 'location_name_en';

        // SQL query to fetch participant locations:
        // - The query counts participants ('participant_count') for each unique location.
        // - It uses the `format_ocmooc_locations` table to map city names to geographic locations.
        // - If a city name matches an alias in `format_ocmooc_aliases`, it resolves to the associated location ID.
        $sql = "SELECT COALESCE(loc.$locationfield, alias_loc.$locationfield) AS location_name,
                       COALESCE(loc.latitude, alias_loc.latitude)             AS latitude,
                       COALESCE(loc.longitude, alias_loc.longitude)           AS longitude,
                       COUNT(DISTINCT u.id)                                   AS participant_count
                FROM {user} u
                  JOIN {user_enrolments} ue
                ON ue.userid = u.id
                    JOIN {enrol} e ON e.id = ue.enrolid
                    LEFT JOIN {format_ocmooc_locations} loc
                    ON (loc.location_name_de = u.city OR loc.location_name_en = u.city)
                    AND loc.country = u.country
                    LEFT JOIN (
                    SELECT alias.alias,
                           loc.id AS location_id,
                           loc.location_name_de,
                           loc.location_name_en,
                           loc.latitude,
                           loc.longitude,
                           loc.country
                    FROM {format_ocmooc_aliases} alias
                    JOIN {format_ocmooc_locations} loc ON alias.location_id = loc.id
                    ) alias_loc
                    ON alias_loc.alias = u.city AND alias_loc.country = u.country
                WHERE e.courseid = :courseid
                  AND u.city <> ''
                  AND (loc.id IS NOT NULL
                   OR alias_loc.location_id IS NOT NULL)
                GROUP BY location_name, latitude, longitude";

        $params = ['courseid' => $courseid];

        // Execute the query and return the results as an array of records.
        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Retrieves geographic information for a given location name.
     *
     * This function queries the GeoNames API to fetch latitude, longitude, and localized names
     * for the provided location. It performs two separate API calls, one for German (`lang=de`)
     * and one for English (`lang=en`), to retrieve localized data. If data is found in either
     * language, it combines the results into a unified array. If no data is found, it returns `null`.
     *
     * @param string $locationname The name of the location to search for.
     * @return array|null An array containing:
     *                    - 'latitude' (float): The latitude of the location.
     *                    - 'longitude' (float): The longitude of the location.
     *                    - 'location_name_de' (string|null): The German name of the location (if available).
     *                    - 'location_name_en' (string|null): The English name of the location (if available).
     *                    Returns null if no data is found.
     */
    private function get_location_info(string $locationname, string $country = null): ?array {
        // Optional country parameter for the API query, encoded for URL safety.
        $countryparam = $country ? '&country=' . urlencode($country) : '';

        // Perform API requests to GeoNames in German and English.
        // The `featureClass=P` parameter limits results to populated places (cities, towns, etc.).
        $apiresponses = [
            'de' => json_decode(
                file_get_contents(
                    'http://api.geonames.org/searchJSON?q=' . urlencode($locationname) .
                    $countryparam . '&maxRows=1&username=j_l_r&lang=de&featureClass=P'
                )
            ),
            'en' => json_decode(
                file_get_contents(
                    'http://api.geonames.org/searchJSON?q=' . urlencode($locationname) .
                    $countryparam . '&maxRows=1&username=j_l_r&lang=en&featureClass=P'
                )
            ),
        ];

        // Check if at least one of the API responses contains valid geoname data.
        if (!empty($apiresponses['de']->geonames) || !empty($apiresponses['en']->geonames)) {
            return [
                // Use the latitude and longitude from the first valid result in either language.
                'latitude' => $apiresponses['de']->geonames[0]->lat ?? $apiresponses['en']->geonames[0]->lat,
                'longitude' => $apiresponses['de']->geonames[0]->lng ?? $apiresponses['en']->geonames[0]->lng,
                // Use the localized names from the respective language responses.
                'location_name_de' => $apiresponses['de']->geonames[0]->name ?? null,
                'location_name_en' => $apiresponses['en']->geonames[0]->name ?? null,
            ];
        }

        // If no valid data is found in either API response, return null.
        return null;
    }

    /**
     * Adds an alias for a given location.
     *
     * This function checks if an alias for the given location already exists in the database.
     * If no such alias exists, it creates a new record in the `format_ocmooc_aliases` table
     * linking the alias name to the specified location ID. Aliases are used to map alternative
     * names for the same geographic location (e.g., different spellings or translations of city names).
     *
     * @param int $locationid The ID of the location to which the alias should be linked.
     * @param string $alias The alias name to be added for the location.
     * @return void
     * @throws dml_exception If a database error occurs during execution.
     */
    private function add_location_alias(int $locationid, string $alias) {
        global $DB;

        // Check if the alias already exists for the specified location.
        // This query ensures no duplicate aliases are added to the database.
        $sql = "SELECT id
                  FROM {format_ocmooc_aliases}
                 WHERE location_id = :location_id AND alias = :alias";

        // Execute the query with the provided location ID and alias as parameters.
        $existingalias = $DB->get_record_sql($sql, [
            'location_id' => $locationid,
            'alias' => $alias,
        ]);

        // If the alias does not already exist, insert a new record.
        if (!$existingalias) {
            $record = new stdClass();
            $record->location_id = $locationid; // Link the alias to the location.
            $record->alias = $alias;           // Store the alias name.

            // Insert the new alias record into the `format_ocmooc_aliases` table.
            $DB->insert_record('format_ocmooc_aliases', $record);
        }
    }
}
