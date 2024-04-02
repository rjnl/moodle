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
 * A Helper for LTI Dynamic Registration.
 *
 * @package    mod_lti
 * @copyright  2020 Claude Vervoort (Cengage), Carlos Costa, Adrian Hutchinson (Macgraw Hill)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_lti\local\ltiopenid;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/lti/locallib.php');
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use stdClass;

/**
 * This class exposes functions for LTI Dynamic Registration.
 *
 * @deprecated since Moodle 4.4
 * @see \core_ltix\ltiopenid\registration_helper
 *
 * @package    mod_lti
 * @copyright  2020 Claude Vervoort (Cengage), Carlos Costa, Adrian Hutchinson (Macgraw Hill)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class registration_helper {
    /** score scope */
    const SCOPE_SCORE = 'https://purl.imsglobal.org/spec/lti-ags/scope/score';
    /** result scope */
    const SCOPE_RESULT = 'https://purl.imsglobal.org/spec/lti-ags/scope/result.readonly';
    /** lineitem read-only scope */
    const SCOPE_LINEITEM_RO = 'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem.readonly';
    /** lineitem full access scope */
    const SCOPE_LINEITEM = 'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem';
    /** Names and Roles (membership) scope */
    const SCOPE_NRPS = 'https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly';
    /** Tool Settings scope */
    const SCOPE_TOOL_SETTING = 'https://purl.imsglobal.org/spec/lti-ts/scope/toolsetting';

    /** Indicates the token is to create a new registration */
    const REG_TOKEN_OP_NEW_REG = 'reg';
    /** Indicates the token is to update an existing registration */
    const REG_TOKEN_OP_UPDATE_REG = 'reg-update';

    /**
     * Get an instance of this helper
     *
     * @return object
     */
    public static function get() {
        return new registration_helper();
    }

    /**
     * Transforms an LTI 1.3 Registration to a Moodle LTI Config.
     *
     * @param array $registrationpayload the registration data received from the tool.
     * @param string $clientid the clientid to be issued for that tool.
     *
     * @return object the Moodle LTI config.
     */
    public function registration_to_config(array $registrationpayload, string $clientid): object {
        debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\ltiopenid\registration_helper::registration_to_config() instead.',
            DEBUG_DEVELOPER);

        return \core_ltix\ltiopenid\registration_helper::get()->registration_to_config($registrationpayload, $clientid);
    }

    /**
     * Transforms a moodle LTI 1.3 Config to an OAuth/LTI Client Registration.
     *
     * @deprecated since Moodle 4.4
     * @param object $config Moodle LTI Config.
     * @param int $typeid which is the LTI deployment id.
     * @param object $type tool instance in case the tool already exists.
     *
     * @return array the Client Registration as an associative array.
     */
    public function config_to_registration(object $config, int $typeid, ?object $type = null): array {
        debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\ltiopenid\registration_helper::config_to_registration() instead.',
            DEBUG_DEVELOPER);

        return \core_ltix\ltiopenid\registration_helper::get()->config_to_registration($config, $typeid, $type);
    }

    /**
     * Validates the registration token is properly signed and not used yet.
     * Return the client id to use for this registration.
     *
     * @deprecated since Moodle 4.4
     * @param string $registrationtokenjwt registration token
     *
     * @return array with 2 keys: clientid for the registration, type but only if it's an update
     */
    public function validate_registration_token(string $registrationtokenjwt): array {
        debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\ltiopenid\registration_helper::validate_registration_token() instead.',
            DEBUG_DEVELOPER);

        return \core_ltix\ltiopenid\registration_helper::get()->validate_registration_token($registrationtokenjwt);
    }

    /**
     * Initializes an array with the scopes for services supported by the LTI module
     *
     * @deprecated since Moodle 4.4
     * @return array List of scopes
     */
    public function lti_get_service_scopes() {
        debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\ltiopenid\registration_helper::lti_get_service_scopes() instead.',
            DEBUG_DEVELOPER);

        return \core_ltix\ltiopenid\registration_helper::get()->lti_get_service_scopes();
    }

    /**
     * Generates a new client id string.
     *
     * @deprecated since Moodle 4.4
     * @return string generated client id
     */
    public function new_clientid(): string {
        debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\ltiopenid\registration_helper::new_clientid() instead.',
            DEBUG_DEVELOPER);

        return \core_ltix\ltiopenid\registration_helper::get()->new_clientid();
    }

    /**
     * Base64 encoded signature for LTI 1.1 migration.
     *
     * @deprecated since Moodle 4.4
     * @param string $key LTI 1.1 key
     * @param string $salt Salt value
     * @param string $secret LTI 1.1 secret
     *
     * @return string base64encoded hash
     */
    public function sign(string $key, string $salt, string $secret): string {
        debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\ltiopenid\registration_helper::sign() instead.',
            DEBUG_DEVELOPER);

        return \core_ltix\ltiopenid\registration_helper::get()->sign($key, $salt, $secret);
    }

    /**
     * Returns a tool proxy
     *
     * @deprecated since Moodle 4.4
     * @param int $proxyid
     *
     * @return mixed Tool Proxy details
     */
    public function get_tool_proxy(int $proxyid): array {
        debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\ltiopenid\registration_helper::get_tool_proxy() instead.',
            DEBUG_DEVELOPER);

        return \core_ltix\ltiopenid\registration_helper::get()->get_tool_proxy($proxyid);
    }
}
