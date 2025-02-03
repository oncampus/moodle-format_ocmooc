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

namespace format_ocmooc;

require_once ($CFG->libdir . '/modinfolib.php');

class forumlist extends base {

    public function __construct($courseid, $url) {
        $this->title = get_string('forumlist', 'format_ocmooc');
        parent::__construct($courseid, $url);
    }


    protected function render_view_custom() {
        global $OUTPUT, $CFG;
        $data = [];
        $forumids = $this->get_forumslistids($this->courseid);
        foreach ($forumids as $forumid) {
            $data[] = get_fast_modinfo($this->courseid)->get_cm($forumid->id);
        }
        $data['forumslist'] = array_values($data);
        $data['urllink'] = $CFG->wwwroot .'/theme/image.php/boost/forum/1700745946/monologo?filtericon=1';
        echo $OUTPUT->render_from_template('format_ocmooc/forumlist/forumlist', $data);
    }

    private function get_forumslistids($courseid) {
        global $DB;
        $sql = "SELECT cm.id
                FROM {course_modules} cm
                JOIN {modules} m ON cm.module = m.id
                WHERE cm.course = :courseid
                AND m.name = 'forum'";
        return $DB->get_records_sql($sql, ['courseid' => $courseid]);

    }
    private function get_forum_info($forumid) {
        global $DB;

        $sql = "SELECT f.id, f.name
            FROM {forum} f
            WHERE id= ?";
        $records = $DB->get_records_sql($sql, ['id' => $forumid]);
        $url = new \moodle_url('forum/view.php', ['id' => $records->id]);
        return [$records->name, $url];
    }
    protected function render_editor_custom() {

    }

    public function render_overview() {

    }

    protected function handle_data($data) {
    }

}
