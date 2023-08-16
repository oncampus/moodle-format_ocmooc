<?php

namespace format_ocmooc\output\courseformat;

use core_courseformat\output\local\content as content_base;
use core_reportbuilder\local\aggregation\count;
use format_ocmooc\moocnav;

class content extends content_base {

    public function export_for_template(\renderer_base $output) {
        global $COURSE;

        $data = parent::export_for_template($output);

        $chapter = optional_param('chapter', 0, PARAM_INT);
        $chapters = $this->get_chapters($output);

        if ($chapter < 0 || $chapter > count($chapters)) {
            $chapter = 0;
        }

        $chapters[$chapter]->selected = true;
        $data->currentchapter = $chapters[$chapter];

        $elementsize = 284;
        $data->chapters = $chapters;
        $sections = $this->get_chapter_sections($chapters[$chapter], $chapter, $output);
        $data->chaptersections = $sections;
        $data->chaptersstartwidth = count($chapters) * $elementsize;

        $data->chapterstarttransform = ($chapter * -$elementsize) + $elementsize;

        $section = optional_param('lection', 0, PARAM_INT);
        if (empty($sections)) {
            $data->sections = array();
            $data->firstsection = true;
            $data->lastsection = true;
        } else {
            if ($section < 0 || $section > count($sections)) {
                $section = 0;
            }

            $sections[$section]->selected = true;
            $data->sections = [$sections[$section]];

            $data->quicknav = true;
            if ($section <= 0) {
                $data->firstsection = true;
            } else if ($section == count($sections) - 1) {
                $data->lastsection = true;
            }

            $params = ['id' => $COURSE->id, 'chapter' => $chapter];
            $params['lection'] = $section + 1;
            $data->nextsection = (new \moodle_url('/course/view.php', $params))->out(false);

            $params['lection'] = $section - 1;
            $data->prevsection = (new \moodle_url('/course/view.php', $params))->out(false);
        }

        #$data->chapters = $this->get_chapters($output);
        #$data->singlesection = $chapters[$chapter];
        #$data->sectionreturn = $data->singlesection;

        $data->moocnav = moocnav::get_moocnav_entries();

        return $data;
    }

    public function get_chapters($output) {
        global $COURSE;

        $chapters = array();

        $modinfo = $this->format->get_modinfo();
        $chapter = 0;
        foreach ($modinfo->get_section_info_all() as $sectionnum => $section) {
            if ($sectionnum == 0) {
                continue;
            }

            if ($section->parent === 0) {
                if ($this->format->is_section_visible($section)) {
                    $section = new $this->sectionclass($this->format, $section);

                    $section = $section->export_for_template($output);
                    $params = ['id' => $COURSE->id, 'chapter' => $chapter, 'lection' => 0];
                    $section->chapternum = $chapter + 1;
                    $section->url = (new \moodle_url('/course/view.php', $params))->out(false);

                    $section->sections = $this->get_chapter_sections($section, $chapter, $output);
                    $section->progress = $this->get_progress_by_sections($section->sections);
                    $section->sectioncount = count($section->sections);

                    $chapters[] = $section;
                }
                $chapter++;
            }
        }

        return $chapters;
    }

    public function get_chapter_sections($chapter, $chapternum, $output) {
        global $COURSE;

        $sections = array();

        if (is_number($chapter)) {
            $chapterid = $chapter;
        } else {
            $chapterid = $chapter->id;
        }

        $modinfo = $this->format->get_modinfo();
        $sectionnum = 0;
        foreach ($modinfo->get_section_info_all() as $section) {
            if ($section != 0 && $section->parent == $chapterid) {
                $section = new $this->sectionclass($this->format, $section);

                $section = $section->export_for_template($output);
                $section->sectionnum = $sectionnum + 1;
                $section->progress = $this->get_progress_by_section($section);
                $params = ['id' => $COURSE->id, 'chapter' => $chapternum, 'lection' => $sectionnum];
                $section->url = (new \moodle_url('/course/view.php', $params))->out(false);

                $sections[] = $section;

                $sectionnum++;
            }
        }

        return $sections;
    }

    private function get_progress_by_sections($sections) {
        $progress = 0;
        if (!empty($sections)) {
            foreach ($sections as $section) {
                $progress += $this->get_progress_by_section($section);
            }
            $progress /= count($sections);
        }
        return $progress;
    }

    private function get_progress_by_section($section) {
        // TODO: Implement progress calculation
        return 0;
    }

    public function get_template_name(\renderer_base $renderer): string {
        return 'format_ocmooc/local/content';
    }

}