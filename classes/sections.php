<?php

namespace format_ocmooc;

class sections {

    private $courseid;

    private $course;

    private $format;

    public function __construct($courseid) {
        $this->courseid = $courseid;
        $this->course   = get_course($courseid);
        $this->format   = course_get_format($courseid);
    }

    public function add_chapter() {
        $section = course_create_section($this->courseid);
        return $this->get_chapter_no_from_id($section->id);
    }

    public function add_lection($chapterid) {
        $section         = course_create_section($this->courseid);
        $section->parent = $chapterid;
        $this->format->update_section_format_options($section);

        $chapterno = $this->get_chapter_no_from_id($chapterid);
        $lectiono  = $this->get_lection_no_from_id($chapterid, $section->id);

        return [$chapterno, $lectiono];
    }

    public function deleteconfirmation($id, $course, $cancelurl) {
        global $PAGE, $OUTPUT,$DB;
        $section = $DB->get_record('course_sections', ['id' => $id], '*', MUST_EXIST);
        $sectionnum = $section->section;

        // Get section_info object with all availability options.
        $sectioninfo = get_fast_modinfo($course)->get_section_info($sectionnum);
        if (get_string_manager()->string_exists('deletesection', 'format_' . $course->format)) {
            $strdelete = get_string('deletesection', 'format_' . $course->format);
        } else {
            $strdelete = get_string('deletesection');
        }
        $PAGE->navbar->add($strdelete);
        $PAGE->set_title($strdelete);
        $PAGE->set_heading($course->fullname);
        echo $OUTPUT->header();
        echo $OUTPUT->box_start('noticebox');
        $deleteurl = new \moodle_url('/course/format/ocmooc/sectionhandler.php');
        $deleteurl->param('action', "delete");
        $deleteurl->param('id', $id);
        $deleteurl->param('courseid', $course->id);
        $formcontinue = new \single_button($deleteurl, get_string('delete'));
        $formcancel   = new \single_button($cancelurl, get_string('cancel'), 'get');
        echo $OUTPUT->confirm(get_string('confirmdeletesection', '',
                get_section_name($course, $sectioninfo)), $formcontinue, $formcancel);
        echo $OUTPUT->box_end();
        echo $OUTPUT->footer();
        exit;
    }

    public function delete_section($id) {
        global $DB;

        $sql     = 'SELECT * FROM {course_format_options} WHERE ';
        $sql     .= $DB->sql_like('format', "'ocmooc'");
        $sql     .= ' AND ';
        $sql     .= $DB->sql_like('name', "'parent'");
        $sql     .= ' AND ';
        $sql     .= ' courseid  = :courseid';
        $sql     .= ' AND ';
        $sql     .= ' value  = :value';
        $records = $DB->get_records_sql($sql,
                ['courseid' => $this->courseid, 'value' => $id]);
        if ($records) {
            foreach ($records as $record) {
                $child = $this->format->get_section($record->sectionid);
                if ($this->format->can_delete_section($child)) {
                    $this->format->delete_section($child);
                } else {
                    $child->parent = 0;
                    $this->format->update_section_format_options($child);
                }
            }
        }

        if ($this->format->can_delete_section($id)) {
            $this->format->delete_section($id);
        }
    }

    public function reorder_sections() {

    }

    public function move_lecture($lectureid, $position, $chapterid = NULL) {
        // Chapterid to move to another chapter.
        // If Chapterid is equal to 0, it's also possible to change lecture to chapter
    }

    public function move_chapter($chapterid, $position) {

    }

    private function get_chapter_no_from_id($chapterid) {
        $modinfo = $this->format->get_modinfo();
        $chapter = 0;
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

    private function get_lection_no_from_id($chapterid, $lectionid) {
        $modinfo = $this->format->get_modinfo();
        $lection = 0;
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

}