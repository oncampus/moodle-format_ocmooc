<?php

namespace format_ocmooc;

use core_group\output\group_details;
use format_ocmooc\forms\participantsform;
use format_ocmooc\forms\searchform;

require_once($CFG->dirroot . '/enrol/locallib.php');

require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->dirroot . '/blocks/online_users/lib.php');

class participants extends base {

    private $data;

    private $counter = 1;

    private $datestring;

    private $searchform;

    public function __construct($courseid, $url) {
        parent::__construct($courseid, $url);
        $this->mform     = new participantsform($this->url, ['courseid' => $this->courseid]);
        $this->setdatadb = 'format_ocmooc_parts';

        $this->datestring        = new \stdClass();
        $this->datestring->year  = get_string('year');
        $this->datestring->years = get_string('years');
        $this->datestring->day   = get_string('day');
        $this->datestring->days  = get_string('days');
        $this->datestring->hour  = get_string('hour');
        $this->datestring->hours = get_string('hours');
        $this->datestring->min   = get_string('min');
        $this->datestring->mins  = get_string('mins');
        $this->datestring->sec   = get_string('sec');
        $this->datestring->secs  = get_string('secs');

        $this->searchform = new searchform($this->url);

        $this->title = get_string('participants', 'format_ocmooc');
    }

    public function check_guest_access() {
        $coursecontext = \context_course::instance($this->courseid);
        if (is_guest($coursecontext)) {
            $allowed = get_config('format_ocmooc', 'participants_guest');
            if (!$allowed) {
                redirect(new \moodle_url('/course/view.php', ['id' => $this->courseid]));
            }
        }
    }

    protected function render_view_custom() {
        global $PAGE, $COURSE, $DB, $OUTPUT, $CFG, $USER;

        $page = optional_param('page', 0, PARAM_INT);
        $perpage = optional_param('perpage', 10, PARAM_INT);

        $data = $this->get_data();

        if ($data->town && $data->country) {
            #$map = get_html_osmmap();
            #echo \html_writer::span($map, 'mb-2');
        }

        $manager = new \course_enrolment_manager($PAGE, $COURSE);

        [$header, $titles, $nosort] = $this->prepare_table_header();

        $table = new \flexible_table('user-index-participants-' . $this->courseid);
        $table->define_columns($header);
        $table->define_headers($titles);
        $table->define_baseurl($this->url);

        $table->set_attribute('cellspacing', '0');
        $table->set_attribute('id', 'participants');
        $table->set_attribute('class', 'generaltable generalbox ');

        foreach ($nosort as $col) {
            $table->no_sorting($col);
        }

        $table->sortable(true);

        $table->setup();

        $instances = $manager->get_enrolment_instances();
        $ids       = [];
        foreach ($instances as $instance) {
            $ids[] = $instance->id;
        }

        unset($instance);
        unset($instances);

        [$insql, $params] = $DB->get_in_or_equal($ids);

        $sql = "SELECT u.* FROM {user} u
                INNER JOIN {user_enrolments} ue
                    ON u.id = ue.userid
                WHERE enrolid $insql";

        [$sql, $paramssearch] = $this->handle_search_data($sql);

        if ($paramssearch) {
            $params = array_merge($params, $paramssearch);
        }

        $sort = $table->get_sql_sort();
        if ($sort) {
            $sql .= " ORDER BY $sort";
        }

        $users = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

        foreach ($users as $user) {
            $user = $this->prepare_user_entry($user);
            $table->add_data($user);
            unset($user);
        }

        // There was a problem with count_records_sql. The count was incorrect.
        $total = $DB->get_records_sql($sql, $params);
        $total = count($total);

        $this->searchform->display();
        echo $OUTPUT->paging_bar($total, $page, $perpage, $this->url);

        $table->finish_html();

        echo $OUTPUT->paging_bar($total, $page, $perpage, $this->url);

        // insert button to unenrol yourself from course with autoenroll
        if ($enrol = $DB->get_record('enrol', array('courseid' => $this->courseid, 'enrol' => 'autoenrol', 'status' => 0))) {
            if ($user_enrolment = $DB->get_record('user_enrolments', array('enrolid' => $enrol->id, 'userid' => $USER->id))) {
                $unenrolurl = new \moodle_url("$CFG->wwwroot/enrol/autoenrol/unenrolself.php?enrolid=$enrol->id");
                echo $OUTPUT->single_button($unenrolurl, get_string('participants_unenrol', 'format_ocmooc'), 'get');
            }
        }

        unset($table);
    }

    private function prepare_table_header() {
        $data = $this->get_data();

        $header      = [];
        $titles      = [];
        $notsortable = [];

        if ($data->profilepicture) {
            $notsortable[] = 'profilepicture';
            $header[]      = 'profilepicture';
            $titles[]      = get_string('userpic',);
        }

        switch ($data->namedisplay) {
            case 1:
                $header[] = 'username';
                $titles[] = get_string('username');
                break;
            case 2:
                $notsortable[] = 'name';
                $header[]      = 'name';
                $titles[]      = get_string('name');
                break;
            default:
                $header[] = 'fullname';
                $titles[] = get_string('fullname');
                break;
        }

        if ($data->email) {
            $header[] = 'email';
            $titles[] = get_string('email');
        }

        if ($data->town) {
            $header[] = 'town';
            $titles[] = get_string('city');
        }

        if ($data->country) {
            $header[] = 'country';
            $titles[] = get_string('country');
        }

        if ($data->roles) {
            $notsortable[] = 'roles';
            $header[]      = 'roles';
            $titles[]      = get_string('roles');
        }

        if ($data->groups) {
            $notsortable[] = 'groups';
            $header[]      = 'groups';
            $titles[]      = get_string('groups');
        }

        if ($data->badges) {
            $notsortable[] = 'badges';
            $header[]      = 'badges';
            $titles[]      = get_string('badges');
        }

        if ($data->lastaccess) {
            $header[] = 'lastaccess';
            $titles[] = get_string('lastaccess');
        }

        return [$header, $titles, $notsortable];
    }

    private function prepare_user_entry($user) {
        global $OUTPUT, $DB;

        $data = $this->get_data();

        $userdata = [];

        if ($data->profilepicture) {
            $userdata[] = $OUTPUT->user_picture($user, ['size' => 35, 'courseid' => $this->courseid]);
        }

        switch ($data->namedisplay) {
            case 1:
                $userdata[] = $user->username;
                break;
            case 2:
                $userdata[] = "User $this->counter";
                $this->counter++;
                break;
            default:
                $userdata[] = fullname($user);
        }

        if ($data->email) {
            $userdata[] = $user->email;
        }

        if ($data->town) {
            $userdata[] = $user->city;
        }

        if ($data->country) {
            $userdata[] = $user->country;
        }

        if ($data->roles) {
            $roles     = get_user_roles(\context_course::instance($this->courseid), $user->id, false);
            $rolesdata = [];
            foreach ($roles as $role) {
                $rolesdata[] = \html_writer::tag('span', $role->fullname ?? $role->shortname, ['class' => '']);
            }
            $seperator  = \html_writer::tag('span', ', ', ['class' => '']);
            $userdata[] = implode($seperator, $rolesdata);
        }

        if ($data->groups) {
            $groupsarr     = \groups_get_user_groups($this->courseid, $user->id)[0];
            $groupsdata = [];
            foreach ($groupsarr as $group) {
                if ($group) {
                    $group=  \groups_get_group($group);
                    $groupsdata[] = \html_writer::tag('span', $group->name, ['class' => '']);
                }
            }

            $seperator  = \html_writer::tag('span', ', ', ['class' => '']);
            $userdata[] = implode($seperator, $groupsdata);
        }

        if ($data->badges) {
            $badges = badges_get_user_badges($user->id, $this->courseid);
            $images = [];
            foreach ($badges as $badge) {
                $imageurl = \moodle_url::make_pluginfile_url(\context_course::instance($this->courseid)->id, 'badges', 'badgeimage',
                        $badge->id, '/', 'f1', false);
                $images[] = \html_writer::img($imageurl, $badge->name, ['style' => 'width: 30px; heigth: 30px;']);
            }

            $seperator  = \html_writer::tag('span', ', ', ['class' => '']);
            $userdata[] = implode($seperator, $images);
        }

        if ($data->lastaccess) {
            $userdata[] = format_time(time() - $user->lastaccess, $this->datestring);
        }

        return $userdata;
    }

    private function get_data() {
        global $DB;

        if (!isset($this->data)) {
            $context = \context_course::instance($this->courseid);
            if (is_guest(($context))) {
                $data = new \stdClass();

                $data->profilepicture = get_config('format_ocmooc', 'profilepicture_guest');
                $data->namedisplay = get_config('format_ocmooc', 'namedisplay_guest');
                $data->email = get_config('format_ocmooc', 'email_guest');
                $data->town = get_config('format_ocmooc', 'town_guest');
                $data->country = get_config('format_ocmooc', 'country_guest');
                $data->badges = get_config('format_ocmooc', 'badges_guest');
                $data->roles = get_config('format_ocmooc', 'roles_guest');
                $data->groups = get_config('format_ocmooc', 'groups_guest');
                $data->lastaccess = get_config('format_ocmooc', 'lastaccess_guest');
            } else {
                $data = $DB->get_record($this->setdatadb, ['courseid' => $this->courseid]);
                if (!$data) {
                    $data = new \stdClass();
                    $data->courseid = $this->courseid;
                    $data->profilepicture = get_config('format_ocmooc', 'profilepicture');
                    $data->namedisplay = get_config('format_ocmooc', 'namedisplay');
                    $data->email = get_config('format_ocmooc', 'town');
                    $data->town = get_config('format_ocmooc', 'town');
                    $data->country = get_config('format_ocmooc', 'country');
                    $data->badges = get_config('format_ocmooc', 'badges');
                    $data->roles = get_config('format_ocmooc', 'roles');
                    $data->groups = get_config('format_ocmooc', 'groups');
                    $data->lastaccess = get_config('format_ocmooc', 'lastaccess');
                    $data->created = time();
                    $data->edited = time();

                    $DB->insert_record('format_ocmooc_parts', $data);
                }
            }
            $this->data = $data;
        }

        return $this->data;
    }

    private function handle_search_data($sql) {
        if ($this->searchform->is_cancelled()) {
            redirect($this->url);
        } else if ($fromform = $this->searchform->get_data()) {
            $data       = $this->get_data();
            $searchtext = "%$fromform->searchtext%";

            $sql .= " AND ";

            $sqlwhere = [];

            $params = [];

            switch ($data->displayname) {
                case 1:
                    $sqlwhere[] = 'u.username LIKE ?';
                    $params[]   = $searchtext;
                    break;
                default:
                    $sqlwhere[] = 'u.firstname LIKE ? OR u.lastname LIKE ?';
                    $params[]   = $searchtext;
                    $params[]   = $searchtext;
                    break;
            }

            if ($data->email) {
                $sqlwhere[] = 'u.email LIKE ?';
                $params[]   = $searchtext;
            }

            if ($data->town) {
                $sqlwhere[] = 'u.city LIKE ?';
                $params[]   = $searchtext;
            }

            if ($data->country) {
                $sqlwhere[] = 'u.city LIKE ?';
                $params[]   = $searchtext;
            }

            $sql .= '(' . implode(' OR ', $sqlwhere) . ')';
        }

        return [$sql, $params ?? false];
    }

    protected function render_editor_custom() {

    }

    public function render_overview() {

    }

    protected function handle_data($data) {
        global $DB;

        $id = $DB->get_field('format_ocmooc_parts', 'id', ['courseid' => $this->courseid]);

        $record = new \stdClass();
        if ($id) {
            $record->id = $id;
        }
        $record->courseid       = $this->courseid;
        $record->profilepicture = $data->profilepicture ?? false;
        $record->namedisplay    = $data->namedisplay;
        $record->email          = $data->email ?? false;
        $record->town           = $data->town ?? false;
        $record->country        = $data->country ?? false;
        $record->badges         = $data->badges ?? false;
        $record->roles          = $data->roles ?? false;
        $record->groups         = $data->groups ?? false;
        $record->lastaccess     = $data->lastaccess ?? false;
        if (!$id) {
            $record->created = time();
        }
        $record->edited = time();

        if ($id) {
            return $DB->update_record('format_ocmooc_parts', $record);
        } else {
            return $DB->insert_record('format_ocmooc_parts', $record);
        }
    }
}