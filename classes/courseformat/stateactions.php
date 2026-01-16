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

namespace format_ocmooc\courseformat;

use context_course;
use core_courseformat\stateupdates;
use format_ocmooc\sections;
use stdClass;

/**
 * class stateactions
 *
 * @package   format_ocmooc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2023 onwards, Moodle Pty Ltd
 */
class stateactions extends \core_courseformat\stateactions {
    /**
     * Moving a section
     *
     * @param \core_courseformat\stateupdates $updates
     * @param stdClass $course
     * @param array $ids
     * @param int|null $targetsectionid if positive number, move AFTER this section under the
     *                                                         same parent if negative number, move TO the parent with
     *                                                         id abs($targetsectionid) as the first child if 0, move
     *                                                         to parent=0 as the first child
     *                                                         (it's quite hacky but unfortunately we can only use one
     *                                                         argument here so have to be creative)
     * @param int|null $targetcmid
     * @return void
     */
    public function section_move(
        \core_courseformat\stateupdates $updates,
        stdClass $course,
        array $ids,
        ?int $targetsectionid = null,
        ?int $targetcmid = null
    ): void {
        $this->validate_sections($course, $ids, __FUNCTION__);

        $coursecontext = context_course::instance($course->id);
        require_capability('moodle/course:movesections', $coursecontext);

        /** @var \format_ocmooc $format */
        $format = course_get_format($course);
        $modinfo = $format->get_modinfo();

        // Parent section and position.
        if ($targetsectionid > 0) {
            $this->validate_sections($course, [$targetsectionid], __FUNCTION__);
            $targetsection = $modinfo->get_section_info_by_id($targetsectionid, MUST_EXIST);
            $before = $this->find_next_section($modinfo, $targetsection);
            $parent = $targetsection->section;
        } else if ($targetsectionid < 0) {
            $this->validate_sections($course, [-$targetsectionid], __FUNCTION__);
            $targetsection = $modinfo->get_section_info_by_id(-$targetsectionid, MUST_EXIST);
            $before = $this->get_first_child($modinfo, $targetsection->section);
            $parent = $modinfo->get_section_info_by_id(-$targetsectionid, MUST_EXIST);
        } else {
            $before = $this->get_first_child($modinfo, 0);
            $parent = 0;
        }

        // Move sections.
        $sections = $this->get_section_info($modinfo, $ids);
        foreach ($sections as $section) {
            $sectionmanager = $format->get_section_manager();
            if ($sectionmanager->can_move_section_to($section, $parent, $before)) {
                $sectionmanager->move_section($section, $parent, $before);
            }
        }

        // All course sections can be renamed because of the resort.
        $allsections = $modinfo->get_section_info_all();
        foreach ($allsections as $section) {
            $updates->add_section_put($section->id);
        }
        // The section order is at a course level.
        $updates->add_course_put();
    }

    /**
     * Move a lection to a new position
     *
     * @param \core_courseformat\stateupdates $updates The updates object to track changes
     * @param stdClass $course The course object
     * @param array $ids The section IDs to move
     * @param int|null $targetsectionid The target section ID
     * @param int|null $targetcmid Not used
     * @return void
     */
    public function lection_move(
        \core_courseformat\stateupdates $updates,
        stdClass $course,
        array $ids,
        ?int $targetsectionid = null,
        ?int $targetcmid = null
    ): void {
        $this->validate_sections($course, $ids, __FUNCTION__);

        $coursecontext = context_course::instance($course->id);
        require_capability('moodle/course:movesections', $coursecontext);

        $format = course_get_format($course);
        $modinfo = $format->get_modinfo();

        $lectionid = array_shift($ids);
        $lection = $modinfo->get_section_info_by_id($lectionid);

        $targetsection = $modinfo->get_section_info_by_id($targetsectionid);

        $parent = $targetsection->parent;

        if ($lection->parent != $targetsection->parent) {
            $targetsection = $this->find_next_section($modinfo, $targetsection);
        } else if ($lection->section < $targetsection->section) {
            if ($lection->parent == $targetsection->parent) {
                $targetsection = $this->find_next_section($modinfo, $targetsection);
            }
        }

        $sectionmanager = $format->get_section_manager();

        // TODO: MDL-12345 Add check for moving here!

        // If parents are different, we need to move lection to another chapter.
        $sectionmanager->move_section($lection, $parent, $targetsection);

        $updates->add_section_put($lection->id);
        if (isset($targetsection)) {
            $updates->add_section_put($targetsection->id);
        }

        // All course sections can be renamed because of the resort.
        $allsections = $modinfo->get_section_info_all();
        foreach ($allsections as $section) {
            $updates->add_section_put($section->id);
        }

        // The section order is at a course level.
        $updates->add_course_put();
    }

    /**
     * Move a lection to a specific chapter
     *
     * @param \core_courseformat\stateupdates $updates The updates object to track changes
     * @param stdClass $course The course object
     * @param array $ids The section IDs to move
     * @param int|null $targetsectionid The target chapter ID
     * @param int|null $targetcmid Not used
     * @return void
     */
    public function lection_move_to_chapter(
        \core_courseformat\stateupdates $updates,
        stdClass $course,
        array $ids,
        ?int $targetsectionid = null,
        ?int $targetcmid = null
    ): void {
        $this->validate_sections($course, $ids, __FUNCTION__);

        $coursecontext = context_course::instance($course->id);
        require_capability('moodle/course:movesections', $coursecontext);

        $format = course_get_format($course);
        $modinfo = $format->get_modinfo();

        $lectionid = array_shift($ids);
        $lection = $modinfo->get_section_info_by_id($lectionid);

        $targetchapter = $modinfo->get_section_info_by_id($targetsectionid);

        $sectionmanager = $format->get_section_manager();
        $target = $this->get_first_child($modinfo, $targetchapter->section);

        // If parents are different, we need to move lection to another chapter.
        $sectionmanager->move_section($lection, $targetchapter, $target);

        // All course sections can be renamed because of the resort.
        $allsections = $modinfo->get_section_info_all();
        foreach ($allsections as $section) {
            $updates->add_section_put($section->id);
        }

        // The section order is at a course level.
        $updates->add_course_put();
    }

    /**
     * Move a chapter to a new position
     *
     * @param \core_courseformat\stateupdates $updates The updates object to track changes
     * @param stdClass $course The course object
     * @param array $ids The section IDs to move
     * @param int|null $targetsectionid The target section ID
     * @param int|null $targetcmid Not used
     * @return void
     */
    public function chapter_move(
        \core_courseformat\stateupdates $updates,
        stdClass $course,
        array $ids,
        ?int $targetsectionid = null,
        ?int $targetcmid = null
    ): void {
        $this->validate_sections($course, $ids, __FUNCTION__);

        $coursecontext = context_course::instance($course->id);
        require_capability('moodle/course:movesections', $coursecontext);

        /** @var \format_ocmooc $format */
        $format = course_get_format($course);
        $modinfo = $format->get_modinfo();

        $chapterid = array_shift($ids);
        $chapter = $modinfo->get_section_info_by_id($chapterid);
        $targetchapter = $modinfo->get_section_info_by_id($targetsectionid);

        $sectionmanager = $format->get_section_manager();

        if ($chapter->section < $targetchapter->section) {
            $target = $this->find_next_section($modinfo, $targetchapter);
        } else {
            $target = $targetchapter;
        }

        $sectionmanager->move_section($chapter, 0, $target);

        // All course sections can be renamed because of the resort.
        $allsections = $modinfo->get_section_info_all();
        foreach ($allsections as $section) {
            $updates->add_section_put($section->id);
        }

        // The section order is at a course level.
        $updates->add_course_put();
    }

    /**
     * Find next section
     *
     * @param \course_modinfo $modinfo
     * @param \section_info $thissection
     * @return \section_info|null
     */
    protected function find_next_section(\course_modinfo $modinfo, \section_info $thissection): ?\section_info {
        $found = false;
        foreach ($modinfo->get_section_info_all() as $section) {
            if ($section->parent != $thissection->parent) {
                continue;
            }

            if ($found) {
                return $section->parent == $thissection->parent ? $section : null;
            } else if ($section->id == $thissection->id) {
                $found = true;
            }
        }
        return null;
    }

    /**
     * Get first subsection
     *
     * @param \course_modinfo $modinfo
     * @param int $parent
     * @return \section_info|null
     */
    protected function get_first_child(\course_modinfo $modinfo, int $parent): ?\section_info {
        foreach ($modinfo->get_section_info_all() as $section) {
            if ($section->parent == $parent && $section->section) {
                return $section;
            }
        }
        return null;
    }

    /**
     * Delete course sections.
     *
     * @param \format_ocmooc\courseformat\stateupdates $updates the affected course elements track
     * @param stdClass $course the course object
     * @param int[] $ids section ids
     * @param int $targetsectionid not used
     * @param int $targetcmid not used
     */
    public function section_delete(
        stateupdates $updates,
        stdClass $course,
        array $ids = [],
        ?int $targetsectionid = null,
        ?int $targetcmid = null
    ): void {

        if (empty($ids)) {
            // Nothing to delete.
            return;
        }

        $coursecontext = context_course::instance($course->id);
        require_capability('moodle/course:update', $coursecontext);
        require_capability('moodle/course:movesections', $coursecontext);

        $modinfo = get_fast_modinfo($course);
        $sectionid = array_shift($ids);

        $section = $modinfo->get_section_info_by_id($sectionid, MUST_EXIST);

        $sectionmanager = new sections($course->id);
        [$sectionstodelete, $modulestodelete] = $sectionmanager->delete_section_with_children($section);

        foreach ($modulestodelete as $cmid) {
            $updates->add_cm_remove($cmid);
        }

        foreach ($sectionstodelete as $sid) {
            $updates->add_section_remove($sid);
        }

        // Removing a section affects the full course structure.
        $this->course_state($updates, $course);
    }

    /**
     * Adding a subsection
     *
     * @param \core_courseformat\stateupdates $updates
     * @param stdClass $course
     * @param array $ids not used
     * @param int|null $targetsectionid parent section id
     * @param int|null $targetcmid not used
     * @return void
     */
    public function section_add_subsection(
        \core_courseformat\stateupdates $updates,
        stdClass $course,
        array $ids,
        ?int $targetsectionid = null,
        ?int $targetcmid = null
    ): void {
        $this->validate_sections($course, [$targetsectionid], __FUNCTION__);
        require_capability('moodle/course:update', context_course::instance($course->id));
        /** @var \format_ocmooc $format */
        $format = course_get_format($course);
        $modinfo = $format->get_modinfo();
        $targetsection = $modinfo->get_section_info_by_id($targetsectionid, MUST_EXIST);
        $format->create_new_section($targetsection);

        // Adding subsection affects the full course structure.
        $this->course_state($updates, $course);
    }

    /**
     * Get all lections that belong to a specific chapter
     *
     * @param \course_modinfo $modinfo The course module info
     * @param \section_info $chapter The chapter section
     * @return array Array of section_info objects representing lections
     */
    public function get_lections_by_chapter(\course_modinfo $modinfo, \section_info $chapter): array {
        $lections = [];

        foreach ($modinfo->get_section_info_all() as $section) {
            if ($section->parent == $chapter->section) {
                $lections[] = $section;
            }
        }

        return $lections;
    }
}
