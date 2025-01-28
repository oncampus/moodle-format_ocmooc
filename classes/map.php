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

use dml_exception;
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
        $missingcities = $this->get_missing_cities($courseid);

        $this->process_cities($missingcities);

        return $this->get_markers($courseid);
    }

    /**
     * Retrieves a list of cities and their countries missing from the database for a given course.
     *
     * This function identifies participant cities that are not yet mapped to a location
     * in the `format_ocmooc_mappings` table. Specifically, it:
     * 1. Checks if the city is not already mapped in the `format_ocmooc_mappings` table.
     * 2. Ensures the city is not marked as invalid in the `format_ocmooc_invalidlocs` table.
     * 3. Excludes empty city values.
     * The results are grouped to avoid duplicate city-country combinations.
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
        // 2. Not mapped to a location in `format_ocmooc_mappings` with the same country.
        // 3. Non-empty strings.
        // 4. Not already listed as invalid in `format_ocmooc_invalidlocs`.
        $sql = "SELECT DISTINCT CONCAT(u.city, '-', u.country) AS uniqueid, u.city, u.country
                        FROM {user} u
                        JOIN {user_enrolments} ue ON ue.userid = u.id
                        JOIN {enrol} e ON e.id = ue.enrolid
                        LEFT JOIN {format_ocmooc_mappings} mapping
                                  ON mapping.city = u.city
                                  AND (mapping.country = u.country OR u.country = '')
                        WHERE e.courseid = :courseid
                          AND mapping.location_id IS NULL
                          AND u.city <> ''
                          AND NOT EXISTS (
                              SELECT 1
                              FROM {format_ocmooc_invalidlocs} invalid
                              WHERE invalid.city = u.city
                                AND (invalid.country = u.country OR invalid.country IS NULL)
                          )
                        GROUP BY u.city, u.country";

        // Execute the query and return the results.
        return $DB->get_records_sql($sql, ['courseid' => $courseid]);
    }

    /**
     * Processes multiple cities and updates location data.
     *
     * This function retrieves geographic data for a list of cities and their associated countries
     * and updates the `format_ocmooc_mappings` and `format_ocmooc_locations` tables accordingly.
     * It performs the following steps:
     * - Fetches geographic data (latitude, longitude, localized names) from an external API for each city-country pair.
     * - Checks if a location with the same latitude, longitude, and country already exists in the database.
     * - If the location exists:
     *   - Adds an entry in the `format_ocmooc_mappings` table to map the city to the existing location.
     * - If the location does not exist:
     *   - Creates a new record in the `format_ocmooc_locations` table.
     *   - Maps the city to the newly created location in the `format_ocmooc_mappings` table.
     * - Adds invalid locations (those with missing or invalid data) to the `format_ocmooc_invalidlocs` table.
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

            // Check if a location with the same latitude, longitude, and country already exists.
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
                // Map the city to the existing location.
                $DB->insert_record('format_ocmooc_mappings', [
                    'location_id' => $existinglocation->id,
                    'city' => $city,
                    'country' => $country,
                ]);
                continue;
            }

            // Create a new location record in `format_ocmooc_locations`.
            $record = new \stdClass();
            $record->location_name_de = $locationinfo['location_name_de'] ?? $city;
            $record->location_name_en = $locationinfo['location_name_en'] ?? $city;
            $record->latitude = $locationinfo['latitude'];
            $record->longitude = $locationinfo['longitude'];
            $record->country = $country;
            $record->last_checked = time();

            $locationid = $DB->insert_record('format_ocmooc_locations', $record);

            // Map the city to the newly created location.
            $DB->insert_record('format_ocmooc_mappings', [
                'location_id' => $locationid,
                'city' => $city,
                'country' => $country,
            ]);
        }

        if (isset($locationinfos['invalid']) && is_array($locationinfos['invalid'])) {
            foreach ($locationinfos['invalid'] as $locationinfo) {
                // Add invalid locations to the `format_ocmooc_invalidlocs` table.
                $this->add_invalid_location($locationinfo['city'], $locationinfo['country']);
            }
        }
    }

    /**
     * Retrieves location markers for course participants.
     *
     * This function generates a list of geographic markers representing the locations of participants
     * in a given course. It aggregates participant data based on their city information and resolves
     * locations using the `format_ocmooc_locations` table and its mappings. The results are grouped
     * by location and include the number of participants at each location, along with the geographic
     * coordinates (latitude and longitude).
     *
     * Workflow:
     * - Cities in the `mdl_user` table are linked to locations via the `mdl_format_ocmooc_mappings` table.
     * - Locations are retrieved from the `mdl_format_ocmooc_locations` table based on mapping IDs.
     * - Cities without valid mappings or locations are excluded from the results.
     *
     * @param int $courseid The ID of the course for which participant locations should be fetched.
     * @return array An array of location markers, where each record contains:
     *               - 'location_name' (string): The localized name of the location.
     *               - 'latitude' (float): The latitude of the location.
     *               - 'longitude' (float): The longitude of the location.
     *               - 'participant_count' (int): The total number of participants at this location.
     * @throws dml_exception If there are any database errors during execution.
     */
    private function get_markers(int $courseid): array {
        global $DB;

        // Determine the appropriate location field based on the current language.
        $lang = current_language();
        $locationfield = ($lang === 'de') ? 'location_name_de' : 'location_name_en';

        // SQL query to fetch location markers and participant counts.
        // This query:
        // 1. Joins users (`mdl_user`) with enrollments (`mdl_user_enrolments`) and course enrollments (`mdl_enrol`).
        // 2. Links participant cities to geographic locations via the `mdl_format_ocmooc_mappings` table.
        // 3. Retrieves geographic data (latitude, longitude, and localized names) from `mdl_format_ocmooc_locations`.
        // 4. Excludes cities with no valid mappings or empty city values.
        // 5. Groups results by location and aggregates participant counts.
        $sql = "SELECT loc.{$locationfield} AS location_name,
                       loc.latitude,
                       loc.longitude,
                       COUNT(DISTINCT u.id) AS participant_count
                FROM mdl_user u
                JOIN mdl_user_enrolments ue ON ue.userid = u.id
                JOIN mdl_enrol e ON e.id = ue.enrolid
                LEFT JOIN mdl_format_ocmooc_mappings mapping
                       ON mapping.city = u.city
                       AND (mapping.country = u.country OR u.country = '')
                LEFT JOIN mdl_format_ocmooc_locations loc
                       ON mapping.location_id = loc.id
                WHERE e.courseid = :courseid
                  AND u.city <> ''
                  AND mapping.location_id IS NOT NULL
                GROUP BY loc.{$locationfield}, loc.latitude, loc.longitude";

        $params = [
            'courseid' => $courseid,
        ];

        // Execute the query and return the results.
        try {
            $results = $DB->get_records_sql($sql, $params);
            return $results;
        } catch (\Exception $e) {
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
     * 5. Handle rate-limiting by switching between available API accounts.
     *
     * @param array $locations An array of locations to query. Each entry should include:
     *                         - 'city' (string): The name of the city.
     *                         - 'country' (string|null): Optional ISO country code.
     * @return array An array of results, with each entry containing:
     *               - 'valid': Array of valid locations with detailed geographic data.
     *               - 'invalid': Array of invalid or skipped locations due to missing data.
     * @throws dml_exception If any errors occur while handling database or debugging operations.
     */
    private function batch_get_location_info(array $locations): array {
        // List of available GeoNames API accounts to handle rate limits.
        $accounts = ['j_l_r', 'j_l_r2'];

        $lastusedaccount = 0; // Initialize with the first account.
        $maxretries = count($accounts); // Number of retries corresponds to available accounts.

        $curlhandles = []; // Store cURL handles for parallel requests.
        $multihandle = curl_multi_init(); // Initialize multi cURL handle.
        $results = []; // Store the final results.

        // Iterate through each location to fetch its geographic data.
        foreach ($locations as $key => $location) {
            $city = urlencode($location['city']);
            $countryparam = isset($location['country']) ? '&country=' . urlencode($location['country']) : '';

            $responses = []; // Hold API responses for 'de' and 'en'.
            $retrycount = 0; // Retry attempts for rate limit handling.
            $retry = true; // Flag to indicate if a retry is needed.

            // Retry logic for handling API rate limits.
            while ($retry && $retrycount < $maxretries) {
                $retry = false; // Reset the retry flag.

                // Select the current account to use.
                $account = $accounts[$lastusedaccount];

                // Generate API URLs for both German and English.
                $apiurls = [
                    'de' => "http://api.geonames.org/searchJSON?q={$city}{$countryparam}&maxRows=1&username={$account}&lang=de&featureClass=P",
                    'en' => "http://api.geonames.org/searchJSON?q={$city}{$countryparam}&maxRows=1&username={$account}&lang=en&featureClass=P",
                ];

                // Initialize cURL requests for both languages.
                foreach ($apiurls as $lang => $url) {
                    $curlhandles["{$key}_{$lang}"] = curl_init();
                    curl_setopt($curlhandles["{$key}_{$lang}"], CURLOPT_URL, $url);
                    curl_setopt($curlhandles["{$key}_{$lang}"], CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($curlhandles["{$key}_{$lang}"], CURLOPT_TIMEOUT, 10);
                    curl_multi_add_handle($multihandle, $curlhandles["{$key}_{$lang}"]);
                }

                // Execute cURL requests in parallel.
                do {
                    $status = curl_multi_exec($multihandle, $active);
                    curl_multi_select($multihandle);
                } while ($active && $status == CURLM_OK);

                // Process the responses from each cURL request.
                foreach (['de', 'en'] as $lang) {
                    $handlekey = "{$key}_{$lang}";
                    $handle = $curlhandles[$handlekey];
                    $response = curl_multi_getcontent($handle);
                    curl_multi_remove_handle($multihandle, $handle);
                    curl_close($handle);

                    $responsedecoded = json_decode($response);

                    // Handle null or invalid JSON responses.
                    if ($responsedecoded === null) {
                        $responses = []; // Invalidate the response set.
                        break; // Exit current language processing.
                    }

                    // Handle API rate limits (status code 19).
                    if (isset($responsedecoded->status)) {
                        $status = $responsedecoded->status;
                        if (isset($status->value) && $status->value == 19) {
                            $lastusedaccount = ($lastusedaccount + 1) % count($accounts); // Switch to the next account.
                            $retrycount++;
                            $retry = true; // Retry with the next account.
                            break; // Exit current language processing.
                        }
                    }

                    // Store valid responses for each language.
                    $responses[$lang] = $responsedecoded;
                }

                // Exit retry loop if both responses are successfully retrieved.
                if (!empty($responses['de']) && !empty($responses['en'])) {
                    break;
                }
            }

            // Handle case where all accounts are exhausted.
            if ($retrycount >= $maxretries) {
                curl_multi_close($multihandle);
                return $results; // Return collected data.
            }

            // Skip cities with inconsistent or missing responses.
            if (empty($responses['de']) || empty($responses['en'])) {
                continue;
            }

            // Validate the GeoNames response data.
            if (
                empty($responses['de']->geonames) || $responses['de']->totalResultsCount < 1 ||
                empty($responses['en']->geonames) || $responses['en']->totalResultsCount < 1
            ) {
                $results['invalid'][] = $location; // Add to invalid list.
                continue;
            }

            // Store valid data from both German and English responses.
            $geodatade = $responses['de']->geonames[0];
            $geodataen = $responses['en']->geonames[0];

            $results['valid'][$key] = [
                'city' => $location['city'],
                'country' => !empty($location['country']) ? $location['country'] : ($geodatade->countryCode ?? null),
                'latitude' => $geodatade->lat,
                'longitude' => $geodatade->lng,
                'location_name_de' => $geodatade->name,
                'location_name_en' => $geodataen->name,
            ];
        }

        // Close the multi cURL handle after processing all locations.
        curl_multi_close($multihandle);
        return $results; // Return the processed results.
    }

    /**
     * Stores invalid locations in the `format_ocmooc_invalidlocs` table.
     *
     * This function ensures that cities deemed invalid (e.g., due to missing or incorrect data)
     * are stored in a dedicated table to prevent repeated API queries for those cities.
     *
     * Workflow:
     * - Check if the invalid city-country combination already exists in the `format_ocmooc_invalidlocs` table.
     * - If it does not exist, insert a new record into the table.
     *
     * Use cases:
     * - Avoid redundant API requests for cities that have previously failed validation.
     * - Maintain a log of unresolvable or invalid city-country combinations for debugging or future review.
     *
     * @param string $city The name of the invalid city.
     * @param string|null $country The optional country code of the city.
     * If null, the invalidation applies globally to the city name.
     * @return void
     * @throws dml_exception If there is an error during database operations.
     */
    private function add_invalid_location(string $city, ?string $country): void {
        global $DB;

        // Check if the invalid location is already stored in the database.
        $sql = "SELECT id
            FROM {format_ocmooc_invalidlocs}
            WHERE city = :city
              AND (country = :country OR country IS NULL)";
        $exists = $DB->get_record_sql($sql, ['city' => $city, 'country' => $country]);

        // If the record does not exist, insert the new invalid location into the database.
        if (!$exists) {
            $record = new \stdClass();
            $record->city = $city;
            $record->country = $country;

            $DB->insert_record('format_ocmooc_invalidlocs', $record);
        }
    }
}
