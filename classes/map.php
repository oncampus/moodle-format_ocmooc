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

namespace format_ocmooc;

use stdClass;

class map {

    /**
     * Fetches participant locations for a given course.
     *
     * This function retrieves the geographic locations of course participants based on their city data.
     * If certain cities are missing from the database, it processes and adds them using an external API.
     * Finally, it returns all markers (locations) with participant counts for the course.
     *
     * Example return:
     *  [
     *    [
     *      'latitude' => 52.5200,
     *      'longitude' => 13.4050,
     *      'location_name' => 'Berlin',
     *      'participant_count' => 42
     *    ],
     *    [
     *      'latitude' => 48.8566,
     *      'longitude' => 2.3522,
     *      'location_name' => 'Paris',
     *      'participant_count' => 15
     *    ]
     *  ]
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
        // Identifiziere Städte, die noch nicht in der Datenbank sind
        $missingcities = $this->get_missing_cities($courseid);

        // Verarbeite die fehlenden Städte und füge sie hinzu
        $this->process_cities($missingcities);

        // Lade die Marker stückweise
        return $this->get_markers($courseid);
    }

    /**
     * Retrieves a list of cities and their countries missing from the database for a given course.
     *
     * This function identifies participant cities that are not yet mapped to a location
     * in the `format_ocmooc_locations` table or its aliases in the `format_ocmooc_aliases` table.
     * It ensures that only cities with a valid mapping or alias are considered as "existing."
     *
     * @param int $courseid The ID of the course.
     * @return array An array of missing cities, where each item contains:
     *               - 'city' (string): The name of the missing city.
     *               - 'country' (string): The associated country of the missing city.
     * @throws dml_exception If there are any database errors during execution.
     */
    private function get_missing_cities(int $courseid): array {
        global $DB;

        // SQL query to find cities and their countries that are:
        // 1. Distinct participant cities in the course.
        // 2. Not mapped to a location in `format_ocmooc_locations` with the same country.
        // 3. Not present as an alias in `format_ocmooc_aliases` for the same country.
        // 4. Non-empty strings.
        // 5. Not already listed as invalid in `format_ocmooc_invalidlocs`.
        $sql = "SELECT DISTINCT u.city, u.country
              FROM {user} u
              JOIN {user_enrolments} ue ON ue.userid = u.id
              JOIN {enrol} e ON e.id = ue.enrolid
              LEFT JOIN {format_ocmooc_locations} loc
                     ON (loc.location_name_de = u.city OR loc.location_name_en = u.city)
                     AND (loc.country = u.country OR u.country = '')
              LEFT JOIN {format_ocmooc_aliases} alias
                     ON alias.alias = u.city
                     AND EXISTS (
                         SELECT 1
                           FROM {format_ocmooc_locations} loc_alias
                          WHERE loc_alias.id = alias.location_id
                            AND (loc_alias.country = u.country OR u.country = '')
                     )
              WHERE e.courseid = :courseid
                AND loc.id IS NULL
                AND alias.id IS NULL
                AND u.city <> ''
                AND NOT EXISTS (
                    SELECT 1
                      FROM {format_ocmooc_invalidlocs} invalid
                     WHERE invalid.city = u.city
                       AND (invalid.country = u.country OR invalid.country IS NULL)
                )";

        // Execute the query and return the results.
        return $DB->get_records_sql($sql, ['courseid' => $courseid]);
    }

    /**
     * Processes multiple cities and updates location data.
     *
     * This function retrieves geographic data for a list of cities and their associated countries.
     * It performs the following steps:
     * - Fetches geographic data (latitude, longitude, localized names) from an external API.
     * - Checks if a location with the same latitude, longitude, and country already exists in the database.
     * - If the location exists, an alias is added if the city name is different.
     * - If the location does not exist, a new location record is created in the database.
     *
     * @param array $cities An array of cities to process, where each item is:
     *                      - 'city' (string): The name of the city.
     *                      - 'country' (string): The associated country of the city.
     * @return void
     * @throws dml_exception If there are any database errors during execution.
     */
    private function process_cities(array $cities) {
        global $DB;

        $citybatch = [];
        foreach ($cities as $citydata) {
            $citybatch[] = [
                'city' => $citydata->city,
                'country' => $citydata->country,
            ];
        }

        $locationinfos = $this->batch_get_location_info($citybatch);

        foreach ($locationinfos['valid'] as $locationinfo) {
            // Skip invalid or empty location data.
            if (!$locationinfo) {
                continue;
            }

            $city = $locationinfo['city'];
            $country = $locationinfo['country'];

            $sql = "SELECT loc.id
                FROM {format_ocmooc_locations} loc
                WHERE loc.latitude = :latitude
                  AND loc.longitude = :longitude
                  AND loc.country = :country";

            $existinglocation = $DB->get_record_sql($sql, [
                'latitude' => $locationinfo['latitude'],
                'longitude' => $locationinfo['longitude'],
                'country' => $country,
            ]);

            if ($existinglocation) {
                if (!in_array($city, [$locationinfo['location_name_de'], $locationinfo['location_name_en']])) {
                    $this->add_location_alias($existinglocation->id, $city);
                }
                continue;
            }

            $record = new \stdClass();
            $record->location_name_de = $locationinfo['location_name_de'] ?? $city;
            $record->location_name_en = $locationinfo['location_name_en'] ?? $city;
            $record->latitude = $locationinfo['latitude'];
            $record->longitude = $locationinfo['longitude'];
            $record->country = $country;
            $record->last_checked = time();

            $locationid = $DB->insert_record('format_ocmooc_locations', $record);

            if (!in_array($city, [$locationinfo['location_name_de'], $locationinfo['location_name_en']])) {
                $this->add_location_alias($locationid, $city);
            }
        }

        foreach ($locationinfos['invalid'] as $locationinfo) {
            $this->add_invalid_location($locationinfo['city'], $locationinfo['country']);

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
        $lang = current_language();
        $locationfield = ($lang === 'de') ? 'location_name_de' : 'location_name_en';

        $sql = "SELECT COALESCE(loc.{$locationfield}, alias_loc.{$locationfield}) AS location_name,
                       COALESCE(loc.latitude, alias_loc.latitude)                 AS latitude,
                       COALESCE(loc.longitude, alias_loc.longitude)               AS longitude,
                       COUNT(DISTINCT u.id)                                       AS participant_count
                FROM mdl_user u
                         JOIN mdl_user_enrolments ue ON ue.userid = u.id
                         JOIN mdl_enrol e ON e.id = ue.enrolid
                         LEFT JOIN mdl_format_ocmooc_locations loc
                                   ON (loc.location_name_de = u.city OR loc.location_name_en = u.city)
                                       AND (loc.country = u.country OR u.country = '')
                         LEFT JOIN (
                    SELECT alias.alias,
                           loc.id AS location_id,
                           loc.location_name_de,
                           loc.location_name_en,
                           loc.latitude,
                           loc.longitude,
                           loc.country
                    FROM mdl_format_ocmooc_aliases alias
                             JOIN mdl_format_ocmooc_locations loc ON alias.location_id = loc.id
                ) alias_loc
                                   ON alias_loc.alias = u.city
                                       AND (alias_loc.country = u.country OR u.country = '')
                WHERE e.courseid = :courseid
                  AND u.city <> ''
                  AND (loc.id IS NOT NULL OR alias_loc.location_id IS NOT NULL)
                GROUP BY location_name, latitude, longitude";

        $params = [
            'courseid' => $courseid,
        ];

        // Execute the query and store results.
        try {
            $results = $DB->get_records_sql($sql, $params);
            debugging("SQL Results: " . print_r($results, true), DEBUG_DEVELOPER);
            return $results;
        } catch (\Exception $e) {
            debugging("Error during SQL execution: " . $e->getMessage(), DEBUG_DEVELOPER);
            return [];
        }
    }

    /**
     * Retrieves geographic information for multiple locations in a batch.
     *
     * This function performs parallel cURL requests to the GeoNames API to fetch geographic data
     * (latitude, longitude, and localized names) for multiple city-country pairs.
     * Each city is queried in both German and English to ensure localized data is collected.
     *
     * Steps:
     * 1. Prepare cURL requests for each city-country combination and for both languages.
     * 2. Execute all requests in parallel to minimize API call latency.
     * 3. Parse the responses, extracting relevant geographic details.
     * 4. Merge the German and English data into a unified result set for each city.
     *
     * @param array $locations An array of locations to query. Each entry should include:
     *                         - 'city' (string): The name of the city.
     *                         - 'country' (string|null): Optional ISO country code.
     * @return array An array of results, with each entry containing:
     *               - 'city' (string): The input city name.
     *               - 'country' (string|null): The input or API-suggested country code.
     *               - 'latitude' (float|null): The geographic latitude of the city.
     *               - 'longitude' (float|null): The geographic longitude of the city.
     *               - 'location_name_de' (string|null): The city name in German.
     *               - 'location_name_en' (string|null): The city name in English.
     */
    private function batch_get_location_info(array $locations): array {
        // Initialize cURL handles for parallel API requests.
        $curlhandles = [];
        $multihandle = curl_multi_init();
        $results = [];

        // Prepare cURL requests for each location and language.
        foreach ($locations as $key => $location) {
            // URL-encode the city name for safe API requests.
            $city = urlencode($location['city']);
            // Append the country parameter if it is provided.
            $countryparam = isset($location['country']) ? '&country=' . urlencode($location['country']) : '';

            // Define the API URLs for German and English responses.
            $apiurls = [
                'de' => "http://api.geonames.org/searchJSON?q={$city}{$countryparam}&maxRows=1&username=j_l_r&lang=de&featureClass=P",
                'en' => "http://api.geonames.org/searchJSON?q={$city}{$countryparam}&maxRows=1&username=j_l_r2&lang=en&featureClass=P",
            ];

            // Initialize cURL handles for both German and English requests.
            foreach ($apiurls as $lang => $url) {
                $curlhandles["{$key}_{$lang}"] = curl_init();
                curl_setopt($curlhandles["{$key}_{$lang}"], CURLOPT_URL, $url);
                curl_setopt($curlhandles["{$key}_{$lang}"], CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curlhandles["{$key}_{$lang}"], CURLOPT_TIMEOUT, 10);
                curl_multi_add_handle($multihandle, $curlhandles["{$key}_{$lang}"]);
            }
        }

        // Execute all cURL requests in parallel until all are completed.
        do {
            $status = curl_multi_exec($multihandle, $active);
            curl_multi_select($multihandle);
        } while ($active && $status == CURLM_OK);

        // Collect responses and process the retrieved data.
        foreach ($curlhandles as $handlekey => $handle) {
            // Get the API response for the current cURL handle.
            $response = curl_multi_getcontent($handle);

            // Close the current cURL handle and remove it from the multi-handle.
            curl_multi_remove_handle($multihandle, $handle);
            curl_close($handle);

            // Extract the key and language identifier from the handle key.
            [$key, $lang] = explode('_', $handlekey);
            $responsedecoded = json_decode($response);

            if (isset($responsedecoded->status)) {
                $status = $responsedecoded->status;
                if (isset($status->value) && $status->value == 19) {
                    // API-Limit überschritten.
                    break; // Exit the loop if rate limit is reached.
                }
            }

            //debugging("API ANSWER: $responsedecoded", DEBUG_DEVELOPER);

            // Check if the response contains valid GeoNames data.
            if ($responsedecoded->totalResultsCount < 1) {
                $results['invalid'][] = $locations[$key]; // Collect invalid locations.
                continue;
            }

            // Initialize the result for this city if it hasn't been set yet.
            $geodata = $responsedecoded->geonames[0];
            if (!isset($results['valid'][$key])) {
                $results['valid'][$key] = [
                    'city' => $locations[$key]['city'],
                    'country' => !empty($locations[$key]['country']) ? $locations[$key]['country'] : $geodata->countryCode,
                    'latitude' => $geodata->lat,
                    'longitude' => $geodata->lng,
                    'location_name_de' => $lang === 'de' ? $geodata->name : null,
                    'location_name_en' => $lang === 'en' ? $geodata->name : null,
                ];
            } else {
                // Merge language-specific data.
                if ($lang === 'de') {
                    $results['valid'][$key]['location_name_de'] = $geodata->name;
                } else if ($lang === 'en') {
                    $results['valid'][$key]['location_name_en'] = $geodata->name;
                }
            }
        }

        // Close the multi-handle after all requests are processed.
        curl_multi_close($multihandle);
        // Return the aggregated results for all queried cities.
        return $results;
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

    /**
     * Speichert ungültige Orte in der Tabelle `format_ocmooc_invalidlocs`.
     *
     * @param string $city Der Name der ungültigen Stadt.
     * @param string|null $country Der optionale Ländercode der Stadt.
     * @return void
     * @throws dml_exception
     */
    private function add_invalid_location(string $city, ?string $country) {
        global $DB;

        debugging("ACHTUNG $city, COUNTRY: ' . $country, DEBUG_DEVELOPER);");
        // Prüfen, ob der Ort bereits als ungültig gespeichert wurde
        $sql = "SELECT id FROM {format_ocmooc_invalidlocs} WHERE city = :city AND (country = :country OR country IS NULL)";
        $exists = $DB->get_record_sql($sql, ['city' => $city, 'country' => $country]);

        if (!$exists) {
            // Einfügen des ungültigen Ortes
            $record = new \stdClass();
            $record->city = $city;
            $record->country = $country;

            $DB->insert_record('format_ocmooc_invalidlocs', $record);
            debugging("Invalid location added: City = $city, Country = $country", DEBUG_DEVELOPER);
        }
    }
}
