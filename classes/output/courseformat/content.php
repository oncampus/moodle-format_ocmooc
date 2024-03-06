<?php

namespace format_ocmooc\output\courseformat;

use core_courseformat\output\local\content as content_base;
use core_reportbuilder\local\aggregation\count;
use format_ocmooc\moocnav;
use section_info;
use stdClass;

class content extends content_base {

    public function export_for_template(\renderer_base $output) {
        global $COURSE, $USER;

        $data = parent::export_for_template($output);

        $chapterpreferencename = "ocmooc_last_chapter_cid_{$COURSE->id}";
        $lastchapter = get_user_preferences($chapterpreferencename, 1);
        $chapter = optional_param('chapter', $lastchapter, PARAM_INT);
        $chapters = $this->get_chapters($output);
        $data->footerfirst = false;
        $data->lastfooter = false;

        if ($chapter <= 0 || $chapter > count($chapters)) {
            $chapter = 1;
        }

        $lectionpreferencename = "ocmooc_last_lection_cid_{$COURSE->id}";
        $lastlection = get_user_preferences($lectionpreferencename, 1);
        $lection = optional_param('lection', false, PARAM_INT);
        // If the current chapter is not equal to the saved chapter.
        if ($chapter != $lastchapter) {
            // Save the current chapter.
            set_user_preference($chapterpreferencename, $chapter);
            // If no lection information is applied, set the lection to start lection of new selected chapter.
            if (!$lection) {
                $lection = 1;
            }
        } else if (!$lection) {
            // If we are still in the same chapter and lection should not be changed, apply the lastlection.
            $lection = $lastlection;
        }

        // If the last saved lection is different to the current selected lection, save it.
        if ($lection != $lastlection) {
            set_user_preference($lectionpreferencename, $lection);
        }

        if (array_key_exists($chapter, $chapters)) {
            $chapters[$chapter]->selected = true;
            $deleteurl = new \moodle_url('/course/format/ocmooc/sectionhandler.php');
            $deleteurl->param('action', "confirm-delete");
            $deleteurl->param('sesskey', sesskey());
            $deleteurl->param('courseid', $COURSE->id);
            $deleteurl->param('sectionnum', $chapters[$chapter]->section);
            $chapters[$chapter]->deleteurl = $deleteurl->out(false);
            $data->currentchapter = $chapters[$chapter];
        }

        $elementsize = 284;
        $data->chapters = array_values($chapters);
        $sections = $this->get_chapter_sections($chapters[$chapter], $chapter, $output);
        $data->chaptersstartwidth = count($chapters) * $elementsize;

        $data->chapterstarttransform = (($chapter - 1) * -$elementsize) + $elementsize;
        $data->quicknav = true;

        $params = ['id' => $COURSE->id, 'chapter' => $chapter];
        if (empty($sections)) {
            $data->sections = [];
            $data->firstsection = true;
            $data->lastsection = true;
            if ($chapter < count($chapters)) {
                $params['chapter'] = $chapter + 1;
                $params['lection'] = 1;
                $data->nextsection = (new \moodle_url('/course/view.php', $params))->out(false);
            }
            $params = ['id' => $COURSE->id, 'chapter' => $chapter];
            if ($chapter > 1) {
                $data->footerfirst = false;
                $prevsections = $this->get_chapter_sections($chapters[$chapter - 1], $chapter - 1, $output);
                $params['chapter'] = $chapter - 1;
                $params['lection'] = count($prevsections);
                $data->prevsection = (new \moodle_url('/course/view.php', $params))->out(false);
            }
        } else {
            if ($lection <= 0 || $lection > count($sections)) {
                $lection = 1;
            }

            $sections[$lection]->selected = true;
            $data->sections = [$sections[$lection]];

            $navlectionarr = [];
            $maxlectionnavitems = 5;
            $countsides = ($maxlectionnavitems - 1) / 2;
            $sectioncount = count($sections);

            if ($lection <= 1 && $chapter === 1) {
                $data->footerfirst = true;
            }
            if ($chapter === count($chapters) && $lection === count($sections)) {
                $data->lastfooter = true;
            }

            if (count($sections) > $maxlectionnavitems) {
                if ($lection - $countsides <= 1) {
                    $data->firstsection = true;
                }
                if ($lection + $countsides >= $sectioncount) {
                    $data->lastsection = true;
                }
                $countleft = $lection - 3 > 0 ? $lection - 3 : 0;
                $overflow = ($lection - 3) + $maxlectionnavitems >= $sectioncount ?
                        ($lection - 3) + $maxlectionnavitems - $sectioncount : 0;
                if ($overflow && $lection > 0) {
                    $countleft -= $lection > $overflow ? $overflow : $lection;
                }
                $navlectionarr = array_slice($sections, $countleft, $maxlectionnavitems);

            } else {
                $navlectionarr += array_values($sections);
                $data->firstsection = true;
                $data->lastsection = true;
            }

            $data->chaptersections = $navlectionarr;

            if ($chapter < count($chapters) && $lection === $sectioncount) {
                $params['chapter'] = $chapter + 1;
                $params['lection'] = 1;
                $data->nextsection = (new \moodle_url('/course/view.php', $params))->out(false);
            } else {
                $params['lection'] = $lection + 1;
                $data->nextsection = (new \moodle_url('/course/view.php', $params))->out(false);
            }
            $params = ['id' => $COURSE->id, 'chapter' => $chapter];
            if ($chapter > 1 && $lection <= 1) {
                $prevsections = $this->get_chapter_sections($chapters[$chapter - 1], $chapter - 1, $output);

                $params['chapter'] = $chapter - 1;
                $params['lection'] = count($prevsections);
                $data->prevsection = (new \moodle_url('/course/view.php', $params))->out(false);
            } else {
                $params['lection'] = $lection - 1;
                $data->prevsection = (new \moodle_url('/course/view.php', $params))->out(false);
            }
        }

        #$data->chapters = $this->get_chapters($output);
        #$data->singlesection = $chapters[$chapter];
        #$data->sectionreturn = $data->singlesection;

        $data->moocnav = moocnav::get_moocnav_entries();
        $data->moocnavdropdown = moocnav::get_drowdown_items();
        $data->showdropdown = !empty($data->moocnavdropdown);
        return $data;
    }

    public
    function get_chapters($output) {
        global $COURSE, $PAGE, $DB;

        $chapters = [];
        $context = \context_course::instance($COURSE->id);

        $modinfo = $this->format->get_modinfo();
        $chapter = 1;
        foreach ($modinfo->get_section_info_all() as $sectionnum => $section) {
            if ($sectionnum == 0) {
                continue;
            }

            if ($section->parent === 0) {
                if ($this->format->is_section_visible($section)) {
                    $rawtitle = $section->name;
                    $sectionid = $section->id;

                    $section = new $this->sectionclass($this->format, $section);
                    $section = $section->export_for_template($output);

                    $section->rawtitle = $rawtitle;
                    $params = ['id' => $COURSE->id, 'chapter' => $chapter, 'lection' => 1];
                    $section->chapternum = $chapter;
                    $section->url = (new \moodle_url('/course/view.php', $params))->out(false);
                    if ($PAGE->user_is_editing()) {
                        $section->editurl = (new \moodle_url('/course/editsection.php', ['id' => $sectionid]));
                    }
                    //get images
                    if ($section->summary->summarytext) {
                        $img_link = explode('src="', $section->summary->summarytext);
                        if (count($img_link) > 1) {
                            $img = substr($img_link[1], 0, strpos($img_link[1], '"'));
                            $section->imgurl = $img;
                        }
                    }

                    $section->section = $sectionnum;
                    $section->sections = $this->get_chapter_sections($section, $chapter, $output);
                    $section->progress = $this->get_progress_by_sections($section->sections);
                    $section->sectioncount = count($section->sections);

                    $chapters[$chapter] = $section;
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
    public
    function get_chapter_sections($chapter, $chapternum, $output) {
        global $COURSE;

        $sections = [];

        if (is_number($chapter)) {
            $chaptersectionnumber = $chapter;
        } else {
            $chaptersectionnumber = $chapter->section;
        }

        $modinfo = $this->format->get_modinfo();
        $sectionnum = 1;
        foreach ($modinfo->get_section_info_all() as $section) {
            if ($section->section != 0 && $section->parent == $chaptersectionnumber) {
                $rawtitle = $section->name;

                $section = new $this->sectionclass($this->format, $section);
                $section = $section->export_for_template($output);

                $section->sectionreturnid = $sectionnum - 1;
                $section->rawtitle = $rawtitle;
                $section->sectionnum = $sectionnum;
                $section->progress = $this->get_progress_by_section($section);
                $section->hasnoprogress = $section->progress === false;
                $params = ['id' => $COURSE->id, 'chapter' => $chapternum, 'lection' => $sectionnum];
                $section->url = (new \moodle_url('/course/view.php', $params))->out(false);

                $sections[$sectionnum] = $section;

                $sectionnum++;
            }
        }

        return $sections;
    }

    private
    function get_progress_by_sections($sections) {
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
    private
    function get_progress_by_section($section) {
        global $USER, $COURSE;

        $completion = new \completion_info($COURSE);

        if (!$completion->is_enabled()) {
            return false;
        }

        $modules = $completion->get_activities();
        $count = 0;
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

        return round(($completed / $count) * 100);
    }

    public
    function get_template_name(\renderer_base $renderer): string {
        return 'format_ocmooc/local/content';
    }

}