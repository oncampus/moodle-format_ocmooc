<?php

namespace format_ocmooc\output\courseformat;

use core_courseformat\output\local\content as content_base;
use core_reportbuilder\local\aggregation\count;
use format_ocmooc\moocnav;
use section_info;
use stdClass;

class content extends content_base {

    public function export_for_template(\renderer_base $output) {
        global $COURSE;

        $data = parent::export_for_template($output);

        $chapter  = optional_param('chapter', 1, PARAM_INT);
        $chapters = $this->get_chapters($output);

        if ($chapter <= 0 || $chapter > count($chapters)) {
            $chapter = 1;
        }

        if (array_key_exists($chapter, $chapters)) {
            $chapters[$chapter]->selected = true;
            $deleteurl                    = new \moodle_url('/course/format/ocmooc/sectionhandler.php');
            $deleteurl->param('action', "confirm-delete");
            $deleteurl->param('sesskey', sesskey());
            $deleteurl->param('courseid', $COURSE->id);
            $deleteurl->param('sectionnum', $chapters[$chapter]->section);
            $chapters[$chapter]->deleteurl = $deleteurl->out(false);
            $data->currentchapter          = $chapters[$chapter];
        }

        $elementsize              = 284;
        $data->chapters           = $chapters;
        $sections                 = $this->get_chapter_sections($chapters[$chapter], $chapter, $output);
        $data->chaptersections    = $sections;
        $data->chaptersstartwidth = count($chapters) * $elementsize;

        $data->chapterstarttransform = ($chapter * -$elementsize) + $elementsize;

        $lection = optional_param('lection', 1, PARAM_INT);
        if (empty($sections)) {
            $data->sections     = [];
            $data->firstsection = true;
            $data->lastsection  = true;
        } else {
            if ($lection <= 0 || $lection > count($sections)) {
                $lection = 1;
            }

            $sections[$lection]->selected = true;
            $data->sections               = [$sections[$lection]];

            $data->quicknav = true;
            if ($lection <= 1) {
                $data->firstsection = true;
            } else if ($lection == count($sections)) {
                $data->lastsection = true;
            }

            $params            = ['id' => $COURSE->id, 'chapter' => $chapter];
            $params['lection'] = $lection + 1;
            $data->nextsection = (new \moodle_url('/course/view.php', $params))->out(false);

            $params['lection'] = $lection - 1;
            $data->prevsection = (new \moodle_url('/course/view.php', $params))->out(false);
        }

        #$data->chapters = $this->get_chapters($output);
        #$data->singlesection = $chapters[$chapter];
        #$data->sectionreturn = $data->singlesection;

        $data->moocnav = moocnav::get_moocnav_entries();

        return $data;
    }

    public function get_chapters($output) {
        global $COURSE, $PAGE, $DB;

        $chapters = [];
        $context  = \context_course::instance($COURSE->id);

        $modinfo = $this->format->get_modinfo();
        $chapter = 1;
        foreach ($modinfo->get_section_info_all() as $sectionnum => $section) {
            if ($sectionnum == 0) {
                continue;
            }

            if ($section->parent === 0) {
                if ($this->format->is_section_visible($section)) {
                    $rawtitle  = $section->name;
                    $sectionid = $section->id;

                    $section = new $this->sectionclass($this->format, $section);
                    $section = $section->export_for_template($output);

                    $section->rawtitle   = $rawtitle;
                    $params              = ['id' => $COURSE->id, 'chapter' => $chapter, 'lection' => 1];
                    $section->chapternum = $chapter;
                    $section->url        = (new \moodle_url('/course/view.php', $params))->out(false);
                    if ($PAGE->user_is_editing()) {
                        $section->editurl = (new \moodle_url('/course/editsection.php', ['id' => $sectionid]));
                    }

                    $file = $DB->get_record('files',
                                            ['contextid' => $context->id, 'component' => 'course', 'filearea' => 'section', 'itemid' => $sectionid,
                                            ]);
                    if ($file) {
                        $section->imgurl = \moodle_url::make_pluginfile_url($context->id, 'course', 'section', $sectionid, '/',
                                                                            $file->filename);
                    } else {
                        // TODO: Use placeholder image url.
                    }

                    $section->section      = $sectionnum;
                    $section->sections     = $this->get_chapter_sections($section, $chapter, $output);
                    $section->progress     = $this->get_progress_by_sections($section->sections);
                    $section->sectioncount = count($section->sections);

                    $chapters[] = $section;
                }
                $chapter++;
            }
        }

        return $chapters;
    }

    /**
     * @param $chapter section_info|stdClass|int the chapter section
     * @param $chapternum
     * @param $output
     * @return array
     * @throws \moodle_exception
     */
    public function get_chapter_sections($chapter, $chapternum, $output) {
        global $COURSE;

        $sections = [];

        if (is_number($chapter)) {
            $chaptersectionnumber = $chapter;
        } else {
            $chaptersectionnumber = $chapter->section;
        }

        $modinfo    = $this->format->get_modinfo();
        $sectionnum = 1;
        foreach ($modinfo->get_section_info_all() as $section) {
            if ($section->section != 0 && $section->parent == $chaptersectionnumber) {
                $rawtitle = $section->name;

                $section = new $this->sectionclass($this->format, $section);
                $section = $section->export_for_template($output);

                $section->sectionreturnid = $sectionnum - 1;
                $section->rawtitle        = $rawtitle;
                $section->sectionnum      = $sectionnum;
                $section->progress        = $this->get_progress_by_section($section);
                $params                   = ['id' => $COURSE->id, 'chapter' => $chapternum, 'lection' => $sectionnum];
                $section->url             = (new \moodle_url('/course/view.php', $params))->out(false);

                $sections[$sectionnum] = $section;

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

    /**
     * @param $section section_info|stdClass the section
     * @return false|float|int
     */
    private function get_progress_by_section($section) {
        global $USER, $COURSE;

        $completion = new \completion_info($COURSE);

        if (!$completion->is_enabled()) {
            return false;
        }

        if (!$completion->is_tracked_user($USER->id)) {
            return false;
        }

        if ($completion->is_course_complete($USER->id)) {
            return 100;
        }

        $modules   = $completion->get_activities();
        $count     = 0;
        $completed = 0;
        foreach ($modules as $module) {
            if ($module->section == $section->id) {
                $count++;
                $data = $completion->get_data($module, true, $USER->id);
                if (($data->completionstate == COMPLETION_INCOMPLETE) || ($data->completionstate == COMPLETION_COMPLETE_FAIL)) {
                    $completed += 0;
                } else {
                    $completed += 1;
                };
            }
        }

        if ($count == 0) {
            return false;
        }

        return ($completed / $count) * 100;
    }

    public function get_template_name(\renderer_base $renderer): string {
        return 'format_ocmooc/local/content';
    }

}