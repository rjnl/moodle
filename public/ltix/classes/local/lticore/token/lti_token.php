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

namespace core_ltix\local\lticore\token;

use Firebase\JWT\JWT;
use Firebase\JWT\JWK;

/**
 * Models the non-encoded claims data in the body of a JWT.
 *
 * @package    core_ltix
 * @copyright  2025 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class lti_token {

    /**
     * Constructor.
     *
     * @param array $claims initial set of JWT claims, keyed by claim name.
     */
    public function __construct(private array $claims) {
    }

    /**
     * Decode and verify a compact JWT string, returning an lti_token instance populated with its claims.
     *
     * The signature is verified against the supplied JWKS key set. An exception is thrown if the JWT
     * is malformed, the signature is invalid, or no matching key is found in the key set.
     *
     * @param string $jwt the compact JWT string to decode.
     * @param array $keyset the JWKS key set as an associative array (e.g. ['keys' => [...]]).
     * @return self a new lti_token instance populated with the decoded claims.
     */
    public static function from_jwt_with_keyset(string $jwt, array $keyset): self {
        // Validate + decode the JWT into its component claims.
        $claims = JWT::decode($jwt, JWK::parseKeySet($keyset));
        $claims = json_decode(json_encode($claims), true); // Convert to array.

        return new self($claims);
    }

    /**
     * Add or overwrite a claim in this token.
     *
     * Returns the same instance to allow method chaining.
     *
     * @param string $name the claim name. Must be a non-empty string.
     * @param string|int|object|array $value the claim value.
     * @return self this instance.
     * @throws \coding_exception if the claim name is empty.
     */
    public function add_claim(string $name, string|int|object|array $value): self {
        if ($name === '') {
            throw new \coding_exception('Claim name must not be empty.');
        }
        $this->claims[$name] = $value;
        return $this;
    }

    /**
     * Return the value of a claim by name, or null if it is not present.
     *
     * @param string $name the claim name.
     * @return string|int|object|array|null the claim value, or null if not set.
     */
    public function get_claim(string $name): string|int|object|array|null {
        return $this->claims[$name] ?? null;
    }

    /**
     * Encode this token's claims as a signed compact JWT string.
     *
     * The following claims are always generated at encoding time and will override any identically-named
     * claims set earlier:
     * - 'iat': the current Unix timestamp (issued-at).
     * - 'exp': the current Unix timestamp plus 60 seconds (expiry).
     *
     * @param string $privatekey the PEM-encoded RSA (or EC) private key used to sign the JWT.
     * @param string $alg the signing algorithm to use (default: 'RS256').
     * @param string|null $kid optional key ID to include in the JWT header.
     * @return string the signed compact JWT string.
     */
    public function to_jwt(string $privatekey, string $alg = 'RS256', ?string $kid = null): string {

        // Add claims which are set at the time of JWT creation. based on their inclusion in the $generatedclaim parameter.
        // These will override any claims of the same name set earlier, which is intended.
        $now = time();
        $claims = array_merge(
            $this->claims,
            [
                'exp' => $now + 60,
                'iat' => $now,
                // Consider adding support for jti and nbf generation to make this token more versatile in lti usage.
                // If these are included, it would allow this class to be used for things like a JWT access token, as defined in
                // the spec, where 'jti' is also a required claim. nbf should use $now, whereas jti should be generated randomly.
            ]
        );

        return JWT::encode($claims, $privatekey, $alg, $kid);
    }
}
