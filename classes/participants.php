<?php

namespace format_ocmooc;

use format_ocmooc\forms\participantsform;

require_once($CFG->dirroot . '/enrol/locallib.php');

require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->dirroot . '/blocks/online_users_map/lib.php');

class participants extends base {

    private $data;

    private $counter = 1;

    private $datestring;

    public function __construct($courseid, $url) {
        parent::__construct($courseid, $url);
        $this->mform = new participantsform($this->url, ['courseid' => $this->courseid]);
        $this->setdatadb = 'format_ocmooc_parts';

        $this->datestring = new \stdClass();
        $this->datestring->year = get_string('year');
        $this->datestring->years = get_string('years');
        $this->datestring->day = get_string('day');
        $this->datestring->days = get_string('days');
        $this->datestring->hour = get_string('hour');
        $this->datestring->hours = get_string('hours');
        $this->datestring->min = get_string('min');
        $this->datestring->mins = get_string('mins');
        $this->datestring->sec = get_string('sec');
        $this->datestring->secs = get_string('secs');
    }

    protected function render_view_custom() {
        global $PAGE, $COURSE, $DB, $OUTPUT;

        $page = optional_param('page', 0, PARAM_INT);
        $perpage = optional_param('perpage', 10, PARAM_INT);

        $data = $this->get_data();

        if ($data->town && $data->country) {
            echo get_html_osmmap();
        }

        $manager = new \course_enrolment_manager($PAGE, $COURSE);

        list($header, $titles, $nosort) = $this->prepare_table_header();

        $table = new \flexible_table('user-index-participants-' . $this->courseid);
        $table->define_columns($header);
        $table->define_headers($titles);
        $table->define_baseurl($this->url);

        $table->set_attribute('cellspacing', '0');
        $table->set_attribute('id', 'participants');
        $table->set_attribute('class', 'generaltable generalbox');

        foreach ($nosort as $col) {
            $table->no_sorting($col);
        }

        $table->sortable(true);

        $table->setup();

        $instances = $manager->get_enrolment_instances();
        $ids = array();
        foreach ($instances as $instance) {
            $ids[] = $instance->id;
        }

        list($insql, $params) = $DB->get_in_or_equal($ids);

        $sql = "SELECT u.* FROM {user} u
                INNER JOIN {user_enrolments} ue
                    ON u.id = ue.userid
                WHERE enrolid $insql";

        $sort = $table->get_sql_sort();
        if ($sort) {
            $sql .= " ORDER BY $sort";
        }

        $users = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

        foreach ($users as $user) {
            $user = $this->prepare_user_entry($user);
            $table->add_data($user);
        }

        // There was a problem with count_records_sql. The count was incorrect.
        $total = $DB->get_records_sql($sql, $params);
        $total = count($total);

        echo $OUTPUT->paging_bar($total, $page, $perpage, $this->url);

        $table->finish_html();

        echo $OUTPUT->paging_bar($total, $page, $perpage, $this->url);
    }

    private function prepare_table_header() {
        $data = $this->get_data();

        $header = [];
        $titles = [];
        $notsortable = [];

        if ($data->profilepicture) {
            $notsortable[] = 'profilepicture';
            $header[] = 'profilepicture';
            $titles[] = get_string('profilepicture', 'format_ocmooc');
        }

        switch ($data->namedisplay) {
            case 1:
                $header[] = 'username';
                $titles[] = get_string('username', 'format_ocmooc');
                break;
            case 2:
                $notsortable[] = 'name';
                $header[] = 'name';
                $titles[] = get_string('name', 'format_ocmooc');
                break;
            default:
                $header[] = 'fullname';
                $titles[] = get_string('fullname', 'format_ocmooc');
                break;
        }

        if ($data->email) {
            $header[] = 'email';
            $titles[] = get_string('email', 'format_ocmooc');
        }

        if ($data->town) {
            $header[] = 'town';
            $titles[] = get_string('town', 'format_ocmooc');
        }

        if ($data->country) {
            $header[] = 'country';
            $titles[] = get_string('country', 'format_ocmooc');
        }

        if ($data->badges) {
            $notsortable[] = 'badges';
            $header[] = 'badges';
            $titles[] = get_string('badges', 'format_ocmooc');
        }

        if ($data->roles) {
            $notsortable[] = 'roles';
            $header[] = 'roles';
            $titles[] = get_string('roles', 'format_ocmooc');
        }

        if ($data->groups) {
            $notsortable[] = 'groups';
            $header[] = 'groups';
            $titles[] = get_string('groups', 'format_ocmooc');
        }

        if ($data->lastaccess) {
            $header[] = 'lastaccess';
            $titles[] = get_string('lastaccess', 'format_ocmooc');
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
            $userdata[] = $user->town;
        }

        if ($data->country) {
            $userdata[] = $user->country;
        }

        if ($data->badges) {
            $badges = badges_get_user_badges($user->id, $this->courseid);
            $images = [];
            foreach ($badges as $badge) {
                $imageurl = \moodle_url::make_pluginfile_url(\context_course::instance($this->courseid)->id, 'badges', 'badgeimage', $badge->id, '/', 'f1', FALSE);
                $images[] = \html_writer::img($imageurl, $badge->name, ['style' => 'width: 30px; heigth: 30px;']);
            }

            $seperator = \html_writer::tag('span', ', ', ['class' => '']);
            $userdata[] = implode($seperator, $images);
        }

        if ($data->roles) {
            $roles = get_user_roles(\context_course::instance($this->courseid), $user->id, false);
            $rolesdata = [];
            foreach ($roles as $role) {
                $rolesdata[] = \html_writer::tag('span', $role->fullname ?? $role->shortname, ['class' => '']);
            }
            $seperator = \html_writer::tag('span', ', ', ['class' => '']);
            $userdata[] = implode($seperator, $rolesdata);
        }

        if ($data->groups) {
            $groups = groups_get_user_groups($this->courseid, $user->id);
            $groupsdata = [];
            foreach ($groups as $group) {
                $groupsdata[] = \html_writer::tag('span', $group->name, ['class' => '']);
            }

            $seperator = \html_writer::tag('span', ', ', ['class' => '']);
            $userdata[] = implode($seperator, $groupsdata);
        }

        if ($data->lastaccess) {
            $userdata[] = format_time(time() - $user->lastaccess, $this->datestring);
        }

        return $userdata;
    }

    private function get_data() {
        global $DB;

        if (!isset($this->data)) {
            $data = $DB->get_record($this->setdatadb, ['courseid' => $this->courseid]);
            if (!$data) {
                $data = new \stdClass();
                $data->courseid = $this->courseid;
                $data->profilepicture = true;
                $data->namedisplay = 0;
                $data->email = $data->email ?? true;
                $data->town = $data->town ?? true;
                $data->country = $data->country ?? true;
                $data->badges = $data->badges ?? true;
                $data->roles = $data->roles ?? false;
                $data->groups = $data->groups ?? false;
                $data->lastaccess = $data->lastaccess ?? false;
                $data->created = time();
                $data->edited = time();

                $DB->insert_record('format_ocmooc_parts', $data);
            }
            $this->data = $data;
        }

        return $this->data;
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
        $record->courseid = $this->courseid;
        $record->profilepicture = $data->profilepicture ?? false;
        $record->namedisplay = $data->namedisplay;
        $record->email = $data->email ?? false;
        $record->town = $data->town ?? false;
        $record->country = $data->country ?? false;
        $record->badges = $data->badges ?? false;
        $record->roles = $data->roles ?? false;
        $record->groups = $data->groups ?? false;
        $record->lastaccess = $data->lastaccess ?? false;
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