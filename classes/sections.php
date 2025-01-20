<?php

namespace format_ocmooc;

use context_course;
use course_modinfo;
use section_info;

/**
 * Manager class for course sections
 */
class sections {

    private int $courseid;

    private \stdClass $course;

    private \core_courseformat\base $format;

    public function __construct($courseid, $format = null) {
        $this->courseid = $courseid;
        $this->course   = get_course($courseid);
        $this->format   = $format ?? course_get_format($courseid);
    }

    /**
     * Create a new section under given parent
     *
     * @param int|section_info      $parent parent section
     * @param null|int|section_info $before
     * @return int $sectionnum
     */
    public function create_new_section($parent = 0, $before = null): \stdClass {
        $section          = course_create_section($this->courseid, 0);
        $sectionnum       = $this->move_section($section, $parent, $before);
        $section->section = $sectionnum;
        return $section;
    }

    /**
     * adds a new chapter to the end of the course and returns its chapter index.
     * @return int the index of the chapter
     */
    public function add_chapter() {
        $section = $this->create_new_section();
        return $this->get_chapter_no_from_number($section->section);
    }

    /**
     * adds a new lection to the end of the chapter and returns its chapter and lection index.
     * @param $chaptersectionumber int the section number of the chapter to add the lection to
     * @return array the index of chapter and the index of the lection
     */
    public function add_lection($chaptersectionumber): array {
        $section         = $this->create_new_section($chaptersectionumber);
        $section->parent = $chaptersectionumber;
        $this->format->update_section_format_options($section);

        $chapterno = $this->get_chapter_no_from_number($chaptersectionumber);
        $lectiono  = $this->get_lection_no_from_number($chaptersectionumber, $section->section);

        return [$chapterno, $lectiono];
    }

    /**
     * Handles a delete request for a section. A safety question will be displayed which the user has to confirm to
     * start the deletion process.
     * @param $id int the id of the section to delete
     * @param $cancelurl
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function deleteconfirmation(int $sectionnum, $cancelurl) {
        global $PAGE, $OUTPUT;

        // Get section_info object with all availability options.
        $sectioninfo = get_fast_modinfo($this->course)->get_section_info($sectionnum);
        if (get_string_manager()->string_exists('deletesection', 'format_' . $this->course->format)) {
            $strdelete = get_string('deletesection', 'format_' . $this->course->format);
        } else {
            $strdelete = get_string('deletesection');
        }
        $PAGE->navbar->add($strdelete);
        $PAGE->set_title($strdelete);
        $PAGE->set_heading($this->course->fullname);
        echo $OUTPUT->header();
        echo $OUTPUT->box_start('noticebox');
        $deleteurl = new \moodle_url('/course/format/ocmooc/sectionhandler.php');
        $deleteurl->param('action', "delete");
        $deleteurl->param('sectionnum', $sectionnum);
        $deleteurl->param('courseid', $this->course->id);
        $formcontinue = new \single_button($deleteurl, get_string('delete'));
        $formcancel   = new \single_button($cancelurl, get_string('cancel'), 'get');
        echo $OUTPUT->confirm(get_string('confirmdeletesection', '',
                                         get_section_name($this->course, $sectioninfo)), $formcontinue, $formcancel);
        echo $OUTPUT->box_end();
        echo $OUTPUT->footer();
        exit;
    }
//
//    /**
//     * Deletes the section with the given id.
//     * @param $sectionnum int the id (section->section) of the section to delete
//     * @return void
//     * @throws \dml_exception
//     * @throws \moodle_exception
//     */
//    public function delete_section($sectionnum) {
//        global $DB;
//
//        $sql     = 'SELECT * FROM {course_format_options} WHERE ';
//        $sql     .= $DB->sql_like('format', "'ocmooc'");
//        $sql     .= ' AND ';
//        $sql     .= $DB->sql_like('name', "'parent'");
//        $sql     .= ' AND ';
//        $sql     .= ' courseid  = :courseid';
//        $sql     .= ' AND ';
//        $sql     .= ' value  = :value';
//        $records = $DB->get_records_sql($sql,
//                                        ['courseid' => $this->courseid, 'value' => $sectionnum]);
//        if ($records) {
//            foreach ($records as $record) {
//                $child = $this->format->get_section($record->sectionid);
//                if ($this->format->can_delete_section($child)) {
//                    course_delete_section($this->course, $child);
////                    $this->format->delete_section($child);
//                } else {
//                    $child->parent = 0;
//                    $this->format->update_section_format_options($child);
//                }
//            }
//        }
//
//        if ($this->format->can_delete_section($id)) {
////            $this->format->delete_section($id);
//            course_delete_section($this->course, $id);
//        }
//        $this->reorder_sections();
//    }

    /**
     * Completely removes a section, all subsections and activities they contain
     *
     * @param section_info $section
     * @return array Array containing arrays of section ids and course mod ids that were deleted
     */
    public function delete_section_with_children(section_info $section): array {
        global $DB;
        if (!$section->section) {
            // Section 0 does not have parent.
            return [[], []];
        }

        $sectionid = $section->id;
        $course    = $this->course;

        // Move the section to be removed to the end (this will re-number other sections).
        $this->move_section($section->section, 0);

        $modinfo          = get_fast_modinfo($this->courseid);
        $allsections      = $modinfo->get_section_info_all();
        $process          = false;
        $sectionstodelete = [];
        $modulestodelete  = [];
        foreach ($allsections as $sectioninfo) {
            if ($sectioninfo->id == $sectionid) {
                // This is the section to be deleted. Since we have already
                // moved it to the end we know that we need to delete this section
                // and all the following (which can only be its subsections).
                $process = true;
            }
            if ($process) {
                $sectionstodelete[] = $sectioninfo->id;
                if (!empty($modinfo->sections[$sectioninfo->section])) {
                    $modulestodelete = array_merge($modulestodelete,
                                                   $modinfo->sections[$sectioninfo->section]);
                }
                // Remove the marker if it points to this section.
                if ($sectioninfo->section == $course->marker) {
                    course_set_marker($course->id, 0);
                }
            }
        }

        foreach ($modulestodelete as $cmid) {
            course_delete_module($cmid, true);
        }

        foreach ($sectionstodelete as $sid) {
            // Invalidate the section cache by given section id.
            course_modinfo::purge_course_section_cache_by_id($course->id, $sid);

            // Delete section summary files.
            $context = \context_course::instance($course->id);
            $fs      = get_file_storage();
            $fs->delete_area_files($context->id, 'course', 'section', $sid);
        }

        [$sectionsql, $params] = $DB->get_in_or_equal($sectionstodelete);
        $transaction = $DB->start_delegated_transaction();
        $DB->execute('DELETE FROM {course_format_options} WHERE sectionid ' . $sectionsql, $params);
        $DB->execute('DELETE FROM {course_sections} WHERE id ' . $sectionsql, $params);
        $transaction->allow_commit();

        // Partial rebuild section cache that has been purged.
        rebuild_course_cache($this->courseid, true, true);

        return [$sectionstodelete, $modulestodelete];
    }

    /**
     * Returns the section relative number regardless whether argument is an object or an int
     *
     * @param int|section_info $section
     * @return int
     */
    public function resolve_section_number($section) {
        if ($section === null || $section === '') {
            return null;
        } else if (is_object($section)) {
            return $section->section;
        } else {
            return (int)$section;
        }
    }

    /**
     * Function recursively reorders the sections while moving one section to the new position
     *
     * If $movedsectionnum is not specified, function just populates the array for each (sub)section
     * If $movedsectionnum is specified, we ignore it on the present location but add it
     * under $movetoparentnum before $movebeforenum
     *
     * @param array            $neworder the result or re-ordering, array (sectionid => sectionnumber)
     * @param int|section_info $cursection
     * @param int|section_info $movedsectionnum
     * @param int|section_info $movetoparentnum
     * @param int|section_info $movebeforenum
     */
    public function reorder_sections(&$neworder, $cursection, $movedsectionnum = null,
                                     $movetoparentnum = null, $movebeforenum = null) {
        // Normalise arguments.
        $cursection      = $this->format->get_section($cursection);
        $movetoparentnum = $this->resolve_section_number($movetoparentnum);
        $movebeforenum   = $this->resolve_section_number($movebeforenum);
        $movedsectionnum = $this->resolve_section_number($movedsectionnum);
        if ($movedsectionnum === null) {
            $movebeforenum = $movetoparentnum = null;
        }

        // Ignore section being moved.
        if ($movedsectionnum !== null && $movedsectionnum == $cursection->section) {
            return;
        }

        // Add current section to $neworder.
        $neworder[$cursection->id] = count($neworder);
        // Loop through subsections and reorder them (insert $movedsectionnum if necessary).
        foreach ($this->get_subsections($cursection) as $subsection) {
            if ($movebeforenum && $subsection->section == $movebeforenum) {
                $this->reorder_sections($neworder, $movedsectionnum);
            }
            $this->reorder_sections($neworder, $subsection, $movedsectionnum, $movetoparentnum, $movebeforenum);
        }
        if (!$movebeforenum && $movetoparentnum !== null && $movetoparentnum == $cursection->section) {
            $this->reorder_sections($neworder, $movedsectionnum);
        }
    }

    /**
     * Moves section to the specified position
     *
     * @param int|section_info      $section
     * @param int|section_info      $parent
     * @param null|int|section_info $before
     * @return int new section number
     */
    public function move_section($section, $parent, $before = null) {
        global $DB;
        $section          = $this->format->get_section($section);
        $parent           = $this->format->get_section($parent);
        $newsectionnumber = $section->section;
        if (!$this->can_move_section_to($section, $parent, $before)) {
            return $newsectionnumber;
        }
//        if ($section->visible != $parent->visible && $section->parent != $parent->section) {
//            // Section is changing parent and new parent has different visibility than the section.
//            if ($section->visible) {
//                // Visible section is moved under hidden parent.
//                $updatesectionvisible    = 0;
//                $updatesectionvisibleold = 1;
//            } else {
//                // Hidden section is moved under visible parent.
//                if ($section->visibleold) {
//                    $updatesectionvisible    = 1;
//                    $updatesectionvisibleold = 1;
//                }
//            }
//        }

        // Find the changes in the sections numbering.
        $origorder = array();
        foreach ($this->format->get_sections() as $subsection) {
            $origorder[$subsection->id] = $subsection->section;
        }
        $neworder = array();
        $this->reorder_sections($neworder, 0, $section->section, $parent, $before);
        if (count($origorder) != count($neworder)) {
            die('Error in sections hierarchy'); // TODO.
        }
        $changes = array();
        foreach ($origorder as $id => $num) {
            if ($num == $section->section) {
                $newsectionnumber = $neworder[$id];
            }
            if ($num != $neworder[$id]) {
                $changes[$id] = array('old' => $num, 'new' => $neworder[$id]);
                if ($num && $this->course->marker == $num) {
                    $changemarker = $neworder[$id];
                }
            }
            if ($parent->section === $num) {
                $newparentnum = $neworder[$id];
            }
        }

        if (empty($changes) && $newparentnum == $section->parent) {
            return $newsectionnumber;
        }

        // Build array of required changes in field 'parent'.
        $changeparent = array();
        foreach ($this->format->get_sections() as $subsection) {
            foreach ($changes as $id => $change) {
                if ($subsection->parent == $change['old']) {
                    $changeparent[$subsection->id] = $change['new'];
                }
            }
        }
        $changeparent[$section->id] = $newparentnum;

        // Update all in database in one transaction.
        $transaction = $DB->start_delegated_transaction();
        // Update sections numbers in 2 steps to avoid breaking database uniqueness constraint.
        foreach ($changes as $id => $change) {
            $DB->set_field('course_sections', 'section', -$change['new'], array('id' => $id));
        }
        foreach ($changes as $id => $change) {
            $DB->set_field('course_sections', 'section', $change['new'], array('id' => $id));
        }
        // Change parents of their subsections.
        foreach ($changeparent as $id => $newnum) {
            $this->format->update_section_format_options(array('id' => $id, 'parent' => $newnum));
        }
        $transaction->allow_commit();
        rebuild_course_cache($this->courseid, true);
        if (isset($changemarker)) {
            course_set_marker($this->courseid, $changemarker);
        }
//        if (isset($updatesectionvisible)) {
//            $this->format->set_section_visible($newsectionnumber, $updatesectionvisible, $updatesectionvisibleold);
//        }
        return $newsectionnumber;
    }

    /**
     * Checks if given section has another section among it's parents
     *
     * @param int|section_info $section   child section
     * @param int              $parentnum parent section number
     * @return boolean
     */
    public function section_has_parent($section, $parentnum) {
        if (!$section) {
            return false;
        }
        $section = $this->format->get_section($section);
        if (!$section->section) {
            return false;
        } elseif ($section->parent == $parentnum) {
            return true;
        } elseif ($section->parent == 0) {
            return false;
        } else {
            // Some error.
            return false;
        }
    }

    /**
     * Check if we can move the section to this position
     *
     * not allow to insert section as it's own subsection
     * not allow to insert section directly before or after itself (it would not change anything)
     *
     * @param int|section_info      $section
     * @param int|section_info      $parent
     * @param null|section_info|int $before null if in the end of subsections list
     */
    public function can_move_section_to($section, $parent, $before = null) {
        $section = $this->format->get_section($section);
        $parent  = $this->format->get_section($parent);
        if ($section === null || $parent === null ||
            !has_capability('moodle/course:update', context_course::instance($this->courseid))) {
            return false;
        }
        // Check that $parent is not subsection of $section.
        if ($section->section == $parent->section || $this->section_has_parent($parent, $section->section)) {
            return false;
        }

        if ($before) {
            if (is_string($before)) {
                $before = (int)$before;
            }
            $before = $this->format->get_section($before);
            // Check that it's a subsection of $parent.
            if (!$before || $before->parent !== $parent->section) {
                return false;
            }
        }

        if ($section->parent == $parent->section) {
            // Section's parent is not being changed
            // do not insert section directly before or after itself.
            if ($before && $before->section == $section->section) {
                return false;
            }
            $subsections = array();
            $lastsibling = null;
            foreach ($this->format->get_sections() as $num => $sibling) {
                if ($sibling->parent == $parent->section) {
                    if ($before && $before->section == $num) {
                        return !($lastsibling && $lastsibling->section == $section->section);
                    }
                    $lastsibling = $sibling;
                }
            }
            if ($lastsibling && !$before && $lastsibling->section == $section->section) {
                return false;
            }
        }
        return true;
    }

    public function move_lecture($lection, $chapter) {
        global $DB;
        $section          = $this->format->get_section($lection);
        $parent           = $this->format->get_section($chapter);

        print_object($section);
        print_object($chapter);

    }

    /**
     * moves a chapter to given position
     * @param $chaptersectionumber int the section number of the chapter to move
     * @param $position            int
     * @return void
     */
    public function move_chapter($chaptersectionumber, $position) {

    }

    /**
     * @param $chapterid int the id of the chapter
     * @return int the index of the chapter
     * @deprecated use get_chapter_no_from_number() instead
     *                   Calculates the index of the given chapter
     */
    public function get_chapter_no_from_id($chapterid) {
        $modinfo = $this->format->get_modinfo();
        $chapter = 1;
        foreach ($modinfo->get_section_info_all() as $sectionnum => $section) {
            if ($sectionnum == 0) {
                continue;
            }

            if ($section->id == $chapterid) {
                return $chapter;
            }

            if ($section->parent === 0) {
                $chapter++;
            }
        }

        return $chapter;
    }

    /**
     * Calculates the index of the given chapter
     * @param $chaptersectionumber int the section number of the chapter
     * @return int the index of the chapter
     */
    public function get_chapter_no_from_number($chaptersectionumber) {
        if ($chaptersectionumber == 0) return 1;
        $modinfo = $this->format->get_modinfo();
        $chapter = 1;
        foreach ($modinfo->get_section_info_all() as $sectionnum => $section) {
            if ($sectionnum == 0) {
                continue;
            }

            if ($section->section == $chaptersectionumber) {
                return $chapter;
            }

            if ($section->parent === 0) {
                $chapter++;
            }
        }

        return $chapter;
    }

    public function get_chapter_before(int $chapter): ?\section_info {
        $modinfo = $this->format->get_modinfo();
        $lastparent = null;
        foreach ($modinfo->get_section_info_all() as $section) {
            if ($section->section == 0) {
                continue;
            }

            if ($section->parent == 0) {
                if ($section->section != $chapter) {
                    $lastparent = $section;
                } else {
                    break;
                }
            }
        }
        return $lastparent;
    }

    /**
     * @param $chapterid int the id of the chapter
     * @param $lectionid int the id of the lection
     * @return int the index of the lection in the chapter
     * @deprecated use get_lection_no_from_number() instead
     *                   calculates the index of the given lection in the given chapter
     */
    public function get_lection_no_from_id($chapterid, $lectionid) {
        $modinfo = $this->format->get_modinfo();
        $lection = 1;
        foreach ($modinfo->get_section_info_all() as $sectionnum => $section) {
            if ($sectionnum == 0) {
                continue;
            }

            if ($section->id == $lectionid && $section->parent == $chapterid) {
                return $lection;
            }

            if ($section->parent === $chapterid) {
                $lection++;
            }
        }

        return $lection;
    }

    /**
     * calculates the index of the given lection in the given chapter
     * @param $chaptersectionumber  int the section number of the chapter
     * @param $lectionsectionnumber int the section number of the lection
     * @return int the index of the lection in the chapter
     */
    public function get_lection_no_from_number($chaptersectionumber, $lectionsectionnumber) {
        $modinfo = $this->format->get_modinfo();
        $lection = 1;
        foreach ($modinfo->get_section_info_all() as $sectionnum => $section) {
            if ($sectionnum == 0) {
                continue;
            }

            if ($section->parent == $chaptersectionumber && $section->section == $lectionsectionnumber) {
                return $lection;
            }

            if ($section->parent === $chaptersectionumber) {
                $lection++;
            }
        }

        return $lection;
    }

    public function get_sections() {
        $modinfo  = $this->format->get_modinfo();
        $chapters = [];
        foreach ($modinfo->get_section_info_all() as $sectionnum => $section) {
            if ($section->parent === 0 || $sectionnum == 0) {
                $section->subsections        = [];
                $chapters[$section->section] = $section;
            } else {
                $chapters[$section->parent]->subsections[] = $section;
            }
        }
        return $chapters;
    }

    public function get_subsections($section) {
        $sectionnum  = $this->resolve_section_number($section);
        $subsections = array();
        foreach ($this->format->get_sections() as $num => $subsection) {
            if ($subsection->parent == $sectionnum && $num != $sectionnum) {
                $subsections[$num] = $subsection;
            }
        }
        return $subsections;
    }

}