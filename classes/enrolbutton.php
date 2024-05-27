<?php

namespace format_ocmooc;

class enrolbutton {

    /**
     * Checks if a specific User is already enrolled in the course
     */
    public static function is_current_user_enrolled() {
        global $COURSE, $USER;

        $context = \context_course::instance($COURSE->id);
    
        if (is_enrolled($context, $USER, '', true)) {
            return true;
        }
    
        return false;
    }
}