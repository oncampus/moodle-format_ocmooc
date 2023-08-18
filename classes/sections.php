<?php

namespace format_ocmooc;

class sections {

    private $courseid;

    private $course;

    private $format;

    public function __construct($courseid) {
        $this->courseid = $courseid;
        $this->course = get_course($courseid);
        $this->format = course_get_format($courseid);
    }

    public function add_chapter() {
        $section = course_create_section($this->courseid);
    }

    public function add_lection($chapterid) {
        $section = course_create_section($this->courseid);
        $section->parent = $chapterid;
        $this->format->update_section_format_options($section);
    }

    public function delete_section($id) {
        global $DB;

        $records = $DB->get_records('course_format_options',
                ['courseid' => $this->courseid, 'format' => 'ocmooc', 'name' => 'parent', 'value' => $id]);
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

    public function move_lecture($lectureid, $position, $chapterid = null) {
        // Chapterid to move to another chapter.
        // If Chapterid is equal to 0, it's also possible to change lecture to chapter
    }

    public function move_chapter($chapterid, $position) {

    }

}