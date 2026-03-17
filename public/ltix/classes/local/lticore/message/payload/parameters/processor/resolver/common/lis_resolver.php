<?php

namespace core_ltix\local\lticore\message\payload\parameters\processor\resolver\common;

use core_ltix\local\lticore\message\context\collection\launch_context;
use core_ltix\local\lticore\message\context\item\course_context;
use core_ltix\local\lticore\message\context\item\user_context;
use core_ltix\local\lticore\message\payload\parameters\pipeline\core\parameters_processor;

/**
 * Resolves lis parameters.
 *
 * @internal
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lis_resolver implements parameters_processor {

    public function process(array $parameters, launch_context $data): array {

        $course = $data->require(course_context::class)->course;
        $user = $data->require(user_context::class)->user;

        $parameters['lis_course_section_sourcedid'] = $course->idnumber;

        $parameters['lis_person_name_given'] = $user->firstname;
        $parameters['lis_person_name_family'] = $user->lastname;
        $parameters['lis_person_name_full'] = fullname($user);
        $parameters['lis_person_sourcedid'] = $user->idnumber;
        $parameters['lis_person_contact_email_primary'] = $user->email;

        return $parameters;
    }
}
