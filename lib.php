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
 * Format base class.
 *
 * This file defines the base class for the OCMOOC course format.
 * It includes the necessary code to render the base class and handle
 * the course context.
 *
 * @package     format_ocmooc
 * @copyright   2025 oncampus GmbH <support@oncampus.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use core_courseformat\base;
use core\context;
use core\context\course as context_course;

require_once($CFG->dirroot . '/course/format/lib.php');

/**
 * Format base class for the OCMOOC course format.
 *
 * This class extends the core_courseformat\base class and provides
 * additional functionality for the OCMOOC course format.
 *
 */
class format_ocmooc extends base {
    /**
     * @var \format_ocmooc\sections The section manager instance
     */
    protected $sectionmanager;

    /**
     * Get the section manager instance
     *
     * @return \format_ocmooc\sections The section manager instance
     */
    public function get_section_manager() {
        if (!isset($this->sectionmanager)) {
            $this->sectionmanager = new \format_ocmooc\sections($this->courseid, $this);
        }
        return $this->sectionmanager;
    }

    /**
     * Returns true if this course format uses sections.
     *
     * @return bool
     */
    public function uses_sections() {
        return true;
    }

    /**
     * Handles section actions.
     *
     * @param int|section_info $section The section to act on
     * @param string $action The action to perform
     * @param int $sr The section number to set
     * @return array
     */
    public function section_action($section, $action, $sr) {
        global $PAGE;

        // Special case of refreshing here.
        // We can not use the parent refreshing.
        if ($action == 'refresh') {
            $course = $this->get_course();
            $coursecontext = context_course::instance($course->id);
            $modinfo = $this->get_modinfo();
            $renderer = $this->get_renderer($PAGE);

            if ($sr) {
                $this->set_sectionnum($sr);
            }

            if (!($section instanceof section_info)) {
                if (is_object($section)) {
                    $section = $modinfo->get_section_info($section->section);
                } else {
                    $section = $modinfo->get_section_info($section);
                }
            }

            if ($section->parent == 0) {
                $section->ischapter = true;
            }

            return [
                'content' => $renderer->course_section_updated($this, $section),
            ];
        } else {
            return parent::section_action($section, $action, $sr);
        }
    }

    /**
     * Returns true if the course format uses indentation.
     *
     * @return bool
     */
    public function uses_indentation(): bool {
        return false;
    }

    /**
     * Returns true if the course format uses a course index.
     *
     * @return bool
     */
    public function uses_course_index() {
        return true;
    }

    /**
     * Returns the section number.
     *
     * @return int
     */
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
        $ajaxsupport          = new stdClass();
        $ajaxsupport->capable = true;
        return $ajaxsupport;
    }

    /**
     * Returns true if the course format supports components.
     *
     * @return bool
     */
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

    /**
     * Returns the section format options.
     *
     * @param bool $foreditform
     * @return array
     */
    public function section_format_options($foreditform = false) {
        $course               = $this->get_course();
        $sectionformatoptions = [
        'parent' => [
            'type'         => PARAM_INT,
            'label'        => '',
            'element_type' => 'hidden',
            'default'      => 0,
            'cache'        => true,
            'cachedefault' => 0,
        ],
        'depth'  => [
            'type'         => PARAM_INT,
            'label'        => '',
            'element_type' => 'hidden',
            'default'      => 0,
            'cache'        => true,
            'cachedefault' => 0,
        ],
        ];

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
        if ((string)$section->name !== '') {
            return format_string(
                $section->name,
                true,
                ['context' => context_course::instance($this->courseid)]
            );
        } else {
            if ($section->section == 0) {
                return get_string('rootsection', 'format_ocmooc');
            } else if ($section->parent == 0) {
                return get_string(
                    'chapter',
                    'format_ocmooc'
                ) . " " . $this->get_section_manager()->get_chapter_no_from_number($section->section);
            } else {
                return get_string(
                    'lection',
                    'format_ocmooc'
                ) . " " . $this->get_section_manager()->get_lection_no_from_number($section->parent, $section->section);
            }
        }
    }

    /**
     * Returns the chapters of the course.
     *
     * @return array
     */
    public function get_chapters() {
        $chapters = [];
        foreach ($this->get_sections() as $num => $section) {
            if ($section->parent == 0 && $section->depth == 0) {
                $section->children = $this->get_chapter_sections($section);
                $chapters[$num]    = $section;
            }
        }
        return $chapters;
    }

    /**
     * Returns the chapter sections of the course.
     *
     * @param section_info $chapter
     * @return array
     */
    public function get_chapter_sections($chapter) {
        $sectionnum = $this->resolve_section_number($chapter);
        $sections   = [];
        foreach ($this->get_sections() as $num => $subsection) {
            if ($subsection->parent == $sectionnum && $num != $sectionnum) {
                $sections[$num] = $subsection;
            }
        }
        return $sections;
    }

    /**
     * Returns the view URL for the given section.
     *
     * @param int|section_info $section The section to get the view URL for
     * @param array $options Additional options
     * @return moodle_url
     */
    public function get_view_url($section, $options = []) {
        global $CFG, $USER;
        $course = $this->get_course();
        $url    = new moodle_url('/course/view.php', ['id' => $course->id]);

        if (array_key_exists('sr', $options)) {
            if ($section) {
                if (is_object($section)) {
                    $sectionno = $section->section;
                } else {
                    $sectionno = $section;
                }
                $url->set_anchor('section-' . $sectionno);
            } else {
                $sectionno = $options['sr'];
            }
            $modinfo = get_fast_modinfo($course);
            $section = $modinfo->get_section_info($sectionno, IGNORE_MISSING);
            if (!$section) {
                return new moodle_url('/course/view.php', ['id' => $course->id]);
            }
        } else if (is_object($section)) {
            $sectionno = $section->section;
        } else {
            $sectionno = $section;
            $modinfo   = get_fast_modinfo($course);
            $section   = $modinfo->get_section_info($sectionno, IGNORE_MISSING);
            if (!$section) {
                $url = new moodle_url('/course/view.php', ['id' => $course->id]);
                if (!empty($options['navigation'])) {
                    return null;
                }
                return $url;
            }
        }

        $chapterno = 1;
        $lectionno = 1;

        if ($section->parent == 0) {
            $chapterno = $this->get_section_manager()->get_chapter_no_from_number($section->section);
            $lectionno = 1;
        } else {
            $chapterno = $this->get_section_manager()->get_chapter_no_from_number($section->parent);
            $lectionno = $this->get_section_manager()->get_lection_no_from_number($section->parent, $section->section);
        }
        if (empty($CFG->linkcoursesections) && !empty($options['navigation']) && $sectionno !== null) {
            // By default assume that sections are never displayed on separate pages.
            return null;
        }
        if ($this->uses_sections() && !empty($sectionno)) {
            $url->remove_params('section');
            $url->param('chapter', $chapterno);
            $url->param('lection', $lectionno);
        }

        if ($USER->editing) {
            $url->set_anchor('section-' . $sectionno);
        }

        return $url;
    }

    /**
     * Loads all the course sections into the navigation
     *
     * @param global_navigation $navigation
     * @param navigation_node   $node The course node within the navigation
     * @return void
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function extend_course_navigation($navigation, navigation_node $node) {
        global $PAGE;
        // If section is specified in course/view.php, make sure it is expanded in navigation.
        if ($navigation->includesectionnum === false) {
            $selectedsection = optional_param('section', null, PARAM_INT);
            if (
                $selectedsection !== null && (!defined('AJAX_SCRIPT') || AJAX_SCRIPT == '0') &&
                $PAGE->url->compare(new moodle_url('/course/view.php'), URL_MATCH_BASE)
            ) {
                $navigation->includesectionnum = $selectedsection;
            }
        }
        // Check if there are callbacks to extend course navigation.

        // We want to remove the general section if it is empty.
        $course   = $this->get_course();
        $modinfo  = get_fast_modinfo($course);
        $sections = $modinfo->get_sections();
        if (!isset($sections[0])) {
            // The general section is empty to find the navigation node for it we need to get its ID.
            $section        = $modinfo->get_section_info(0);
            $generalsection = $node->get($section->id, navigation_node::TYPE_SECTION);
            if ($generalsection) {
                // We found the node - now remove it.
                $generalsection->remove();
            }
        }


        if (!empty($modinfo->sections[0])) {
            foreach ($modinfo->sections[0] as $cmid) {
                $this->navigation_add_activity($node, $modinfo->get_cm($cmid));
            }
        }
        foreach ($modinfo->get_section_info_all() as $section) {
            if ($section->parent == 0 && $section->section != 0) {
                $this->navigation_add_section($navigation, $node, $section);
            }
        }
    }

    /**
     * Adds a section to navigation node, loads modules and subsections if necessary
     *
     * @param global_navigation $navigation
     * @param navigation_node   $node
     * @param section_info      $section
     * @return null|navigation_node
     */
    protected function navigation_add_section($navigation, navigation_node $node, section_info $section): ?navigation_node {
        if (!$section->uservisible || !$this->is_section_real_available($section)) {
            return null;
        }
        $sectionname = get_section_name($this->get_course(), $section);
        $url         = course_get_url($this->get_course(), $section->section, ['navigation' => true]);

        $sectionnode           = $node->add($sectionname, $url, navigation_node::TYPE_SECTION, null, $section->id);
        $sectionnode->nodetype = navigation_node::NODETYPE_BRANCH;
        $sectionnode->hidden   = !$section->visible || !$section->available;
        if ($section->section == $this->get_viewed_section()) {
            $sectionnode->force_open();
        }
        if (
            $this->get_section_manager()->section_has_parent($navigation->includesectionnum, $section->section)
            || $navigation->includesectionnum == $section->section
        ) {
            $modinfo = get_fast_modinfo($this->courseid);
            if (!empty($modinfo->sections[$section->section])) {
                foreach ($modinfo->sections[$section->section] as $cmid) {
                    $this->navigation_add_activity($sectionnode, $modinfo->get_cm($cmid));
                }
            }
            foreach ($modinfo->get_section_info_all() as $subsection) {
                if ($subsection->parent == $section->section && $subsection->section != 0) {
                    $this->navigation_add_section($navigation, $sectionnode, $subsection);
                }
            }
        }
        return $sectionnode;
    }

    /**
     * If we are on course/view.php page return the 'section' attribute from query
     *
     * @return int
     */
    public function get_viewed_section() {
        if ($this->on_course_view_page()) {
            if ($s = $this->get_caller_page_url()->get_param('section')) {
                return $s;
            }
            $sid = $this->get_caller_page_url()->get_param('sectionid');
            if ($sid && ($section = $this->get_modinfo()->get_section_info_by_id($sid))) {
                return $section->section;
            }
        }
        return 0;
    }

    /**
     * Returns true if we are on /course/view.php page
     *
     * @return bool
     */
    public function on_course_view_page() {
        $url = $this->get_caller_page_url();
        return ($url && $url->compare(new moodle_url('/course/view.php'), URL_MATCH_BASE));
    }

    /**
     * URL of the page from where this function was called (use referer if this is an AJAX request)
     *
     * @return moodle_url
     */
    protected function get_caller_page_url(): moodle_url {
        global $PAGE, $FULLME;
        $url = $PAGE->has_set_url() ? $PAGE->url : new moodle_url($FULLME);
        if ($url->compare(new moodle_url('/lib/ajax/service.php'), URL_MATCH_BASE)) {
            return !empty($_SERVER['HTTP_REFERER']) ? new moodle_url($_SERVER['HTTP_REFERER']) : $url;
        }
        return $url;
    }

    /**
     * Adds a course module to the navigation node
     *
     * @param navigation_node $node
     * @param cm_info         $cm
     * @return null|navigation_node
     */
    protected function navigation_add_activity(navigation_node $node, cm_info $cm): ?navigation_node {
        if (!$cm->uservisible || !$cm->has_view()) {
            return null;
        }
        $activityname = $cm->get_formatted_name();
        $action       = $cm->url;
        if ($cm->icon) {
            $icon = new pix_icon($cm->icon, $cm->modfullname, $cm->iconcomponent);
        } else {
            $icon = new pix_icon('icon', $cm->modfullname, $cm->modname);
        }
        $activitynode = $node->add($activityname, $action, navigation_node::TYPE_ACTIVITY, null, $cm->id, $icon);
        if (global_navigation::module_extends_navigation($cm->modname)) {
            $activitynode->nodetype = navigation_node::NODETYPE_BRANCH;
        } else {
            $activitynode->nodetype = navigation_node::NODETYPE_LEAF;
        }
        if (method_exists($cm, 'is_visible_on_course_page')) {
            $activitynode->display = $cm->is_visible_on_course_page();
        }
        return $activitynode;
    }

    /**
     * Checks if section is really available for the current user (analyses parent section available)
     *
     * @param int|section_info $section
     * @return bool
     */
    public function is_section_real_available($section) {
        if (($this->get_section_manager()->resolve_section_number($section) == 0)) {
            // Section 0 is always available.
            return true;
        }
        $context = \context::instance_by_id(context_course::instance($this->courseid)->id);
        if (has_capability('moodle/course:viewhiddensections', $context)) {
            // For the purpose of this function only return true for teachers.
            return true;
        }
        $section = $this->get_section($section);
        return $section->available && $this->is_section_real_available($section->parent);
    }

    /**
     * Definitions of the additional options that site uses
     *
     * @param bool $foreditform
     * @return array of options
     */
    public function course_format_options($foreditform = false) {
        static $courseformatoptions = false;
        if ($courseformatoptions === false) {
            $courseformatoptions = [
            'certpercentage' => [
                'default' => 0,
                'type' => PARAM_INT,
            ],
            ];
        }
        if ($foreditform && !isset($courseformatoptions['certpercentage']['label'])) {
            $courseformatoptionsedit = [
            'certpercentage' => [
                'label' => new lang_string('certpercentage', 'format_ocmooc'),
                'help' => 'certpercentage',
                'help_component' => 'format_ocmooc',
                'element_type' => 'text',
            ],
            ];
            $courseformatoptions = array_merge_recursive($courseformatoptions, $courseformatoptionsedit);
        }
        return $courseformatoptions;
    }
}

/**
 * Implements callback inplace_editable() allowing to edit values in-place.
 *
 * This method is required for inplace section name editor.
 *
 * @param string $itemtype
 * @param int    $itemid
 * @param mixed  $newvalue
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
        $format  = core_courseformat\base::instance($section->course);
        return $format->inplace_editable_update_section_name($section, $itemtype, $newvalue);
    }
}

/**
 * Extends the course navigation node.
 *
 * @param navigation_node $parentnode The parent navigation node
 * @param stdClass $course The course object
 * @param context_course $context The course context
 */
function format_ocmooc_extend_navigation_course(navigation_node $parentnode, stdClass $course, context_course $context) {
    if ($course->format != 'ocmooc') {
        return;
    }

    $contextinstance = \context::instance_by_id($context->id);
    if (!has_capability('format/ocmooc:edit', $contextinstance)) {
        return;
    }

    if (is_guest($contextinstance)) {
        return;
    }

    $params = [
        'courseid' => $course->id,
    ];
    $parentnode->add(
        get_string('edit:participants', 'format_ocmooc'),
        new moodle_url('/course/format/ocmooc/edit/participants.php', $params)
    );

    $parentnode->add(
        get_string('edit:social', 'format_ocmooc'),
        new moodle_url('/course/format/ocmooc/edit/social.php', $params)
    );

    $parentnode->add(
        get_string('edit:htmlpage', 'format_ocmooc'),
        new moodle_url('/course/format/ocmooc/edit/htmlpage.php', $params)
    );
}
