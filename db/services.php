<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    format_ocmooc
 * @copyright  2018 ILD, Technische Hochschule Lübeck (https://www.th-luebeck.de/ild)
 * @author     Eugen Ebel (eugen.ebel@th-luebeck.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
        'format_ocmooc_setgrade_subcontent' => [
                'classname' => 'format_ocmooc_external',
                'methodname' => 'setgrade_subcontent',
                'classpath' => 'course/format/ocmooc/externallib.php',
                'description' => 'Set H5P grade for activites with subcontent interactions',
                'type' => 'write',
                'ajax' => true,
        ],
        'format_ocmooc_setgrade' => [
                'classname' => 'format_ocmooc_external',
                'methodname' => 'setgrade',
                'classpath' => 'course/format/ocmooc/externallib.php',
                'description' => 'Set H5P grade',
                'type' => 'write',
                'ajax' => true,
        ],
        'format_ocmooc_get_participant_locations' => [
        'classname'   => 'format_ocmooc_external',
        'methodname'  => 'get_participant_locations',
        'classpath'   => 'course/format/ocmooc/externallib.php',
        'description' => 'Returns participant locations for a course.',
        'type'        => 'read',
        'ajax'        => true,
        ],

];

$services = [
    'ocmooc_setgrade_subcontent' => [
        'functions' => ['format_ocmooc_setgrade_subcontent'],
        'restrictedusers' => 0,
        'enabled' => 1,
    ],
    'ocmooc_setgrade' => [
        'functions' => ['format_ocmooc_setgrade'],
        'restrictedusers' => 0,
        'enabled' => 1,
    ],
];

