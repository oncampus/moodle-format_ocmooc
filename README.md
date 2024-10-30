# MOOC-Format für Moodle ##

Das MOOC-Format ist ein speziell entwickeltes Kursformat für Moodle, das umfangreiche Funktionen zur Unterstützung von Massive Open Online Courses (MOOCs) bietet. Es ermöglicht eine modulare Kursstruktur mit Kapiteln, Lektionen und interaktiven Aktivitäten, sowie Funktionen wie Fortschrittsverfolgung, Abzeichen, Zertifikate, Teilnehmerlisten und Diskussionsforen. Diese Features machen es besonders geeignet für große offene Online-Kurse und individuelle Lernpfade.

## Features ##

- **Modulare Struktur**: Organisiert Kurse in Kapitel und Lektionen.
- **Fortschrittsverfolgung**: Zeigt den Fortschritt der Lernenden im Kurs an.
- **Interaktive H5P-Inhalte**: Integriert H5P für interaktive Inhalte direkt im Kurs.
- **Zertifikate und Abzeichen**: Automatische Verteilung von Zertifikaten und Abzeichen nach Kursabschluss.
- **Diskussionsforen und Teilnehmerlisten**: Fördert Austausch und Kollaboration im Kurs.

## Anforderungen ##

- **Moodle Version**: 4.1 oder höher
- **H5P Plugin**: Version 1.26.1 oder höher ([H5P Plugin Version 1.26.1](https://github.com/h5p/moodle-mod_hvp/tree/1.26.1))

## Installation ##

1. **MOOC-Format Plugin herunterladen**: Lade das Plugin aus unserem GitLab-Repository herunter:
   - [MOOC-Format GitLab Repository](https://gitlab.oncampus-system.de/moodle/plugins/mod/format_ocmooc/-/tree/MOODLE_401_STABLE?ref_type=heads)
2. **Plugin installieren**:
   - Entpacke das Plugin und kopiere den gesamten Ordner in das Moodle-Verzeichnis `moodle/course/format`.
3. **H5P Plugin installieren**:
   - Installiere das H5P-Plugin (falls nicht vorhanden), um interaktive Inhalte einzubetten.
4. **Moodle aktualisieren**:
   - Melde dich als Admin bei Moodle an und führe ein Update durch, um das Plugin zu aktivieren.

## H5P-Integration ##

Das MOOC-Format-Plugin ist auf H5P-Inhalte ausgelegt. Eine detaillierte Anleitung zur H5P-Einbettung für das MOOC-Format findest du [hier](https://oncampus-gmbh.atlassian.net/wiki/spaces/IT/pages/192643114/HVP+Einbettung).


## Update Logs und Kundeninformationen ##

Eine Liste der Kunden, die derzeit das MOOC-Format nutzen, und eine Übersicht aller Updates und Funktionen findest du unter:
- **Update Logs**: [Update Log für das MOOC-Format](https://oncampus-gmbh.atlassian.net/wiki/spaces/IT/pages/434405417/Update+Log)
- **Kundenliste**: [Kunden im MOOC-Format](https://oncampus-gmbh.atlassian.net/wiki/spaces/IT/pages/edit-v2/695336961?draftShareId=8c2aeeee-f85b-4a54-9b35-070416d8e38e)


## Installing manually ##

The plugin can be also installed by putting the contents of this directory to

    {your/moodle/dirroot}/course/format/ocmooc

Afterwards, log in to your Moodle site as an admin and go to _Site administration >
Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

## License ##

2022 oncampus GmbH <support@oncampus.de>

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.
