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
 * This files exposes functions for LTI 1.3 Key Management.
 *
 * @package    mod_lti
 * @copyright  2020 Claude Vervoort (Cengage)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_lti\local\ltiopenid;

/**
 * This class exposes functions for LTI 1.3 Key Management.
 *
 * @deprecated since Moodle 4.4
 * @see \core_ltix\ltiopenid\jwks_helper
 *
 * @package    mod_lti
 * @copyright  2020 Claude Vervoort (Cengage)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class jwks_helper {

    /**
     * Returns the private key to use to sign outgoing JWT.
     *
     * @deprecated since Moodle 4.4
     * @return array keys are kid and key in PEM format.
     */
    public static function get_private_key() {
        debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\ltiopenid\jwks_helper::get_private_key() instead.',
            DEBUG_DEVELOPER);

        return \core_ltix\ltiopenid\jwks_helper::get_private_key();
    }

    /**
     * Returns the JWK Key Set for this site.
     *
     * @deprecated since Moodle 4.4
     * @return array keyset exposting the site public key.
     */
    public static function get_jwks() {
        debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\ltiopenid\jwks_helper::get_jwks() instead.',
            DEBUG_DEVELOPER);

        return \core_ltix\ltiopenid\jwks_helper::get_jwks();
    }

    /**
     * Take an array of JWKS keys and infer the 'alg' property for a single key, if missing, based on an input JWT.
     *
     * This only sets the 'alg' property for a single key when all the following conditions are met:
     * - The key's 'kid' matches the 'kid' provided in the JWT's header.
     * - The key's 'alg' is missing.
     * - The JWT's header 'alg' matches the algorithm family of the key (the key's kty).
     * - The JWT's header 'alg' matches one of the approved LTI asymmetric algorithms.
     *
     * Keys not matching the above are left unchanged.
     *
     * @deprecated since Moodle 4.4
     * @param array $jwks the keyset array.
     * @param string $jwt the JWT string.
     * @return array the fixed keyset array.
     */
    public static function fix_jwks_alg(array $jwks, string $jwt): array {
        debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\ltiopenid\jwks_helper::fix_jwks_alg() instead.',
            DEBUG_DEVELOPER);

        return \core_ltix\ltiopenid\jwks_helper::fix_jwks_alg($jwks, $jwt);
    }

}
