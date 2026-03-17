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

namespace core_ltix\route\controller\launchrequest;

use core\router\require_login;
use core\router\route;
use core_ltix\local\lticore\message\launchrequest\service\datarepository\launch_data_repository;
use core_ltix\local\lticore\message\launchrequest\service\v2p0\v2p0_resource_link_launch_service;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Controller handling LTI v2p0 Resource Link Request launches.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class v2p0_resource_link_launch_controller {

    /**
     * Ctor.
     *
     * @param v2p0_resource_link_launch_service $launchservice
     * @param launch_data_repository $launchdatarepository
     */
    public function __construct(
        protected v2p0_resource_link_launch_service $launchservice,
        protected launch_data_repository $launchdatarepository,
    ) {
    }

    /**
     * Handler for the v2p0 resource link request launch route.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param int $resourcelinkid
     * @return ResponseInterface
     */
    #[route(
        // Resolves to https://SITE/ltix/v2p0/resourcelink/10/launch
        path: '/v2p0/resourcelink/{resourcelinkid}/launch',
        method: ['GET', 'POST'],
        requirelogin: new require_login(
            requirelogin: true,
            autologinguest: false,
        )
    )]
    public function launch_resource_link(
        ServerRequestInterface $request,
        ResponseInterface $response,
        int $resourcelinkid,
    ): ResponseInterface {
        // TODO: Once $request->getAttribute('user') is implemented (in MDL-87215), use that.
        global $USER, $PAGE;

        // This is a bit clunky, but PAGE context needs to be set just as it would have once been,
        // before various Moodle APIs will work as expected (text formatting, etc).
        $link = $this->launchdatarepository->get_resource_link($resourcelinkid);
        $linkcontext = \context::instance_by_id($link->get('contextid'));
        $PAGE->set_context($linkcontext);

        $response->getBody()->write(
            $this->launchservice->launch($resourcelinkid, $USER)->to_html_form()
        );

        return $response;
    }
}
