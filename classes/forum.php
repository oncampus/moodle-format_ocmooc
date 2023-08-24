<?php

namespace format_ocmooc;

use format_ocmooc\forms\forumform;
use format_ocmooc\forms\searchform;
use mod_forum\local\container as container;


class forum extends base {

    private $searchform;

    public function __construct($courseid, $url) {
        parent::__construct($courseid, $url);
        $this->searchform = new searchform($this->url);
        $this->title = get_string('forum', 'format_ocmooc');
        $this->setdatadb = 'forum';
    }

    public function render_overview() {
    }

    protected function render_view_custom() {
        global $CFG, $OUTPUT, $USER;
        require_once($CFG->dirroot . '/mod/forum/lib.php');
        $type = required_param("type", PARAM_TEXT);
        $forum = forum_get_course_forum($this->courseid, $type);
        $course = get_course($this->courseid);
        if (empty($forum)) {
            echo $OUTPUT->notification('Could not find or create a forum here');
        }

        $coursemodule = get_coursemodule_from_instance('forum', $forum->id);
        $modcontext = \context_module::instance($coursemodule->id);

        $entityfactory = container::get_entity_factory();
        $forumentity = $entityfactory->get_forum_from_stdclass($forum, $modcontext, $coursemodule, $course);

        // Print forum intro above posts  MDL-18483.
        if (trim($forum->intro) != '') {
            $options = (object)[
                'para' => false,
            ];
            $introcontent = format_module_intro('forum', $forum, $coursemodule->id);

            echo $OUTPUT->box($introcontent, 'generalbox', 'intro');
        }
        if($type === "social"){
            $this->searchform->display();
        }


        $numdiscussions = course_get_format($course)->get_course()->numdiscussions;
        if ($numdiscussions < 1) {
            // Make sure that the value is at least one.
            $numdiscussions = 1;
        }
        $rendererfactory = container::get_renderer_factory();
        $discussionsrenderer = $rendererfactory->get_social_discussion_list_renderer($forumentity);
        $cm = \cm_info::create($coursemodule);
        echo $discussionsrenderer->render($USER, $cm, null, null, null, $numdiscussions);
    }

    protected function render_editor_custom() {

    }

    protected function get_data() {

    }

    protected function handle_data($data) {

    }

    protected function get_forum_type($courseid){
        global $DB;
        return $DB->get_field('forum', 'type', ["courseid" => $courseid]);
    }
}