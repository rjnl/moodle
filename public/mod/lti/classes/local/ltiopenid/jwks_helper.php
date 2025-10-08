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
 * @deprecated since Moodle 5.1
 * @see \core_ltix\local\ltiopenid\jwks_helper
 *
 * @package    mod_lti
 * @copyright  2020 Claude Vervoort (Cengage)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\core\attribute\deprecated(
    reason: 'Use \core_ltix\local\ltiopenid\jwks_helper instead',
    since: '5.1',
    mdl: 'MDL-79113',
)]
class jwks_helper {

    /**
     * Returns the private key to use to sign outgoing JWT.
     *
     * @deprecated since Moodle 5.1
     * @return array keys are kid and key in PEM format.
     */
    #[\core\attribute\deprecated(
        reason: 'Use \core_ltix\local\ltiopenid\jwks_helper::get_private_key() instead',
        since: '5.1',
        mdl: 'MDL-79113',
    )]
    public static function get_private_key() {
        \core\deprecation::emit_deprecation_if_present([self::class, __FUNCTION__]);
        return \core_ltix\local\ltiopenid\jwks_helper::get_private_key();
    }

    /**
     * Returns the JWK Key Set for this site.
     *
     * @deprecated since Moodle 5.1
     * @return array keyset exposting the site public key.
     */
    #[\core\attribute\deprecated(
        reason: 'Use \core_ltix\local\ltiopenid\jwks_helper::get_jwks() instead',
        since: '5.1',
        mdl: 'MDL-79113',
    )]
    public static function get_jwks() {
        \core\deprecation::emit_deprecation_if_present([self::class, __FUNCTION__]);
        return \core_ltix\local\ltiopenid\jwks_helper::get_jwks();
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
     * @deprecated since Moodle 5.1
     * @param array $jwks the keyset array.
     * @param string $jwt the JWT string.
     * @return array the fixed keyset array.
     */
    #[\core\attribute\deprecated(
        reason: 'Use \core_ltix\local\ltiopenid\jwks_helper::fix_jwks_alg() instead',
        since: '5.1',
        mdl: 'MDL-79113',
    )]
    public static function fix_jwks_alg(array $jwks, string $jwt): array {
        \core\deprecation::emit_deprecation_if_present([self::class, __FUNCTION__]);
        return \core_ltix\local\ltiopenid\jwks_helper::fix_jwks_alg($jwks, $jwt);
    }

}
