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
 * Plugin strings are defined here.
 *
 * @package     format_ocmooc
 * @category    string
 * @copyright   2022 oncampus GmbH <support@oncampus.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname']      = 'OC MOOC';
$string['addsections']     = 'Add section';
$string['currentsection']  = 'This section';
$string['deletesection']   = 'Delete section';
$string['editsection']     = 'Edit section';
$string['editsectionname'] = 'Edit section name';
$string['hidefromothers']  = 'Hide section';
$string['Manage']          = 'manage';
$string['newsectionname']  = 'New name for section {$a}';
$string['sectionname']     = 'Section';
$string['settings']        = 'Settings';
$string['showfromothers']  = 'Show section';

/** course options **/
$string['certpercentage']  = 'Certificate available at (%) ';
$string['certpercentage_help']  = 'Amount of correct answers required to download a certificate of participation (%) ';

/** forms **/
/* htmlpageform.php */
$string['title']   = 'Title';
$string['content'] = 'content';
/* participantsform.php */
$string['anonymized']  = 'Anonymized user name';
$string['shortened_surname'] = 'First name and shortened surname';
$string['namedisplay'] = 'Display method for the username';

/* socialform.php */
$string['url']      = 'URL:';
$string['youtube']  = 'Youtube';
$string['twitter']  = 'X';
$string['facebook'] = 'Facebook';
$string['mastodon'] = 'Mastodon';
$string['linkedin'] = 'LinkedIn';
$string['xing']     = 'Xing';
$string['other']    = 'Other';
$string['type']     = 'Type:';

/**  classes/output **/
/* content.php */
$string['addchapter'] = 'Add an chapter';
$string['addlection'] = 'Add an lection';

/** classes/output/state **/
/* addsection.php */

/**  classes **/
/* badges.php */
$string['badges']            = 'Badges';
$string['badges_nav']        = 'Badges';
$string['no_badges_awarded'] = 'No badges have been earned yet';
// certifcates
$string['certificate_and_badges'] = 'Certificate & badges';
$string['cert_descr'] = 'Congratulations! You have answered at least {$a}% of all video questions correctly. You can now download your certificate for course completion.';
$string['cert_descr_general']         = 'Congratulations! You have answered enough questions correctly and can download your certificate here.';
$string['certificate'] = 'Course certificate';
$string['cert_need']                = 'In this course you can download the confirmation of participation if you have passed at least {$a->min_per} percent of the required online self-tests. You are currently at {$a->current} percent.';
$string['only_for_trainers'] = 'Only admins and trainers can see this';
$string['cert_addtext']          = 'Lorem ipsum';
$string['cert_nocert'] = 'It is not possible to obtain a certificate of attendance for this course.';
$string['cert_available'] = 'It is possible to obtain a certificate of attendance for this course.';
/* base.php */
$string['default_title'] = 'Default Title';
$string['editor']        = 'Editor';
$string['success']       = 'Success';
$string['failed']        = 'Failed';
/* htmlpage.php */
$string['htmlpage'] = 'HTML page';
$string['edit']     = 'Edit';
$string['overview'] = 'Overview';
$string['create']   = 'Create';
$string['title']    = 'Title';
$string['created']  = 'Created';
$string['updated']  = 'Updated';
$string['actions']  = 'Actions';
$string['view']     = 'View';
$string['delete']   = 'Delete';
/* moocnav.php */
$string['coursetab']       = 'Course content';
$string['newsforum']       = 'News';
$string['discussionforum'] = 'Discussionforum';
$string['social']          = 'Social';
/* sections.php */
$string['deletesection'] = 'Delete section';
/* social.php */
$string['overview']   = 'Overview';
$string['action']     = 'Action';
$string['sesskey']    = 'Session key';
$string['courseid']   = 'Course ID';
$string['sectionnum'] = 'Section Number';
/* participants.php*/
$string['worldmap'] = 'Worldmap';
$string['enroleduserscount'] = 'Users enrolled in the course: {$a}';

/**  edit **/
/* htmlpage.php */
$string['error:nocapability'] = 'You do not have the capability to view this page';
$string['participant']       = 'Participant';
$string['participants']       = 'Participants';
$string['participants_unenrol'] = 'Unenrol yourself from course';

/* participants.php */
$string['htmlpage'] = 'HTML page';
/* social.php */

/**  templates **/
/* bagdes.mustache */
$string['profile_badges'] = 'Profile badges';
$string['mybackpack']     = 'My backpack';
$string['all_badges']     = 'All Badges';

/* chapterheader.mustache */
$string['chapter'] = 'Chapter';
/* sectionnav.mustache */
$string['lection'] = 'Lection';
/* sectionquicknav.mustache */
$string['footernav:top']  = 'Top';
$string['footernav:next'] = 'Next lession';
$string['footernav:prev'] = 'Previous lession';
/* enrolbutton.mustache */
$string['enrolbutton'] = 'Enroll in the course';

/**  lib.php **/
$string['edit:participants'] = 'Edit particitpants';
$string['edit:social']       = 'Edit socials';
$string['edit:htmlpage']     = 'Edit HTML page';

$string['rootsection']     = 'Course administration';
$string['numberedchapter'] = 'Chapter {a}';
$string['numberedlection'] = 'Lection {a}';

/* badges.php */
$string['badge_overview_description']  = 'Course badges reward and show progress in the course.';
$string['no_badges_available']  = 'Their are no badges available.';
$string['my_badges']  = 'My Badges';

$string['participants_and_map_global'] = 'Participantslist and worldmap global';
$string['participants_and_map_global_info'] = 'These settings allow you to globally configure whether the user table and world map are displayed in courses.';
$string['display_list'] = 'Display list';
$string['display_list_desc'] = 'Enable this option to show the list of participants in courses.';
$string['display_worldmap'] = 'Display worldmap';
$string['display_worldmap_desc'] = 'Enable this option to show the worldmap in courses.';

$string['participants_default'] = 'Participants Default';
$string['participants_guest'] = 'Participants Guest';
$string['completionwarning'] = 'Edeting';
$string['completionwarning_changeinbulk'] = 'Bulk edit activities';

/*forumlist.php*/
$string['forumlist'] = 'Forum list';
$string['no_forumlist_available'] = 'No Forums are available in this course.';
$string['name_forumlist'] = 'List of all forums';
