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

namespace format_ocmooc\output\courseformat\state;

/**
 * Contains the ajax update section structure.
 *
 * @package   format_ocmooc
 * @copyright 2022 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section extends \core_courseformat\output\local\state\section {

    /** @var \format_ocmooc the course format class */
    protected $format;

    /**
     * Export this data so it can be used as state object in the course editor.
     *
     * @param \renderer_base $output typically, the renderer that's calling this function
     * @return \stdClass data context for a mustache template
     */
    public function export_for_template(\renderer_base $output): \stdClass {
        $format  = $this->format;
        $course  = $format->get_course();
        $section = $this->section;
        $modinfo = $format->get_modinfo();

        $indexcollapsed   = false;
        $contentcollapsed = false;
        $preferences      = $format->get_sections_preferences();
        if (empty($preferences)) {
            $indexcollapsed = true;
        } else {
            if (isset($preferences[$section->id])) {
                $sectionpreferences = $preferences[$section->id];
                if (!empty($sectionpreferences->contentcollapsed)) {
                    $contentcollapsed = true;
                }
                if (!empty($sectionpreferences->indexcollapsed)) {
                    $indexcollapsed = true;
                }
            }
        }
        $data = (object)[
            'id'               => $section->id,
            'section'          => $section->section,
            'number'           => $section->section,
            'title'            => $format->get_section_name($section),
            'hassummary'       => !empty($section->summary),
            'rawtitle'         => $section->name,
            'cmlist'           => [],
            'visible'          => !empty($section->visible),
            'sectionurl'       => course_get_url($course, $section->section)->out(),
            'current'          => $format->is_section_current($section),
            'indexcollapsed'   => $indexcollapsed,
            'contentcollapsed' => $contentcollapsed,
            'hasrestrictions'  => $this->get_has_restrictions(),
        ];

//        foreach ($modinfo->sections[$section->section] as $modnumber) {
//            $mod = $modinfo->cms[$modnumber];
//            if ($section->uservisible && $mod->is_visible_on_course_page()) {
//                $data->cmlist[] = $mod->id;
//            }
//        }

        $data->parent   = $this->section->parent;
        $data->parentid = $this->section->parent ? $this->format->get_modinfo()->get_section_info($this->section->parent)->id : 0;
//        $data->collapsed = (bool)$this->section->collapsed;

        // For sections that are displayed as a link do not print list of cms or controls.
        $data->children = [];
        if ($this->section->section) {
            foreach ($this->format->get_modinfo()->get_section_info_all() as $s) {
                if ($s->parent == $this->section->section && $this->format->is_section_visible($s)) {
                    $data->children[] = (array)((new static($this->format, $s))->export_for_template($output)) +
                        $this->default_section_properties();
                }
            }
        }
        $data->haschildren   = !empty($data->children);
        $data->singlesection = (int)($this->section->section == $this->format->get_viewed_section());

        return $data;
    }

    /**
     * Since we display sections nested the values from the parent can propagate in templates
     *
     * @return array
     */
    protected function default_section_properties(): array {
        return [
            'isstealth' => false, 'ishidden' => false, 'notavailable' => false, 'hiddenfromstudents' => false,
            'cmlist'    => [], 'hascms' => false, 'cms' => [],
        ];
    }
}
