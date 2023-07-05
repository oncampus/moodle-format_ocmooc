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
 *  Format base class.
 *
 * @package     format_ocmooc
 * @copyright   2022 oncampus GmbH <support@oncampus.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/course/format/lib.php');

class format_ocmooc extends core_courseformat\base {

    /**
     * Returns true if this course format uses sections.
     *
     * @return bool
     */
    public function uses_sections() {
        return true;
    }

    public function uses_indentation(): bool {
        return false;
    }

    public function uses_course_index() {
        return true;
    }

    public function get_section_number(): int {
        return 0;
    }

    /**
     * Returns the information about the ajax support in the given source format.
     *
     * The returned object's property (boolean)capable indicates that
     * the course format supports Moodle course ajax features.
     *
     * @return stdClass
     */
    public function supports_ajax() {
        $ajaxsupport = new stdClass();
        $ajaxsupport->capable = true;
        return $ajaxsupport;
    }

    public function supports_components() {
        return true;
    }

    /**
     * Whether this format allows to delete sections.
     *
     * Do not call this function directly, instead use {@link course_can_delete_section()}
     *
     * @param int|stdClass|section_info $section
     * @return bool
     */
    public function can_delete_section($section) {
        return true;
    }

    /**
     * Indicates whether the course format supports the creation of a news forum.
     *
     * @return bool
     */
    public function supports_news() {
        return true;
    }

    public function section_format_options($foreditform = false) {
        $course = $this->get_course();
        $sectionformatoptions = array(
                'parent' => [
                        'type' => PARAM_INT,
                        'label' => '',
                        'element_type' => 'hidden',
                        'default' => 0,
                        'cache' => true,
                        'cachedefault' => 0,
                ],
                'depth' => [
                        'type' => PARAM_INT,
                        'label' => '',
                        'element_type' => 'hidden',
                        'default' => 0,
                        'cache' => true,
                        'cachedefault' => 0,
                ]
        );

        return $sectionformatoptions;
    }

    /**
     * Returns the display name of the given section that the course prefers.
     *
     * This method is required for inplace section name editor.
     *
     * @param int|stdClass $section Section object from database or just field section.section
     * @return string Display name that the course format prefers, e.g. "Topic 2"
     */
    public function get_section_name($section) {
        $section = $this->get_section($section);
        if ((string) $section->name !== '') {
            return format_string(
                    $section->name,
                    true,
                    ['context' => context_course::instance($this->courseid)]
            );
        } else {
            if ($section->parent == 0) {
                return "Chapter $section->section";
            } else {
                return $this->get_default_section_name($section);
            }
        }
    }

    public function get_chapters() {
        $chapters = array();
        foreach ($this->get_sections() as $num => $section) {
            if ($section->parent == 0 && $section->depth == 0) {
                $section->children = $this->get_chapter_sections($section);
                $chapters[$num] = $section;
            }
        }
        return $chapters;
    }

    public function get_chapter_sections($chapter) {
        $sectionnum  = $this->resolve_section_number($chapter);
        $sections = array();
        foreach ($this->get_sections() as $num => $subsection) {
            if ($subsection->parent == $sectionnum && $num != $sectionnum) {
                $sections[$num] = $subsection;
            }
        }
        return $sections;
    }
}

/**
 * Implements callback inplace_editable() allowing to edit values in-place.
 *
 * This method is required for inplace section name editor.
 *
 * @param string $itemtype
 * @param int $itemid
 * @param mixed $newvalue
 * @return inplace_editable
 */
function format_ocmooc_inplace_editable($itemtype, $itemid, $newvalue) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/course/lib.php');
    if ($itemtype === 'sectionname' || $itemtype === 'sectionnamenl') {
        $section = $DB->get_record_sql(
                'SELECT s.* FROM {course_sections} s JOIN {course} c ON s.course = c.id WHERE s.id = ? AND c.format = ?',
                [$itemid, 'ocmooc'],
                MUST_EXIST
        );
        $format = core_courseformat\base::instance($section->course);
        return $format->inplace_editable_update_section_name($section, $itemtype, $newvalue);
    }
}

function format_ocmooc_extend_navigation_course(navigation_node $parentnode, stdClass $course, context_course $context) {
    $parentnode->add('HIERHABEICHMICHVERSTECKT', new moodle_url('/index.php'));
}
