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

namespace format_ocmooc\output\courseformat\content;

use core_courseformat\output\local\content\addsection as addsection_base;
use stdClass;

/**
 * Content output class for the OCMOOC course format add section functionality.
 *
 * @package    format_ocmooc
 * @copyright  2025 oncampus GmbH <support@oncampus.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class addsection extends addsection_base {
    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output Renderer base.
     * @return stdClass
     */
    public function export_for_template(\renderer_base $output): stdClass {
        global $COURSE, $PAGE;

        $data = new stdClass();

        $chapter = optional_param('chapter', 1, PARAM_INT);

        $format      = $this->format;
        $lastsection = $format->get_last_section_number();
        $maxsections = $format->get_max_sections();

        $params = [
            'courseid' => $COURSE->id,
            'action'   => 'addchapter',
            'sesskey'  => sesskey(),
            'position' => $maxsections - $lastsection,

        ];

        $data->addchapter = [
            'url'        => new \moodle_url('/course/format/ocmooc/sectionhandler.php', $params),
            'title'      => get_string('addchapter', 'format_ocmooc'),
            'newsection' => $maxsections - $lastsection,
        ];

        $params['action']               = 'addlection';
        $params['chaptersectionnumber'] = $this->get_chapter_section_number($chapter);

        $data->addlection = [
            'url'        => new \moodle_url('/course/format/ocmooc/sectionhandler.php', $params),
            'title'      => get_string('addlection', 'format_ocmooc'),
            'newsection' => $maxsections - $lastsection,
        ];

        $context = \context_course::instance($COURSE->id);
        // Show Create Section on the edit page or on the main page if no section is available.
        $data->showaddsection = ($PAGE->user_is_editing() && has_capability('moodle/course:update', $context)) ||
                               ($lastsection == 0 && has_capability('moodle/course:update', $context));

        return $data;
    }

    /**
     * Get the section number for a specific chapter.
     *
     * @param int $chapter The chapter number to find the section for.
     * @return int|false The section number if found, false otherwise.
     */
    private function get_chapter_section_number($chapter) {
        $modinfo = $this->format->get_modinfo();

        $chaptercount = 1;
        foreach ($modinfo->get_section_info_all() as $sectionnum => $section) {
            if ($sectionnum == 0) {
                continue;
            }

            if ($section->parent === 0) {
                if ($chapter == $chaptercount) {
                    return $section->section;
                }
                $chaptercount++;
            }
        }
        return false;
    }
}
