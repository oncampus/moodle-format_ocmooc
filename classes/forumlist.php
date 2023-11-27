<?php

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
        foreach ($forumids as $forumid){
            $data[] = get_fast_modinfo($this->courseid)->get_cm($forumid->id);
        }
        $data['forumslist'] = array_values($data);
        $data['urllink'] = $CFG->wwwroot .'/theme/image.php/boost/forum/1700745946/monologo?filtericon=1';
        echo $OUTPUT->render_from_template('format_ocmooc/forumlist/forumlist', $data);
    }

    private function get_forumslistids($courseid){
        global $DB;
        $sql = "SELECT cm.id
                FROM {course_modules} cm
                JOIN {modules} m ON cm.module = m.id
                WHERE cm.course = :courseid 
                AND m.name = 'forum'";
        return $DB->get_records_sql($sql, ['courseid' => $courseid]);

    }
    private function get_forum_info($forumid){
        global $DB;

        $sql = "SELECT f.id, f.name
            FROM {forum} f
            WHERE id= ?";
        $records = $DB->get_records_sql($sql, ['id'=>$forumid]);
        $url = new \moodle_url('forum/view.php', ['id' =>$records->id]);
        return [$records->name, $url];
    }
    protected function render_editor_custom() {

    }

    public function render_overview() {

    }

    protected function handle_data($data) {
    }

}