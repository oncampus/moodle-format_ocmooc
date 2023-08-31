<?php

namespace format_ocmooc\output\courseformat\content;

use \core_courseformat\output\local\content\addsection as addsection_base;
use stdClass;

class addsection extends addsection_base {

    public function export_for_template(\renderer_base $output): stdClass {
        global $COURSE;

        $data = new stdClass();

        $chapter = optional_param('chapter', 1, PARAM_INT);

        $format      = $this->format;
        $lastsection = $format->get_last_section_number();
        $maxsections = $format->get_max_sections();

        $params = [
            'courseid' => $COURSE->id,
            'action'   => 'addchapter',
            'sesskey'  => sesskey(),
            'position' => $maxsections - $lastsection

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

        if (count((array)$data)) {
            $data->showaddsection = true;
        }

        return $data;
    }

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