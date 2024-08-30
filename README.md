# OC MOOC #

TODO Describe the plugin shortly here.

TODO Provide more detailed description here.

## Installing via uploaded ZIP file ##

1. Log in to your Moodle site as an admin and go to _Site administration >
   Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code. You should only be prompted to add
   extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.

## Installing manually ##

The plugin can be also installed by putting the contents of this directory to

    {your/moodle/dirroot}/course/format/ocmooc

Afterwards, log in to your Moodle site as an admin and go to _Site administration >
Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

## Update Logs ##

### Hotfix `2024083000` ###
- Verschieben von Aktivitäten nach oben.
- Lektionen in der Seitennavigation können geöffnet werden.
- Hinzufügen von Aktivitäten in System Administration und in Kapitelsektion.
- Alles einklappen/ausklappen im Bearbeitungsmodus.
- Verschieben über die drei Punkte temporär ausgeblendet.
- Behebung von courseindex sectionTogglers Errors in der Konsole.

### Version `2024052700` ###

- Bei erneutem Kurseintritt sowie beim Neuladen der Seite in der zuletzt bearbeiteten Lektion landen.
- Lernfortschrittsbalken bei H5P-Videos/Präsentationen angepasst. Nun wird jede Interaktion in einem Video einzeln getrackt und gespeichert.
- Der Knopf, um in den Bearbeitungsmodus zu gelangen, ist erst nach vollständigem Laden der Seite wieder benutzbar.
- Behebung einiger Fehler bei der Selbsteinschreibung ins MOOC-Format.
- Hinzufügen eines Knopfes zum Beitreten des MOOC innerhalb des Kurses.
- Aktivitäten, Lektionen und Kapitel können im Bearbeitungsmodus verschoben werden.

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
