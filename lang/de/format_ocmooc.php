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
$string['addsections']     = 'Abschnitt hinzufügen';
$string['currentsection']  = 'Dieser Abschnitt';
$string['deletesection']   = 'Abschnitt Löschen';
$string['editsection']     = 'Abschnitt bearbeiten';
$string['editsectionname'] = 'Abschnittsnamen bearbeiten';
$string['hidefromothers']  = 'Abschnitt verstecken';
$string['Manage']          = 'verwalten';
$string['newsectionname']  = 'Neuer Abschnittsname {$a}';
$string['sectionname']     = 'Abschnitt';
$string['settings']        = 'Einstellungen';
$string['showfromothers']  = 'Abschnitt anzeigen';

/** forms **/
/* htmlpageform.php */
$string['title']   = 'Title';
$string['content'] = 'Inhalt';
/* participantsform.php */
$string['anonymized']  = 'Anonymer Nutzername';
$string['namedisplay'] = 'Anzeigeart des Nutzernamens';
/* socialform.php */
$string['url']      = 'URL:';
$string['youtube']  = 'Youtube';
$string['twitter']  = 'X';
$string['facebook'] = 'Facebook';
$string['mastodon'] = 'Mastodon';
$string['linkedin'] = 'LinkedIn';
$string['xing']     = 'Xing';
$string['other']    = 'Andere';
$string['type']     = 'Type:';

/**  classes/output **/
/* content.php */
$string['addchapter'] = 'Kapitel hinzufügen';
$string['addlection'] = 'Abschnitt hinzufügen';

/** classes/output/state **/
/* addsection.php */

/**  classes **/
/* badges.php */
$string['badges']            = 'Abzeichen';
$string['no_badges_awarded'] = 'Bisher wurden keine Abzeichen verdient';
/* base.php */
$string['default_title'] = 'Standart Title';
$string['editor']        = 'Editor';
$string['success']       = 'Erfolgreich';
$string['failed']        = 'Fehlgeschlagen';
/* htmlpage.php */
$string['htmlpage'] = 'HTML-Seite';
$string['edit']     = 'Bearbeiten';
$string['overview'] = 'Übersicht';
$string['create']   = 'Erstellen';
$string['title']    = 'Title';
$string['created']  = 'Erstellt';
$string['updated']  = 'Bearbeited';
$string['actions']  = 'Aktionen';
$string['view']     = 'Ansicht';
$string['delete']   = 'Löschen';
/* moocnav.php */
$string['newsforum']       = 'Neuigkeiten';
$string['discussionforum'] = 'Diskussionsforum';
$string['social']          = 'Soziale Medien';
/* sections.php */
$string['deletesection'] = 'Sektion Löschen';
/* social.php */
$string['action']     = 'Aktion';
$string['sesskey']    = 'Sitzungsschlüssel';
$string['courseid']   = 'Kurs ID';
$string['sectionnum'] = 'Sektionsnummer';

/**  edit **/
/* htmlpage.php */
$string['error:nocapability'] = 'Do hast nicht die benötigten berechtigungen';
$string['participants']       = 'Teilnehmer/in';
/* participants.php */
/* social.php */

/**  templates **/
/* bagdes.mustache */
$string['profile_badges'] = 'Profil Auszeichnungen';
$string['mybackpack']     = 'Mein Rucksack';
$string['all_badges']     = 'Alle Auszeichnugnen';
/* chapterheader.mustache */
$string['chapter'] = 'Kapitel';
/* sectionnav.mustache */
$string['lection'] = 'Lektion';
/* sectionquicknav.mustache */
$string['footernav:prev'] = 'Vorherige Lektion';
$string['footernav:top']  = 'Nach oben';
$string['footernav:next'] = 'Nächste Lektion';

/**  lib.php **/
$string['edit:participants'] = 'Teilnehmer bearbeiten';
$string['edit:social']       = 'Soziale Links bearbeiten';
$string['edit:htmlpage']     = 'HTML-Seite bearbeiten';

$string['rootsection']     = 'Kurs Administration';
$string['numberedchapter'] = 'Kapitel {a}';
$string['numberedlection'] = 'Lektion {a}';

/* badges.php */
$string['badge_overview_description']  = 'Mit Kursbadges bzw. digitale Lernabzeichen wird der Fortschritt im Kurs belohnt und sichtbar gemacht.';
$string['no_badges_available']  = 'Es sind keine Auszeichnungen verfügbar';
$string['my_badges']  = 'Meine Auszeichnungen';

$string['participants_default'] = 'Teilnehmerliste Default';
$string['participants_guest'] = 'Teilnehmerliste Gast';
$string['completionwarning'] = 'Bearbeitet';
$string['completionwarning_changeinbulk'] = 'Mehrere Elemente bearbeiten';

/* forumlist.php */
$string['forumlist'] = 'Forenliste';
$string['no_forumlist_available'] = 'Es sind keine Foren im Kurs verfügbar';
$string['name_forumlist'] = 'Liste aller Foren';