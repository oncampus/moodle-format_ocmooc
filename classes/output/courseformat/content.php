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

namespace format_ocmooc\output\courseformat;

use core_courseformat\output\local\content as content_base;
use format_ocmooc\moocnav;
use section_info;
use stdClass;

/**
 * Content output class for the OCMOOC course format.
 *
 * @package    format_ocmooc
 * @copyright  2023 OnCampus GmbH <support@oncampus.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content extends content_base {
    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output Renderer base.
     * @return stdClass
     */
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

            $maxlectionnavitems = 5;
            $sectioncount = count($sections);

            if ($lection <= 1 && $chapter === 1) {
                $data->footerfirst = true;
            }
            if ($chapter === count($chapters) && $lection === count($sections)) {
                $data->lastfooter = true;
            }

            if (count($sections) > $maxlectionnavitems) {
                $data->firstsection = true;
                $data->lastsection = false;
                $data->chaptersections = array_values($sections);
            } else {
                $data->firstsection = true;
                $data->lastsection = true;
                $data->chaptersections = array_values($sections);
            }

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

        $data->moocnav = moocnav::get_moocnav_entries();
        $data->moocnavdropdown = moocnav::get_drowdown_items();
        $data->showdropdown = !empty($data->moocnavdropdown);
        return $data;
    }

    /**
     * Get all chapters for the course.
     *
     * @param \renderer_base $output Renderer base.
     * @return array Array of chapter objects.
     */
    public function get_chapters($output) {
        global $COURSE, $PAGE;

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
                    $section->ischapter = true;
                    $section->url = (new \moodle_url('/course/view.php', $params))->out(false);
                    if ($PAGE->user_is_editing()) {
                        $section->editurl = (new \moodle_url('/course/editsection.php', ['id' => $sectionid]));
                    }
                    // Get images.
                    if ($section->summary->summarytext) {
                        $imglink = explode('src="', $section->summary->summarytext);
                        if (count($imglink) > 1) {
                            $img = substr($imglink[1], 0, strpos($imglink[1], '"'));
                            $section->imgurl = $img;
                        }
                    }

                    $section->section = $sectionnum;
                    $section->sections = $this->get_chapter_sections($section, $chapter, $output);
                    $section->progress = $this->get_progress_by_sections($section->sections);
                    $section->sectioncount = count($section->sections);
                    $section->parent = 0;

                    $chapters[$chapter] = $section;
                }
                $chapter++;
            }
        }

        return $chapters;
    }

    /**
     * Get all sections for a specific chapter.
     *
     * @param mixed $chapter The chapter section info, section number, or object.
     * @param int $chapternum The chapter number.
     * @param \renderer_base $output Renderer base.
     * @return array Array of section objects.
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
                $section->ischapter = false;
                $params = ['id' => $COURSE->id, 'chapter' => $chapternum, 'lection' => $sectionnum];
                $section->url = (new \moodle_url('/course/view.php', $params))->out(false);
                $section->parent = $chapter->id;

                $sections[$sectionnum] = $section;

                $sectionnum++;
            }
        }

        return $sections;
    }

    /**
     * Calculate the progress percentage for a collection of sections.
     *
     * @param array $sections Array of section objects.
     * @return int The calculated progress percentage.
     */
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
     * Calculate the progress percentage for a single section.
     *
     * @param section_info|stdClass $section The section object.
     * @return false|float|int The calculated progress percentage or false if completion not enabled.
     */
    private function get_progress_by_section($section) {
        global $USER, $COURSE, $CFG;

        $completion = new \completion_info($COURSE);

        if (!$completion->is_enabled()) {
            return false;
        }

        $modules = $completion->get_activities();
        $count = 0.0;
        $completed = 0.0;
        foreach ($modules as $module) {
            if ($module->section == $section->id) {
                $count++;
                $data = $completion->get_data($module, true, $USER->id);

                if (($data->completionstate == COMPLETION_INCOMPLETE) || ($data->completionstate == COMPLETION_COMPLETE_FAIL)) {
                    require_once($CFG->libdir . '/gradelib.php');
                    $gradinginfo = \grade_get_grades($module->course, 'mod', 'hvp', $module->instance, $USER->id);
                    // Check if activity has a grademax > 0 and the current reached grade is > 0.
                    if (
                        isset($gradinginfo->items[0]) && $gradinginfo->items[0]->grademax != null &&
                        $gradinginfo->items[0]->grademax > 0 && isset($gradinginfo->items[0]->grades[$USER->id]) &&
                        $gradinginfo->items[0]->grades[$USER->id]->grade != null &&
                        $gradinginfo->items[0]->grades[$USER->id]->grade > 0
                    ) {
                        // Set completed status to current grade ratio (e.g., grade = 2.5, maxgrade = 10 => completed += 0.25).
                        $completed += $gradinginfo->items[0]->grades[$USER->id]->grade / $gradinginfo->items[0]->grademax;
                    } else {
                        $completed += 0;
                    }
                } else {
                    $completed += 1;
                }
            }
        }

        if ($count == 0.0) {
            return false;
        }

        return round(($completed / $count) * 100);
    }

    /**
     * Returns the template name for rendering.
     *
     * @param \renderer_base $renderer The renderer instance.
     * @return string The template name.
     */
    public function get_template_name(\renderer_base $renderer): string {
        return 'format_ocmooc/local/content';
    }
}
