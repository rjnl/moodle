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
 * This file contains a class definition for the LISResult container resource
 *
 * @package    ltixservice_gradebookservices
 * @copyright  2017 Cengage Learning http://www.cengage.com
 * @author     Dirk Singels, Diego del Blanco, Claude Vervoort
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace ltixservice_gradebookservices\local\resources;

use core_ltix\local\lticore\message\context\collection\launch_context;
use core_ltix\local\lticore\message\context\item\course_context;
use core_ltix\local\lticore\message\context\item\resource_link_context;
use ltixservice_gradebookservices\local\service\gradebookservices;
use core_ltix\local\ltiservice\resource_base;
use core_ltix\local\placement\service\resource_link_manager;

defined('MOODLE_INTERNAL') || die();

/**
 * A resource implementing LISResult container.
 *
 * @package    ltixservice_gradebookservices
 * @copyright  2017 Cengage Learning http://www.cengage.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scores extends resource_base {

    /**
     * Class constructor.
     *
     * @param \ltixservice_gradebookservices\local\service\gradebookservices $service Service instance
     */
    public function __construct($service) {

        parent::__construct($service);
        $this->id = 'Score.collection';
        $this->template = '/{context_id}/lineitems/{item_id}/lineitem/scores';
        $this->variables[] = 'Scores.url';
        $this->formats[] = 'application/vnd.ims.lis.v1.scorecontainer+json';
        $this->formats[] = 'application/vnd.ims.lis.v1.score+json';
        $this->methods[] = 'POST';

    }

    /**
     * Execute the request for this resource.
     *
     * @param \core_ltix\local\ltiservice\response $response  Response object for this request.
     */
    public function execute($response) {
        global $CFG, $DB;

        $params = $this->parse_template();
        $contextid = $params['context_id'];
        $itemid = $params['item_id'];

        // GET is disabled by the moment, but we have the code ready
        // for a future implementation.

        $isget = $response->get_request_method() === 'GET';
        if ($isget) {
            $contenttype = $response->get_accept();
        } else {
            $contenttype = $response->get_content_type();
        }
        $container = empty($contenttype) || ($contenttype === $this->formats[0]);
        // We will receive typeid when working with LTI 1.x, if not the we are in LTI 2.
        $typeid = optional_param('type_id', null, PARAM_ALPHANUM);

        $scope = gradebookservices::SCOPE_GRADEBOOKSERVICES_SCORE;

        try {
            if (!$this->check_tool($typeid, $response->get_request_data(), array($scope))) {
                throw new \Exception(null, 401);
            }
            $typeid = $this->get_service()->get_type()->id;
            if (empty($contextid) || !($container ^ ($response->get_request_method() === self::HTTP_POST)) ||
                    (!empty($contenttype) && !in_array($contenttype, $this->formats))) {
                throw new \Exception('No context or unsupported content type', 400);
            }
            if (!($course = $DB->get_record('course', array('id' => $contextid), 'id', IGNORE_MISSING))) {
                throw new \Exception("Not Found: Course {$contextid} doesn't exist", 404);
            }
            if (!$this->get_service()->is_allowed_in_context($typeid, $course->id)) {
                throw new \Exception('Not allowed in context', 403);
            }
            if (!$DB->record_exists('grade_items', array('id' => $itemid))) {
                throw new \Exception("Not Found: Grade item {$itemid} doesn't exist", 404);
            }
            $item = $this->get_service()->get_lineitem($contextid, $itemid, $typeid);
            if ($item === false) {
                throw new \Exception('Line item does not exist', 404);
            }
            $gbs = gradebookservices::find_ltixservice_gradebookservice_for_lineitem($itemid);
            $ltilinkid = null;
            if ($gbs && isset($gbs->ltilinkid)) {
                $ltilinkid = $gbs->ltilinkid;
            } else if (isset($item->iteminstance)) {
                // This fallback exists to support grade validation for resource links not having an associated gbs row,
                // which only occurs if they were created before the patch in MDL-60416 landed.
                // As such, the assumption in this code is that the gradeitem relates to a mod_lti instance record,
                // as that was the only usage of LTI (placement) at that time.
                $cm = get_coursemodule_from_instance('lti', $item->iteminstance, $item->courseid);
                if ($cm) {
                    $resourcelink = resource_link_manager::get_resource_link_by_item($cm->id, 'mod_lti:activityplacement');
                    $ltilinkid = $resourcelink ? $resourcelink->get('id') : null;
                }
            }
            if ($ltilinkid != null) {
                if (is_null($typeid)) {
                    if (!gradebookservices::check_lti_id($ltilinkid, $item->courseid,
                            $this->get_service()->get_tool_proxy()->id)) {
                        $response->set_code(403);
                        $response->set_reason("Invalid LTI id supplied.");
                        return;
                    }
                } else {
                    if (!gradebookservices::check_lti_1x_id($ltilinkid, $item->courseid, $typeid)) {
                        $response->set_code(403);
                        $response->set_reason("Invalid LTI id supplied.");
                        return;
                    }
                }
            }
            $json = '[]';
            require_once($CFG->libdir.'/gradelib.php');
            switch ($response->get_request_method()) {
                case 'GET':
                    $response->set_code(405);
                    $response->set_reason("GET requests are not allowed.");
                    break;
                case 'POST':
                    try {
                        $json = $this->get_json_for_post_request($response, $response->get_request_data(), $item, $contextid,
                            $typeid);
                        $response->set_content_type($this->formats[1]);
                    } catch (\Exception $e) {
                        $response->set_code($e->getCode());
                        $response->set_reason($e->getMessage());
                    }
                    break;
                default:  // Should not be possible.
                    $response->set_code(405);
                    $response->set_reason("Invalid request method specified.");
                    return;
            }
            $response->set_body($json);
        } catch (\Exception $e) {
            $response->set_code($e->getCode());
            $response->set_reason($e->getMessage());
        }
    }

    /**
     * Generate the JSON for a POST request.
     *
     * @param \core_ltix\local\ltiservice\response $response Response object for this request.
     * @param string $body POST body
     * @param object $item Grade item instance
     * @param string $contextid
     * @param string $typeid
     *
     * @throws \Exception
     */
    private function get_json_for_post_request($response, $body, $item, $contextid, $typeid) {
        $score = json_decode($body);
        if (empty($score) ||
                !isset($score->userId) ||
                !isset($score->timestamp) ||
                !isset($score->gradingProgress) ||
                !isset($score->activityProgress) ||
                !isset($score->timestamp) ||
                isset($score->timestamp) && !gradebookservices::validate_iso8601_date($score->timestamp) ||
                (isset($score->scoreGiven) && !is_numeric($score->scoreGiven)) ||
                (isset($score->scoreGiven) && !isset($score->scoreMaximum)) ||
                (isset($score->scoreMaximum) && !is_numeric($score->scoreMaximum)) ||
                (!gradebookservices::is_user_gradable_in_course($contextid, $score->userId))
                ) {
            throw new \Exception('Incorrect score received' . $body, 400);
        }
        $score->timemodified = intval($score->timestamp);

        if (!isset($score->scoreMaximum)) {
            $score->scoreMaximum = 1;
        }
        $response->set_code(200);
        $grade = \grade_grade::fetch(array('itemid' => $item->id, 'userid' => $score->userId));
        if ($grade &&  !empty($grade->timemodified)) {
            if ($grade->timemodified >= strtotime($score->timestamp)) {
                $exmsg = "Refusing score with an earlier timestamp for item " . $item->id . " and user " . $score->userId;
                throw new \Exception($exmsg, 409);
            }
        }
        if (isset($score->scoreGiven)) {
            if ($score->gradingProgress != 'FullyGraded') {
                $score->scoreGiven = null;
            }
        }
        $this->get_service()->save_grade_item($item, $score, $score->userId);
    }

    /**
     * Parse a value for custom parameter substitution variables.
     * @param string $value String to be parsed
     * @param launch_context $launchcontext
     * @return string
     */
    public function parse_val(string $value, launch_context $launchcontext): string {
        if (str_contains($value, '$Scores.url')) {
            $course = $launchcontext->require(course_context::class)->course;
            $resourcelink = $launchcontext->get(resource_link_context::class)->resourcelink;
            $this->params['context_id'] = $course->id;
            $resolved = '';

            if (!is_null($resourcelink) && $resourcelink->get('gradable') === true) {
                global $CFG;
                require_once($CFG->libdir . '/gradelib.php');

                $component = $resourcelink->get('component');
                [$type, $name] = \core_component::normalize_component($component);
                if ($type === 'mod') {
                    // Per ltixservice_gradebookservices\local\service\gradebookservices, gradable, mod-associated links must
                    // be using cmid as the value of itemid.
                    $cmid = $resourcelink->get('itemid');
                    if (!empty($cmid)) {
                        $cm = get_fast_modinfo($course)->get_cm($cmid);
                        $item = \grade_item::fetch([
                            'courseid' => $course->id,
                            'itemtype' => $type,
                            'itemmodule' => $name,
                            'iteminstance' => $cm->instance,
                        ]);
                        if ($item) {
                            $this->params['item_id'] = $item->id;
                            $resolved = parent::get_endpoint();
                        }
                    }
                }
            }
            $value = str_replace('$Scores.url', $resolved, $value);
        }

        return $value;
    }
}
