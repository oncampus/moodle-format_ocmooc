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
 * Contains the default content output class.
 *
 * @package   format_ocmooc
 * @copyright 2023 oncampus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ocmooc\output\courseformat;


use core_courseformat\output\local\content as content_base;

/**
 * Format ocmooc class to render course content.
 *
 * @package   format_ocmooc
 * @copyright 2023 oncampus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class fullcontent extends content_base {

    /**
     * Export this data so it can be used as the context for a mustache template (core/inplace_editable).
     *
     * @param \renderer_base $output typically, the renderer that's calling this function
     * @return \stdClass data context for a mustache template
     */
    public function export_for_template(\renderer_base $output) {
        global $PAGE, $DB;
        $isediting = $PAGE->user_is_editing();

        $data = parent::export_for_template($output);
        $data->editoradvice = [];

        $courseformatoptions = $this->format->get_format_options();

        // TODO for now this class is only used if user is editing but check anyway as one day it will be used when not editing.
        if ($isediting) {
            $course = $this->format->get_course();

            if (get_config('format_ocmooc', 'allowsubocmoocview')
            && isset($courseformatoptions['courseusesubocmooc']) && $courseformatoptions['courseusesubocmooc']) {
                // TODO for now (Beta version) we warn editor about sub ocmooc only appearing in non-edit view.
                $messgage = get_string('editoradvicesubocmooc', 'format_ocmooc');
                if (has_capability('moodle/site:config', \context_system::instance())) {
                    $messgage .= ' (' . get_string('version', 'format_ocmooc', self::get_ocmooc_plugin_release()) . ')';
                }
                $data->editoradvice[] = [
                    'text' => $messgage,
                    'icon' => 'info-circle', 'class' => 'secondary'
                ];
            }
            // If completion tracking is on but nothing to track at activity level, display help to teacher.
            $hasnotrackableactivities = $DB->record_exists('course_modules', ['course' => $course->id, 'visible' => 1])
                && !$DB->record_exists_sql(
                "SELECT id FROM {course_modules} WHERE course = ? AND visible = 1 AND completion != 0",
                [$course->id]
            );
            if ($hasnotrackableactivities) {
                $bulklink = \html_writer::link(
                  new \moodle_url('/course/bulkcompletion.php', array('id' => $course->id)),
                  get_string('completionwarning_changeinbulk', 'format_ocmooc')
                );
                $helplink = \html_writer::link(
                    get_docs_url('Activity_completion_settings#Changing_activity_completion_settings_in_bulk'),
                    $output->pix_icon('help', '', 'core')
                );
                $data->editoradvice[] = [
                    'text' => get_string('completionwarning', 'format_ocmooc') . ' '  . $bulklink . ' ' . $helplink,
                    'icon' => 'exclamation-triangle', 'class' => 'warning'
                ];
            }
        }

        return $data;
    }

    /**
     * Get the release details of this version of ocmooc.
     * @return string
     */
    private static function get_ocmooc_plugin_release(): string {
        global $CFG;
        $plugin = new \stdClass();
        $plugin->release = '';
        require("$CFG->dirroot/course/format/ocmooc/version.php");
        return $plugin->release;
    }
}
