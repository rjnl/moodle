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
use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests covering lti_token.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(lti_token::class)]
class lti_token_test extends \basic_testcase {

    /** @var array $keypairs static keypair cache. */
    private static array $keypairs = [];

    /**
     * Generate an RSA key pair and return private key PEM, public key PEM, and a minimal JWKS.
     *
     * Note: this caches the generated keys to facilitate fast re-use, because key generation is relatively expensive.
     *
     * @param string $kid key ID to embed in the JWKS entry.
     * @return array{privatekey: string, publickey: string, jwks: array}
     */
    #[BeforeClass]
    public static function generate_rsa_key_pair(string $kid = 'kid-1'): array {
        if (isset(self::$keypairs[$kid])) {
            return self::$keypairs[$kid];
        }

        $res = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($res, $privatekey);
        $details = openssl_pkey_get_details($res);
        $publickey = $details['key'];

        $jwks = [
            'keys' => [[
                'kty' => 'RSA',
                'alg' => 'RS256',
                'use' => 'sig',
                'kid' => $kid,
                'n'   => rtrim(strtr(base64_encode($details['rsa']['n']), '+/', '-_'), '='),
                'e'   => rtrim(strtr(base64_encode($details['rsa']['e']), '+/', '-_'), '='),
            ]],
        ];

        self::$keypairs[$kid] = ['privatekey' => $privatekey, 'publickey' => $publickey, 'jwks' => $jwks];
        return self::$keypairs[$kid];
    }

    #[AfterClass]
    public static function teardown_keyset(): void {
        self::$keypairs = [];
    }

    /**
     * Decode a compact JWT string using a JWKS without verifying expiry, returning claims as an array.
     *
     * @param string $jwt
     * @param array $jwks
     * @return array
     */
    private static function decode_jwt(string $jwt, array $jwks): array {
        $decoded = JWT::decode($jwt, JWK::parseKeySet($jwks));
        return json_decode(json_encode($decoded), true);
    }

    /**
     * Test that claims passed to the constructor are accessible via get_claim().
     *
     * @return void
     */
    public function test_constructor_stores_claims(): void {
        $token = new lti_token(['sub' => 'user-1', 'iss' => 'https://example.com']);

        $this->assertEquals('user-1', $token->get_claim('sub'));
        $this->assertEquals('https://example.com', $token->get_claim('iss'));
    }

    /**
     * Test that a token constructed with no claims has no claims set.
     *
     * @return void
     */
    public function test_constructor_with_empty_claims(): void {
        $token = new lti_token([]);

        $this->assertNull($token->get_claim('sub'));
    }

    /**
     * Test that get_claim() returns null for a claim that was never set.
     *
     * @return void
     */
    public function test_get_claim_returns_null_for_missing_claim(): void {
        $token = new lti_token([]);

        $this->assertNull($token->get_claim('nonexistent'));
    }

    /**
     * Test that get_claim() returns a string claim value.
     *
     * @return void
     */
    public function test_get_claim_returns_string_value(): void {
        $token = new lti_token(['iss' => 'https://platform.example.com']);

        $this->assertSame('https://platform.example.com', $token->get_claim('iss'));
    }

    /**
     * Test that get_claim() returns an integer claim value.
     *
     * @return void
     */
    public function test_get_claim_returns_int_value(): void {
        $now = time();
        $token = new lti_token(['iat' => time()]);

        $this->assertSame($now, $token->get_claim('iat'));
    }

    /**
     * Test that get_claim() returns an array claim value.
     *
     * @return void
     */
    public function test_get_claim_returns_array_value(): void {
        $roles = ['Instructor', 'Administrator'];
        $token = new lti_token(['roles' => $roles]);

        $this->assertSame($roles, $token->get_claim('roles'));
    }

    /**
     * Test that get_claim() returns an object claim value.
     *
     * @return void
     */
    public function test_get_claim_returns_object_value(): void {
        $resourcelink = (object) ['id' => '42', 'title' => 'My Resource'];
        $token = new lti_token(['resource_link' => $resourcelink]);

        $this->assertEquals($resourcelink, $token->get_claim('resource_link'));
    }

    /**
     * Test that add_claim() makes the claim retrievable via get_claim().
     *
     * @return void
     */
    public function test_add_claim_stores_claim(): void {
        $token = new lti_token([]);
        $token->add_claim('aud', 'client-id-abc');

        $this->assertSame('client-id-abc', $token->get_claim('aud'));
    }

    /**
     * Test that add_claim() overwrites a claim of the same name.
     *
     * @return void
     */
    public function test_add_claim_overwrites_existing_claim(): void {
        $token = new lti_token(['iss' => 'https://old.example.com']);
        $token->add_claim('iss', 'https://new.example.com');

        $this->assertSame('https://new.example.com', $token->get_claim('iss'));
    }

    /**
     * Test that add_claim() supports method chaining to add multiple claims.
     *
     * @return void
     */
    public function test_add_claim_supports_chaining(): void {
        $token = (new lti_token([]))
            ->add_claim('iss', 'https://platform.example.com')
            ->add_claim('aud', 'client-id-xyz')
            ->add_claim('sub', 'user-99');

        $this->assertSame('https://platform.example.com', $token->get_claim('iss'));
        $this->assertSame('client-id-xyz', $token->get_claim('aud'));
        $this->assertSame('user-99', $token->get_claim('sub'));
    }

    /**
     * Test that add_claim() accepts an array value.
     *
     * @return void
     */
    public function test_add_claim_accepts_array_value(): void {
        $token = new lti_token([]);
        $token->add_claim('roles', ['Instructor', 'Learner']);

        $this->assertSame(['Instructor', 'Learner'], $token->get_claim('roles'));
    }

    /**
     * Test that add_claim() accepts an object value.
     *
     * @return void
     */
    public function test_add_claim_accepts_object_value(): void {
        $token = new lti_token([]);
        $context = (object) ['id' => 'course-1', 'label' => 'CS101'];
        $token->add_claim('context', $context);

        $this->assertEquals($context, $token->get_claim('context'));
    }

    /**
     * Test that add_claim() disallows adding an empty name for a claim.
     *
     * @return void
     */
    public function test_add_claim_disallows_empty_name(): void {
        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage('Claim name must not be empty.');

        $token = new lti_token([]);
        $token->add_claim('', 'client-id-abc');
    }

    /**
     * Test that to_jwt() returns a string (compact JWT format).
     *
     * @return void
     */
    public function test_to_jwt_returns_a_string(): void {
        ['privatekey' => $privatekey] = self::generate_rsa_key_pair('kid-1');
        $jwt = (new lti_token(['sub' => 'user-1']))->to_jwt($privatekey);

        $this->assertIsString($jwt);
        // Compact JWTs have exactly three dot-separated segments.
        $this->assertCount(3, explode('.', $jwt));
    }

    /**
     * Test that the JWT produced by to_jwt() can be decoded and verified using the matching public key.
     *
     * @return void
     */
    public function test_to_jwt_produces_verifiable_jwt(): void {
        ['privatekey' => $privatekey, 'jwks' => $jwks] = self::generate_rsa_key_pair('kid-1');
        $jwt = (new lti_token(['sub' => 'user-1', 'iss' => 'https://platform.example.com']))->to_jwt($privatekey, kid: 'kid-1');

        $claims = self::decode_jwt($jwt, $jwks);
        $this->assertIsArray($claims);
    }

    /**
     * Test that claims set before calling to_jwt() are present in the decoded JWT.
     *
     * @return void
     */
    public function test_to_jwt_encodes_all_set_claims(): void {
        ['privatekey' => $privatekey, 'jwks' => $jwks] = self::generate_rsa_key_pair('kid-1');
        $token = new lti_token([
            'sub' => 'user-42',
            'iss' => 'https://platform.example.com',
            'aud' => 'client-id',
            'roles' => ['Instructor'],
        ]);

        $claims = self::decode_jwt($token->to_jwt($privatekey, kid: 'kid-1'), $jwks);

        $this->assertSame('user-42', $claims['sub']);
        $this->assertSame('https://platform.example.com', $claims['iss']);
        $this->assertSame('client-id', $claims['aud']);
        $this->assertSame(['Instructor'], $claims['roles']);
    }

    /**
     * Test that to_jwt() always adds an 'iat' claim equal to the current time.
     *
     * @return void
     */
    public function test_to_jwt_sets_iat(): void {
        ['privatekey' => $privatekey, 'jwks' => $jwks] = self::generate_rsa_key_pair('kid-1');
        $before = time();
        $claims = self::decode_jwt((new lti_token([]))->to_jwt($privatekey, kid: 'kid-1'), $jwks);
        $after = time();

        $this->assertArrayHasKey('iat', $claims);
        $this->assertGreaterThanOrEqual($before, $claims['iat']);
        $this->assertLessThanOrEqual($after, $claims['iat']);
    }

    /**
     * Test that to_jwt() always adds an 'exp' claim set 60 seconds after 'iat'.
     *
     * @return void
     */
    public function test_to_jwt_sets_exp_sixty_seconds_after_iat(): void {
        ['privatekey' => $privatekey, 'jwks' => $jwks] = self::generate_rsa_key_pair('kid-1');
        $claims = self::decode_jwt((new lti_token([]))->to_jwt($privatekey, kid: 'kid-1'), $jwks);

        $this->assertArrayHasKey('exp', $claims);
        $this->assertEquals($claims['iat'] + 60, $claims['exp']);
    }

    /**
     * Test that to_jwt() overrides any 'iat' or 'exp' values set beforehand.
     *
     * @return void
     */
    public function test_to_jwt_overrides_caller_iat_and_exp(): void {
        ['privatekey' => $privatekey, 'jwks' => $jwks] = self::generate_rsa_key_pair('kid-1');
        $token = new lti_token(['iat' => 1, 'exp' => 2]);
        $claims = self::decode_jwt($token->to_jwt($privatekey, kid: 'kid-1'), $jwks);

        $this->assertNotEquals(1, $claims['iat']);
        $this->assertNotEquals(2, $claims['exp']);
    }

    /**
     * Test that the 'kid' header is present in the JWT when a kid is supplied to to_jwt().
     *
     * @return void
     */
    public function test_to_jwt_includes_kid_in_header_when_provided(): void {
        ['privatekey' => $privatekey] = self::generate_rsa_key_pair('kid-1');
        $jwt = (new lti_token(['sub' => 'u1']))->to_jwt($privatekey, kid: 'kid-1');

        // Decode the header manually (first segment, base64url-encoded JSON).
        $header = json_decode(base64_decode(strtr(explode('.', $jwt)[0], '-_', '+/')), true);
        $this->assertSame('kid-1', $header['kid']);
    }

    /**
     * Test that the 'kid' header is absent from the JWT when no kid is supplied.
     *
     * @return void
     */
    public function test_to_jwt_omits_kid_in_header_when_not_provided(): void {
        ['privatekey' => $privatekey] = self::generate_rsa_key_pair('kid-1');
        $jwt = (new lti_token(['sub' => 'u1']))->to_jwt($privatekey);

        $header = json_decode(base64_decode(strtr(explode('.', $jwt)[0], '-_', '+/')), true);
        $this->assertArrayNotHasKey('kid', $header);
    }

    /**
     * Test that from_jwt_with_keyset() returns an lti_token instance whose claims match the original token.
     *
     * @return void
     */
    public function test_from_jwt_with_keyset_decodes_claims(): void {
        ['privatekey' => $privatekey, 'jwks' => $jwks] = self::generate_rsa_key_pair('kid-1');
        $original = new lti_token(['sub' => 'user-1', 'iss' => 'https://platform.example.com']);
        $jwt = $original->to_jwt($privatekey, kid: 'kid-1');

        $decoded = lti_token::from_jwt_with_keyset($jwt, $jwks);

        $this->assertInstanceOf(lti_token::class, $decoded);
        $this->assertSame('user-1', $decoded->get_claim('sub'));
        $this->assertSame('https://platform.example.com', $decoded->get_claim('iss'));
    }

    /**
     * Test that from_jwt_with_keyset() restores array-valued claims correctly.
     *
     * @return void
     */
    public function test_from_jwt_with_keyset_decodes_array_claims(): void {
        ['privatekey' => $privatekey, 'jwks' => $jwks] = self::generate_rsa_key_pair('kid-1');
        $original = new lti_token(['roles' => ['Instructor', 'Learner']]);
        $jwt = $original->to_jwt($privatekey, kid: 'kid-1');

        $decoded = lti_token::from_jwt_with_keyset($jwt, $jwks);

        $this->assertSame(['Instructor', 'Learner'], $decoded->get_claim('roles'));
    }

    /**
     * Test that from_jwt_with_keyset() throws when the JWT is signed with a key not in the keyset.
     *
     * @return void
     */
    public function test_from_jwt_with_keyset_throws_on_wrong_key(): void {
        ['privatekey' => $privatekey] = self::generate_rsa_key_pair('kid-1');
        ['jwks' => $otherwjwks] = self::generate_rsa_key_pair('kid-2');

        $jwt = (new lti_token(['sub' => 'u1']))->to_jwt($privatekey, kid: 'kid-1');

        $this->expectException(\Exception::class);
        lti_token::from_jwt_with_keyset($jwt, $otherwjwks);
    }

    /**
     * Test that from_jwt_with_keyset() throws when given a malformed JWT string.
     *
     * @return void
     */
    public function test_from_jwt_with_keyset_throws_on_malformed_jwt(): void {
        ['jwks' => $jwks] = self::generate_rsa_key_pair('kid-1');

        $this->expectException(\Exception::class);
        lti_token::from_jwt_with_keyset('not.a.jwt', $jwks);
    }
}
