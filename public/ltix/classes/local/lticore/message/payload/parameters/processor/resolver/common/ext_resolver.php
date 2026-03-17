<?php

namespace core_ltix\local\lticore\message\payload\parameters\processor\resolver\common;

use core_ltix\local\lticore\message\context\collection\launch_context;
use core_ltix\local\lticore\message\context\item\user_context;
use core_ltix\local\lticore\message\payload\parameters\pipeline\core\parameters_processor;

/**
 * Resolves ext parameters.
 *
 * @internal
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ext_resolver implements parameters_processor {

    public function process(array $parameters, launch_context $data): array {

        $user = $data->require(user_context::class)->user;
        $parameters['ext_user_username'] = $user->username;
        $parameters['ext_lms'] = 'moodle-2';

        return $parameters;
    }
}
