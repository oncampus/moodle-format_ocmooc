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
 * @package    format_ocmooc
 * @copyright  2018 ILD, Technische Hochschule Lübeck (https://www.th-luebeck.de/ild)
 * @author     Eugen Ebel (eugen.ebel@th-luebeck.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

global $CFG;

require_once($CFG->libdir."/externallib.php");
require_once(__DIR__ . "/locallib.php");

class format_ocmooc_external extends external_api {

    public static function setgrade_parameters() {
        return new external_function_parameters([
            'contentid' => new external_value(PARAM_INT, 'H5P content id'),
            'score' => new external_value(PARAM_FLOAT, 'H5P score'),
            'maxscore' => new external_value(PARAM_FLOAT, 'H5P max score'),
        ]);
    }
    public static function setgrade($contentid, $score, $maxscore) {
        global $SESSION;
        // Parameter validation
        // REQUIRED.
        $params = self::validate_parameters(
            self::setgrade_parameters(),
            [
                'contentid' => $contentid,
                'score' => $score,
                'maxscore' => $maxscore,
            ]
        );

        // Context validation
        // OPTIONAL but in most web service it should present.
        $context = \context_system::instance();
        self::validate_context($context);
        $cm = get_coursemodule_from_instance('hvp', $contentid);
        $progress = \setgrade($contentid, $score, $maxscore);

        $debug = "CONTENT DEBUG: Contentid: " . $contentid . " Score: " . $score . " maxscore: " . $maxscore . " progress: " . $progress['percentage'];
        if ($cm == null) {
            // Kursmodul wurde nicht gefunden.
            return [
                'sectionId' => null,
                'percentage' => null,
            ];
        }

        // Rückgabe der Daten, wenn das Kursmodul gefunden wurde.
        return [
            'sectionId' => $cm->section,
            'percentage' => $progress['percentage'],
            'debug' => $debug,
        ];
    }

    public static function setgrade_returns() {
        return new \external_single_structure([
            'sectionId' => new external_value(PARAM_INT, 'section ID'),
            'percentage' => new external_value(PARAM_FLOAT, 'Percentage of section progress'),
            'debug' => new external_value(PARAM_TEXT, 'Debug Text'),
        ], 'Section progress');
    }

    public static function setgrade_subcontent_parameters() {
        return new external_function_parameters(
            [
                'contentid' => new external_value(PARAM_INT, 'The ID of the content', VALUE_REQUIRED),
                'subcontentid' => new external_value(PARAM_TEXT, 'The ID of the subcontent', VALUE_REQUIRED),
                'score' => new external_value(PARAM_FLOAT, 'The score of the subcontent', VALUE_REQUIRED),
                'maxscore' => new external_value(PARAM_FLOAT, 'The maximum possible score for the subcontent', VALUE_REQUIRED),
                'totalinteractions' => new external_value(PARAM_INT, 'The interaction amount of the activity', VALUE_REQUIRED),
            ]
        );
    }

    /**
     * Stores individual H5P activities such as all individual activities of an interactive video or presentations.
     */
    public static function setgrade_subcontent($contentid, $subcontentid, $score, $maxscore, $totalinteractions) {
        global $DB, $USER;

        // Get DB Entries by contentID and UserID
        $hvpcontent = $DB->get_record('format_ocmooc_hvp', ['content_id' => $contentid, 'user_id' => $USER->id]);

        // If no entry exists with the contentid create a entry in the table
        if (!$hvpcontent) {
            $newentry = new stdClass();
            $newentry->user_id = $USER->id;
            $newentry->content_id = $contentid;
            $newentry->subcontent_id = $subcontentid;
            $newentry->score = $score;
            $newentry->maxscore = $maxscore;
            $DB->insert_record('format_ocmooc_hvp', $newentry);
        } else {
            // If an entry with the contentid and user_id exists, proceed with the existing entry logic
            // Get existing entry for the given subcontentid and user_id, if any
            $existingsubcontententry = $DB->get_record('format_ocmooc_hvp', [
                'content_id' => $contentid,
                'subcontent_id' => $subcontentid,
                'user_id' => $USER->id,
            ]);
            if ($existingsubcontententry) {
                // If an entry exists, update the score if the new score is higher
                if ($existingsubcontententry->score < $score) {
                    $existingsubcontententry->score = $score;
                    $DB->update_record('format_ocmooc_hvp', $existingsubcontententry);
                }
            } else {
                // If no entry exists, create a new one
                $newentry = new stdClass();
                $newentry->user_id = $USER->id;
                $newentry->content_id = $contentid;
                $newentry->subcontent_id = $subcontentid;
                $newentry->score = $score;
                $newentry->maxscore = $maxscore;
                $DB->insert_record('format_ocmooc_hvp', $newentry);
            }
        }

        // Get DB Entries for the current user and contentID
        $updatedhvpcontent = $DB->get_records('format_ocmooc_hvp', ['content_id' => $contentid, 'user_id' => $USER->id]);

        // Get all subcontents from the content and calculate the new total score (count($score) / count($maxscore))
        $totalscore = 0.0;
        foreach ($updatedhvpcontent as $subcontent) {
            $totalscore += ($subcontent->score / $subcontent->maxscore);
        }
        $score = $totalinteractions > 0 ? ($totalscore / $totalinteractions) * 100 : 0;
        $maxscore = 100;

        // Parameter validation
        // REQUIRED
        $params = self::validate_parameters(
            self::setgrade_subcontent_parameters(),
            [
                'contentid' => $contentid,
                'subcontentid' => $subcontentid,
                'score' => $score,
                'maxscore' => $maxscore,
                'totalinteractions' => $totalinteractions,
            ]
        );

        // Context validation
        // OPTIONAL but in most web service it should present.
        $context = \context_system::instance();
        self::validate_context($context);
        $cm = get_coursemodule_from_instance('hvp', $contentid);
        $progress = \setgrade($contentid, $score, $maxscore);

        $debug = "SUBCONTENT DEBUG: setGrade Data: " . $progress['sectionId'] . " ,Contentid: " . $contentid . " Score: " . $score . " maxscore: " . $maxscore . " progress: " . $progress['percentage'];
        if ($cm == null) {
            // Kursmodul wurde nicht gefunden.
            return [
                'sectionId' => null,
                'percentage' => null,
                'debug' => $debug,
            ];
        }

        // Rückgabe der Daten, wenn das Kursmodul gefunden wurde.
        return [
            'sectionId' => $cm->section,
            'percentage' => $progress['percentage'],
            'debug' => $debug,
        ];
    }



    public static function setgrade_subcontent_returns() {
        return new \external_single_structure([
            'sectionId' => new external_value(PARAM_INT, 'section ID'),
            'percentage' => new external_value(PARAM_FLOAT, 'Percentage of section progress'),
            'debug' => new external_value(PARAM_TEXT, 'Debug Text'),
        ], 'Section progress');
    }

    public static function get_participant_locations($courseid) {
        global $DB;

        // Parameter validieren
        self::validate_parameters(
            self::get_participant_locations_parameters(), ['courseid' => $courseid]
        );

        // Kontext validieren
        $context = \context_course::instance($courseid);
        self::validate_context($context);

        if (!has_capability('moodle/course:viewparticipants', $context)) {
            throw new \moodle_exception('nopermission');
        }

        // Datenbankabfrage mit Limit und Offset
        $map = new \format_ocmooc\map();
        $locations = $map->fetch_course_participant_locations($courseid);

        return ['locations' => array_values($locations)];
    }

    public static function get_participant_locations_parameters() {
        return new \external_function_parameters([
            'courseid' => new \external_value(PARAM_INT, 'Course ID'),
        ]);
    }

    public static function get_participant_locations_returns() {
        return new \external_single_structure([
            'locations' => new \external_multiple_structure(
                new \external_single_structure([
                    'location_name' => new \external_value(PARAM_TEXT, 'Location name'),
                    'latitude' => new \external_value(PARAM_FLOAT, 'Latitude'),
                    'longitude' => new \external_value(PARAM_FLOAT, 'Longitude'),
                    'participant_count' => new \external_value(PARAM_INT, 'Participant count'),
                ])
            ),
        ]);
    }
}
