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
 * Plugin administration pages are defined here.
 *
 * @package     format_ocmooc
 * @category    admin
 * @copyright   2022 oncampus GmbH <support@oncampus.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('format_ocmooc_settings', new lang_string('pluginname', 'format_ocmooc'));

    // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
    if ($ADMIN->fulltree) {
        $options = [
                0 => get_string('fullname'),
                1 => get_string('username'),
                2 => get_string('anonymized', 'format_ocmooc'),
                3 => get_string('shortened_surname', 'format_ocmooc'),
        ];

        // GLOBAL SETTINGS: PARTICIPANTS & WORLDMAP.
        $settings->add(
            new admin_setting_heading(
                'global_participants_header',
                get_string('participants_and_map_global', 'format_ocmooc'),
                get_string('participants_and_map_global_info', 'format_ocmooc'),
            )
        );

        $settings->add(
            new admin_setting_configcheckbox(
                'format_ocmooc/displaylist',
                get_string('display_list', 'format_ocmooc'),
                get_string('display_list_desc', 'format_ocmooc'),
                true
            )
        );

        $settings->add(
            new admin_setting_configcheckbox(
                'format_ocmooc/displayworldmap',
                get_string('display_worldmap', 'format_ocmooc'),
                get_string('display_worldmap_desc', 'format_ocmooc'),
                false
            )
        );

        // DEFAULT SETTINGS: PARTICIPANTS.
        $settings->add(
            new admin_setting_heading(
                'default_participants_header',
                get_string('participants_default', 'format_ocmooc'),
                ''
            )
        );

        $settings->add(
            new admin_setting_configcheckbox(
                'format_ocmooc/profilepicture',
                get_string('userpic', ''),
                '',
                true
            )
        );

        $settings->add(
            new admin_setting_configselect(
                'format_ocmooc/namedisplay',
                get_string('namedisplay', 'format_ocmooc'),
                '',
                0,
                $options
            )
        );

        $settings->add(
            new admin_setting_configcheckbox(
                'format_ocmooc/email',
                get_string('email', ''),
                '',
                true
            )
        );

        $settings->add(
            new admin_setting_configcheckbox(
                'format_ocmooc/town',
                get_string('city', ''),
                '',
                true
            )
        );

        $settings->add(
            new admin_setting_configcheckbox(
                'format_ocmooc/country',
                get_string('country', ''),
                '',
                true
            )
        );

        $settings->add(
            new admin_setting_configcheckbox(
                'format_ocmooc/badges',
                get_string('badges', 'format_ocmooc'),
                '',
                true
            )
        );

        $settings->add(
            new admin_setting_configcheckbox(
                'format_ocmooc/roles',
                get_string('roles', ''),
                '',
                false
            )
        );

        $settings->add(
            new admin_setting_configcheckbox(
                'format_ocmooc/groups',
                get_string('groups', ''),
                '',
                false
            )
        );

        $settings->add(
            new admin_setting_configcheckbox(
                'format_ocmooc/lastaccess',
                get_string('lastaccess', ''),
                '',
                false
            )
        );

        // GUEST SETTINGS: PARTICIPANTS.
        $settings->add(
            new admin_setting_heading(
                'guest_participants_header',
                get_string('participants_guest', 'format_ocmooc'),
                ''
            )
        );

        $settings->add(
            new admin_setting_configcheckbox(
                'format_ocmooc/participants_guest',
                get_string('participants', 'format_ocmooc'),
                '',
                false
            )
        );

        $settings->add(
            new admin_setting_configcheckbox(
                'format_ocmooc/profilepicture_guest',
                get_string('userpic', ''),
                '',
                false
            )
        );

        $settings->add(
            new admin_setting_configselect(
                'format_ocmooc/namedisplay_guest',
                get_string('namedisplay', 'format_ocmooc'),
                '',
                2,
                $options
            )
        );

        $settings->add(
            new admin_setting_configcheckbox(
                'format_ocmooc/email_guest',
                get_string('email', ''),
                '',
                false
            )
        );

        $settings->add(
            new admin_setting_configcheckbox(
                'format_ocmooc/town_guest',
                get_string('city', ''),
                '',
                false
            )
        );

        $settings->add(
            new admin_setting_configcheckbox(
                'format_ocmooc/country_guest',
                get_string('country', ''),
                '',
                false
            )
        );

        $settings->add(
            new admin_setting_configcheckbox(
                'format_ocmooc/badges_guest',
                get_string('badges', 'format_ocmooc'),
                '',
                false
            )
        );

        $settings->add(
            new admin_setting_configcheckbox(
                'format_ocmooc/roles_guest',
                get_string('roles', ''),
                '',
                false
            )
        );

        $settings->add(
            new admin_setting_configcheckbox(
                'format_ocmooc/groups_guest',
                get_string('groups', ''),
                '',
                false
            )
        );

        $settings->add(
            new admin_setting_configcheckbox(
                'format_ocmooc/lastaccess_guest',
                get_string('lastaccess', ''),
                '',
                false
            )
        );
    }
}
