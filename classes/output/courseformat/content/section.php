<?php

namespace format_ocmooc\output\courseformat\content;

use \core_courseformat\output\local\content\section as section_base;
use renderer_base;
use stdClass;

class section extends section_base {

    public function export_for_template(renderer_base $output): stdClass {
        $data = parent::export_for_template($output);

        return $data;
    }

    public function get_template_name(\renderer_base $renderer): string {
        return "format_ocmooc/local/content/section";
    }

}