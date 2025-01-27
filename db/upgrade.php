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
 * Plugin upgrade steps are defined here.
 *
 * @package     format_ocmooc
 * @category    upgrade
 * @copyright   2022 oncampus GmbH <support@oncampus.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute format_ocmooc upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_format_ocmooc_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // For further information please read {@link https://docs.moodle.org/dev/Upgrade_API}.
    //
    // You will also have to create the db/install.xml file by using the XMLDB Editor.
    // Documentation for the XMLDB Editor can be found at {@link https://docs.moodle.org/dev/XMLDB_editor}.

    if ($oldversion < 2023062800) {

        // Define table format_ocmooc_htmlsite to be created.
        $table = new xmldb_table('format_ocmooc_htmlsite');

        // Adding fields to table format_ocmooc_htmlsite.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('title', XMLDB_TYPE_CHAR, '24', null, XMLDB_NOTNULL, null, null);
        $table->add_field('content', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('created', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('updated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table format_ocmooc_htmlsite.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for format_ocmooc_htmlsite.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table format_ocmooc_social to be created.
        $table = new xmldb_table('format_ocmooc_social');

        // Adding fields to table format_ocmooc_social.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('title', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL, null, null);
        $table->add_field('url', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('type', XMLDB_TYPE_CHAR, '24', null, XMLDB_NOTNULL, null, null);
        $table->add_field('created', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('updated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table format_ocmooc_social.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for format_ocmooc_social.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Ocmooc savepoint reached.
        upgrade_plugin_savepoint(true, 2023062800, 'format', 'ocmooc');
    }

    if ($oldversion < 2023070501) {

        // Define field sortorder to be added to format_ocmooc_social.
        $table = new xmldb_table('format_ocmooc_social');
        $field = new xmldb_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'type');

        // Conditionally launch add field sortorder.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Ocmooc savepoint reached.
        upgrade_plugin_savepoint(true, 2023070501, 'format', 'ocmooc');
    }

    if ($oldversion < 2023071700) {

        // Define table format_ocmooc_parts to be created.
        $table = new xmldb_table('format_ocmooc_parts');

        // Adding fields to table format_ocmooc_parts.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('profilepicture', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('namedisplay', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('email', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('town', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('country', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('badges', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('roles', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('groups', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('lastaccess', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('created', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('edited', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table format_ocmooc_parts.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for format_ocmooc_parts.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Ocmooc savepoint reached.
        upgrade_plugin_savepoint(true, 2023071700, 'format', 'ocmooc');
    }

    if ($oldversion < 2024052700) {

        // Define field id to be added to format_ocmooc_hvp.
        $table = new xmldb_table('format_ocmooc_hvp');

        // Adding fields to table format_ocmooc_htmlsite.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('user_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('content_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('subcontent_id', XMLDB_TYPE_CHAR, '36', null, XMLDB_NOTNULL, null, null);
        $table->add_field('score', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('maxscore', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table format_ocmooc_parts.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch add field id.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Ocmooc savepoint reached.
        upgrade_plugin_savepoint(true, 2024052700, 'format', 'ocmooc');
    }

    if ($oldversion < 2025012700) {

        // Define table format_ocmooc_locations to be created.
        $table = new xmldb_table('format_ocmooc_locations');

        // Adding fields to table format_ocmooc_locations.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, 'Primary key');
        $table->add_field('location_name_de', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null, 'German locationname');
        $table->add_field('location_name_en', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null, 'English locationname');
        $table->add_field('country', XMLDB_TYPE_CHAR, '2', null, null, null, null);
        $table->add_field('latitude', XMLDB_TYPE_NUMBER, '10, 7', null, XMLDB_NOTNULL, null, null, 'Latitude coordinate');
        $table->add_field('longitude', XMLDB_TYPE_NUMBER, '10, 7', null, XMLDB_NOTNULL, null, null, 'Longitude coordinate');
        $table->add_field('last_checked', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'Timestamp of last API check');

        // Adding keys to table format_ocmooc_locations.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Adding indexes to improve query performance.
        $table->add_index('idx_location_name_de_country', XMLDB_INDEX_NOTUNIQUE, ['location_name_de', 'country']);
        $table->add_index('idx_location_name_en_country', XMLDB_INDEX_NOTUNIQUE, ['location_name_en', 'country']);
        $table->add_index('idx_latitude_longitude', XMLDB_INDEX_NOTUNIQUE, ['latitude', 'longitude']);

        // Conditionally launch create table for format_ocmooc_locations.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table format_ocmooc_aliases to be created.
        $table = new xmldb_table('format_ocmooc_aliases');

        // Adding fields to table format_ocmooc_aliases.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, 'Primary key');
        $table->add_field(
            'location_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'Reference to format_ocmooc_locations'
        );
        $table->add_field('alias', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'Alias for the location');

        // Adding keys to table format_ocmooc_aliases.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fk_location_reference', XMLDB_KEY_FOREIGN, ['location_id'], 'format_ocmooc_locations', ['id']);

        // Adding indexes to improve query performance.
        $table->add_index('idx_alias', XMLDB_INDEX_NOTUNIQUE, ['alias']); // Nur auf alias, nicht auf location_id!
        $table->add_index('idx_location_id_alias', XMLDB_INDEX_NOTUNIQUE, ['location_id', 'alias']);

        // Conditionally launch create table for format_ocmooc_aliases.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table format_ocmooc_invalidlocs to be created.
        $table = new xmldb_table('format_ocmooc_invalidlocs');

        // Adding fields to table format_ocmooc_invalidlocs.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, 'Primary key');
        $table->add_field('city', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null, 'Invalid city name');
        $table->add_field('country', XMLDB_TYPE_CHAR, '2', null, null, null, null, 'Optional country code (ISO 3166-1 Alpha-2)');

        // Adding keys to table format_ocmooc_invalidlocs.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for format_ocmooc_invalidlocs.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }


        // Ocmooc savepoint reached.
        upgrade_plugin_savepoint(true, 2025012700, 'format', 'ocmooc');
    }
    return true;
}
