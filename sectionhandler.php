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
 * Section handler for the OCMOOC course format
 *
 * This file handles section management operations like adding chapters,
 * adding lections, deleting sections, and reordering sections.
 *
 * @package    format_ocmooc
 * @copyright  2025 oncampus GmbH <info@oncampus.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

$action   = required_param('action', PARAM_TEXT);
$sesskey  = required_param('sesskey', PARAM_ALPHANUM);
$courseid = required_param('courseid', PARAM_INT);

$context = context_course::instance($courseid);
$course  = get_course($courseid);

require_login($courseid);
require_sesskey();

$redirecturl = new moodle_url('/course/view.php', ['id' => $courseid]);

if (!has_capability('format/ocmooc:edit', $context) || $course->format != 'ocmooc') {
    redirect($redirecturl);
}
$sectionmanager = new format_ocmooc\sections($courseid);

switch ($action) {
    case 'addchapter':
        $chapterno = $sectionmanager->add_chapter();
        $redirecturl->param('chapter', $chapterno);
        break;
    case 'addlection':
        $chaptersectionnumber = required_param('chaptersectionnumber', PARAM_INT);
        [$chapterno, $lectiono] = $sectionmanager->add_lection($chaptersectionnumber);
        $redirecturl->param('chapter', $chapterno);
        $redirecturl->param('lection', $lectiono);
        break;
    case 'confirm-delete':
        $sectionnum = required_param('sectionnum', PARAM_INT);
        $sectionmanager->deleteconfirmation($sectionnum, $redirecturl);
        break;
    case 'delete':
        $sectionnum = required_param('sectionnum', PARAM_INT);
        $modinfo    = get_fast_modinfo($course);
        $section    = $modinfo->get_section_info($sectionnum, MUST_EXIST);
        $redirecturl->param('chapter', $sectionmanager->get_chapter_no_from_number($section->parent));
        $redirecturl->param('lection', $sectionmanager->get_lection_no_from_number($section->parent, $sectionnum - 1));
        $sectionmanager->delete_section_with_children($section);
        $neworder = [];
        $sectionmanager->reorder_sections($neworder, 0);
        break;
    case 'reorder':
        $neworder = [];
        $sectionmanager->reorder_sections($neworder, 0);
        break;
}

redirect($redirecturl);
