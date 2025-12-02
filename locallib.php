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
 * Library functions for the OCMOOC course format
 *
 * @package    format_ocmooc
 * @copyright  2025 oncampus GmbH <support@oncampus.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Sets the grade for an H5P activity
 *
 * @param int $contextid The context ID of the H5P activity
 * @param float $score The score achieved
 * @param float $maxscore The maximum possible score
 * @return array Progress information with section ID and percentage
 */
function setgrade($contextid, $score, $maxscore) {
    global $DB, $USER, $CFG;
    require($CFG->dirroot . '/mod/hvp/lib.php');

    $cm = get_coursemodule_from_instance('hvp', $contextid);
    if (!$cm) {
        return ['sectionId' => "", 'percentage' => 0];
    }

    // Check permission.
    $context = \context_module::instance($cm->id);
    if (!has_capability('mod/hvp:saveresults', $context)) {
        return ['sectionId' => "", 'percentage' => 0];
    }

    // Get hvp data from content.
    $hvp = $DB->get_record('hvp', ['id' => $cm->instance]);
    if (!$hvp) {
        return ['sectionId' => "", 'percentage' => 0];
    }

    // Create grade object and set grades.
    $grade = (object)[
            'userid' => $USER->id,
    ];

    /* oncampus mod - start */
    require_once($CFG->libdir . '/gradelib.php');
    $gradinginfo = \grade_get_grades($cm->course, 'mod', 'hvp', $cm->instance, $USER->id);
    if (!empty($gradinginfo->items)) {
        $usergrade = $gradinginfo->items[0]->grades[$USER->id]->grade;
    } else {
        $usergrade = 0;
    }

    if ($score > $usergrade) {
        // Set grade using Gradebook API.
        $hvp->cmidnumber = $cm->idnumber;
        $hvp->name = $cm->name;
        $hvp->rawgrade = $score;
        $hvp->rawgrademax = $maxscore;
        hvp_grade_item_update($hvp, $grade);

        // Get content info for log.
        $content = $DB->get_record_sql(
            "SELECT c.name AS title, l.machine_name AS name, l.major_version, l.minor_version
					   FROM {hvp} c
					   JOIN {hvp_libraries} l ON l.id = c.main_library_id
					  WHERE c.id = ?",
            [$hvp->id]
        );

        // Log results set event.
        new \mod_hvp\event(
            'results',
            'set',
            $hvp->id,
            $content->title,
            $content->name,
            $content->major_version . '.' . $content->minor_version
        );

        $progress = get_progress($cm->course, $cm->section);
        return $progress;
    } else if ($score != 0 && $score == $usergrade) {
        // If score is higher 0 and equal to user_grade the hvp grade update already made.
        // We only need to get the correct Progressbar.
        $progress = get_progress($cm->course, $cm->section);
        return $progress;
    }
    return ['sectionId' => "cm->instance: " . $cm->instance . " score: " . $score . " usergrade: " . $usergrade, 'percentage' => 0];
}

/**
 * Calculates the progress for a specific section in a course
 *
 * @param int $courseid The ID of the course
 * @param int $sectionid The ID of the section
 * @return array|bool Progress information with section ID and percentage, or false if no modules found
 */
function get_progress($courseid, $sectionid) {
    global $DB, $CFG, $USER, $SESSION;
    require_once($CFG->libdir . '/gradelib.php');

    if (!$module = $DB->get_record('modules', ['name' => 'hvp'])) {
        return false;
    }

    $cms = $DB->get_records('course_modules', ['section' => $sectionid, 'course' => $courseid]);

    if (count($cms) == 0) {
        return false;
    }

    $activitygrade = 0.0;
    $activitymaxgrade = 0.0;

    if (isset($SESSION->lang)) {
        $userlang = $SESSION->lang;
    } else {
        $userlang = $USER->lang;
    }

    foreach ($cms as $mod) {
        if ($mod->visible == 1) {
            $skip = false;

            if (isset($mod->availability)) {
                $availability = json_decode($mod->availability);
                foreach ($availability->c as $criteria) {
                    if ($criteria->type == 'language' && ($criteria->id != $userlang)) {
                        $skip = true;
                    }
                }
            }

            if ($mod->completion == 0) {
                $skip = true;
            }

            if (!$skip) {
                $gradinginfo = \grade_get_grades($mod->course, 'mod', 'hvp', $mod->instance, $USER->id);
                // It could be that an object is still displayed and marked with
                // [Deletion in Progress] if the corresponding cron job has not run yet.
                if (
                    !str_contains($gradinginfo->items[0]->name, '[Deletion in progress]')
                    && !str_contains($gradinginfo->items[0]->name, '[Löschung in Bearbeitung]')
                ) {
                    $activitygrade += $gradinginfo->items[0]->grades[$USER->id]->grade;
                    $activitymaxgrade += $gradinginfo->items[0]->grademax;
                }
            }
        }
    }

    $progress = ['sectionId' => $sectionid, 'percentage' => round(($activitygrade / $activitymaxgrade) * 100)];

    return $progress;
}
