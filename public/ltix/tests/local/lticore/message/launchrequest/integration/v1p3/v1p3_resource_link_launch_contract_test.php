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

namespace core_ltix\local\lticore\message\launchrequest\integration\v1p3;

use core\di;
use core\url;
use core_ltix\constants;
use core_ltix\local\lticore\lti_version;
use core_ltix\local\lticore\message\context\collection\launch_context;
use core_ltix\local\lticore\message\launchrequest\builder\v1p3\v1p3_resource_link_launch_request_builder;
use core_ltix\local\lticore\message\launchrequest\role_mapper;
use core_ltix\local\lticore\message\launchrequest\service\datarepository\launch_data_repository;
use core_ltix\local\lticore\message\launchrequest\service\v1p3\v1p3_resource_link_launch_service;
use core_ltix\local\lticore\message\payload\lis_vocab_converter;
use core_ltix\local\lticore\message\payload\parameters\pipeline\factory\custom_param_substitutor_factory;
use core_ltix\local\lticore\message\payload\parameters\pipeline\factory\custom_parameter_normaliser_factory;
use core_ltix\local\lticore\message\payload\parameters\pipeline\factory\parameters_builder_factory;
use core_ltix\local\lticore\message\payload\parameters\pipeline\registry\parameter_processor_registry;
use core_ltix\local\lticore\message\payload\parameters\processor\resolver\common\ltixservice_resolver;
use core_ltix\local\lticore\message\substitution\factory\variable_substitutor_factory;
use core_ltix\local\lticore\message\type\message_type_factory;
use core_ltix\local\lticore\message\type\message_type_registry;
use core_ltix\local\lticore\models\resource_link;
use core_ltix\local\lticore\repository\tool_registration_repository;
use core_ltix\local\ltiopenid\jwks_helper;
use core_ltix\local\ltiservice\plugin_parameters_service_interface;
use core_ltix\local\ltiservice\plugin_substitution_service_interface;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Contract tests for LTI 1.3 resource link launch requests.
 *
 * This simulates a real use of the service:launch() and verifies it generates a complete request, as would be sent to the tool's
 * initiate login endpoint, and having a partially complete JWT inside the lti_message_hint param (to be completed after auth).
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(v1p3_resource_link_launch_service::class)]
class v1p3_resource_link_launch_contract_test extends \basic_testcase {

    /**
     * Test a typical launch generates a signed LTI 2.0 launch request.
     * @return void
     */
    public function test_complete_launch_matches_expected(): void {
        global $CFG;

        $toolconfig = (object) [
            'tool' => (object) [
                'id' => '44444',
                'baseurl' => 'https://tool.example.com',
                'ltiversion' => lti_version::LTI_VERSION_1P3->value,
                'issuer' => 'https://moodle-lms.institution.example.org',
                'clientid' => 'ab345cd5678',
            ],
            'config' => (object) [
                'launchcontainer' => constants::LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS,
                'customparameters' => "tool_param_1=value1\ntool_PARAM#2=VALUE2\ntoolParam3=\$User.username",
                'initiatelogin' => 'https://tool.example.com/lti/initiatelogin',
                'sendemailaddr' => constants::LTI_SETTING_ALWAYS,
                'sendname' => constants::LTI_SETTING_ALWAYS,
                'acceptgrades' => constants::LTI_SETTING_ALWAYS,
            ],
        ];

        $resourcelink = new resource_link(0, (object) [
            'id' => 24,
            'typeid' => 44444,
            'contextid' => 456,
            'url' => 'https://tool.example.com/lti/resource/1',
            'title' => 'Resource 1',
            'text' => 'A plain text description of resource 1',
            'launchcontainer' => constants::LTI_LAUNCH_CONTAINER_DEFAULT, // Defer to tool configuration value.
            'customparams' => "link_param_1=linkval1\nlinkParam2=\$Person.name.full\nlinkParam#3=\$Not.resolvable",
            'gradable' => true,
            'servicesalt' => 'abc123',
        ]);

        $roles = [
            'Instructor',
            'http://purl.imsglobal.org/vocab/lis/v2/system/person#Administrator',
            'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Administrator'
        ];

        $user = (object) [
            'id' => '103000',
            'auth' => 'manual',
            'confirmed' => '1',
            'policyagreed' => '0',
            'deleted' => '0',
            'suspended' => '0',
            'mnethostid' => '1',
            'username' => 'username1',
            'password' => '',
            'idnumber' => 'UID:U123',
            'firstname' => '美羽',
            'lastname' => '斎藤',
            'email' => 'username1@example.com',
            'emailstop' => '0',
            'phone1' => '',
            'phone2' => '',
            'institution' => '',
            'department' => '',
            'address' => '',
            'city' => '',
            'country' => '',
            'lang' => 'en',
            'calendartype' => 'gregorian',
            'theme' => '',
            'timezone' => '99',
            'firstaccess' => '0',
            'lastaccess' => '0',
            'lastlogin' => '0',
            'currentlogin' => '0',
            'lastip' => '0.0.0.0',
            'secret' => '',
            'picture' => '0',
            'description' => NULL,
            'descriptionformat' => '1',
            'mailformat' => '1',
            'maildigest' => '0',
            'maildisplay' => '2',
            'autosubscribe' => '1',
            'trackforums' => '0',
            'timecreated' => '1765442016',
            'timemodified' => '1765442016',
            'trustbitmask' => '0',
            'imagealt' => NULL,
            'lastnamephonetic' => '高橋',
            'firstnamephonetic' => 'Michael',
            'middlename' => 'Leah',
            'alternatename' => '娜',
            'moodlenetprofile' => NULL,
        ];

        $course = (object) [
            'id' => '100000',
            'category' => '1',
            'sortorder' => '0',
            'fullname' => 'Test course 1',
            'shortname' => 'tc_1',
            'idnumber' => 'CID:4567',
            'summary' => 'Test course 1
Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Nulla non arcu lacinia neque faucibus fringilla. Vivamus porttitor turpis ac leo. Integer in sapien. Nullam eget nisl. Aliquam erat volutpat. Cras elementum. Mauris suscipit, ligula sit amet pharetra semper, nibh ante cursus purus, vel sagittis velit mauris vel metus. Integer malesuada. Nullam lectus justo, vulputate eget mollis sed, tempor sed magna. Mauris elementum mauris vitae tortor. Aliquam erat volutpat.
Temporibus autem quibusdam et aut officiis debitis aut rerum necessitatibus saepe eveniet ut et voluptates repudiandae sint et molestiae non recusandae. Pellentesque ipsum. Cras pede libero, dapibus nec, pretium sit amet, tempor quis. Aliquam ante. Proin in tellus sit amet nibh dignissim sagittis. Vivamus porttitor turpis ac leo. Duis bibendum, lectus ut viverra rhoncus, dolor nunc faucibus libero, eget facilisis enim ipsum id lacus. In sem justo, commodo ut, suscipit at, pharetra vitae, orci. Aliquam erat volutpat. Nulla est.
Vivamus luctus egestas leo. Aenean fermentum risus id tortor. Mauris dictum facilisis augue. Aliquam erat volutpat. Aliquam ornare wisi eu metus. Aliquam id dolor. Duis condimentum augue id magna semper rutrum. Donec iaculis gravida nulla. Pellentesque ipsum. Etiam dictum tincidunt diam. Quisque tincidunt scelerisque libero. Etiam egestas wisi a erat.
Integer lacinia. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Mauris tincidunt sem sed arcu. Nullam feugiat, turpis at pulvinar vulputate, erat libero tristique tellus, nec bibendum odio risus sit amet ante. Aliquam id dolor. Maecenas sollicitudin. Et harum quidem rerum facilis est et expedita distinctio. Mauris suscipit, ligula sit amet pharetra semper, nibh ante cursus purus, vel sagittis velit mauris vel metus. Nullam dapibus fermentum ipsum. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Pellentesque sapien. Duis risus. Mauris elementum mauris vitae tortor. Suspendisse nisl. Integer rutrum, orci vestibulum ullamcorper ultricies, lacus quam ultricies odio, vitae placerat pede sem sit amet enim.
In laoreet, magna id viverra tincidunt, sem odio bibendum justo, vel imperdiet sapien wisi sed libero. Proin pede metus, vulputate nec, fermentum fringilla, vehicula vitae, justo. Nullam justo enim, consectetuer nec, ullamcorper ac, vestibulum in, elit. Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur? Maecenas lorem. Etiam posuere lacus quis dolor. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos hymenaeos. Curabitur ligula sapien, pulvinar a vestibulum quis, facilisis vel sapien. Nam sed tellus id magna elementum tincidunt. Suspendisse nisl. Vivamus luctus egestas leo. Nulla non arcu lacinia neque faucibus fringilla. Etiam dui sem, fermentum vitae, sagittis id, malesuada in, quam. Etiam dictum tincidunt diam. Etiam commodo dui eget wisi. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Proin pede metus, vulputate nec, fermentum fringilla, vehicula vitae, justo. Duis ante orci, molestie vitae vehicula venenatis, tincidunt ac pede. Pellentesque sapien.',
            'summaryformat' => '0',
            'format' => 'topics',
            'showgrades' => '1',
            'newsitems' => '0',
            'startdate' => '1765382400',
            'enddate' => '0',
            'relativedatesmode' => '0',
            'marker' => '0',
            'maxbytes' => '0',
            'legacyfiles' => '0',
            'showreports' => '0',
            'visible' => '1',
            'visibleold' => '1',
            'downloadcontent' => NULL,
            'groupmode' => '0',
            'groupmodeforce' => '0',
            'defaultgroupingid' => '0',
            'lang' => '',
            'calendartype' => '',
            'theme' => '',
            'timecreated' => '1765422564',
            'timemodified' => '1765422564',
            'requested' => '0',
            'enablecompletion' => '0',
            'completionnotify' => '0',
            'cacherev' => '0',
            'originalcourseid' => NULL,
            'showactivitydates' => '0',
            'showcompletionconditions' => '1',
            'pdfexportfont' => NULL,
            'hiddensections' => 1,
            'coursedisplay' => 0,
        ];

        // Stub registration repo, returning stub objects.
        $stubregrepo = $this->createStub(tool_registration_repository::class);
        $stubregrepo->method('get_by_id')
            ->willReturn($toolconfig);

        // Stub datarepository returning the stub objects.
        $stublaunchdatarepo = $this->createStub(launch_data_repository::class);
        $stublaunchdatarepo->method('get_course')
            ->willReturn($course);
        $stublaunchdatarepo->method('get_resource_link')
            ->willReturn($resourcelink);

        // Stub role mapper, returning stub roles.
        $stubrolemap = $this->createStub(role_mapper::class);
        $stubrolemap->method('map_for')
            ->willReturn($roles);

        // Stub the ltixservice plugins substitution handler.
        $subservicestub = $this->createStub(plugin_substitution_service_interface::class);
        $subservicestub->method('substitute')
            ->willReturnCallback(function(launch_context $launchcontext, string $param) use ($user) {
                return match ($param) {
                    '$Service.substitution.example' => 'service-level value',
                    '$LineItems.url' => 'https://lms.example.com/context/2/lineitems',
                    '$LineItem.url' => 'https://lms.example.com/context/2/lineitem/10',
                    '$ToolProxyBinding.memberships.url' => 'https://lms.example.com/CourseSection/2/bindings/3/memberships',
                    default => $param,
                };
            });

        // Stub the ltixservice plugins launch parameters handler.
        $parameterservicestub = $this->createStub(plugin_parameters_service_interface::class);
        $parameterservicestub->method('get_launch_parameters')
            ->willReturnCallback(function(launch_context $launchcontext) {
                return [
                    // Simulate the addition of AGS and NRPS claims.
                    // These values are taken from the respective ltixservice plugins, gradebookservice and memberships, in order
                    // to remove service plugin dependencies from this test, while still ensuring the output is realistic and as
                    // expected.
                    'custom_lineitems_url' => '$LineItems.url',
                    'custom_lineitem_url' => '$LineItem.url',
                    'custom_context_memberships_url' => '$ToolProxyBinding.memberships.url',
                    'custom_context_memberships_v2_url' => '$ToolProxyBinding.memberships.url',
                    'custom_context_memberships_versions' => '1.0,2.0',
                ];
            });
        $ltixserviceprocessor = new ltixservice_resolver($parameterservicestub);

        // Registries are currently assembled in the DI container.
        // For now, use DI to fetch those two things rather than mocking them.
        $processorregistry = di::get(parameter_processor_registry::class);
        $messagetyperegistry = di::get(message_type_registry::class);

        // Replace the ltixservice plugin processor in the register with a stub to control the output
        // of service-level parameter addition within the parameters_builder. Similar to the stub above,
        // but for parameter injection instead of value substitution.
        $refprocreg = new \ReflectionClass($processorregistry);
        $refprop = $refprocreg->getProperty('processors');
        $refprop->setAccessible(true);
        $val = $refprop->getValue($processorregistry);
        $refprop->setValue($processorregistry, array_merge($val, [$ltixserviceprocessor::class => $ltixserviceprocessor]));

        $optionalparamsbuilderfactory = new parameters_builder_factory(
            $processorregistry,
            new custom_param_substitutor_factory(
                new variable_substitutor_factory($subservicestub)
            ),
            new custom_parameter_normaliser_factory(),
            new message_type_factory(
                $messagetyperegistry,
            )
        );

        $service = new v1p3_resource_link_launch_service(
            $optionalparamsbuilderfactory,
            new v1p3_resource_link_launch_request_builder(),
            $stubregrepo,
            $stublaunchdatarepo,
            $stubrolemap,
            new lis_vocab_converter(),
            new message_type_factory(
                di::get(message_type_registry::class),
            )
        );

        $message = $service->launch($resourcelink->get('id'), $user);

        $expectedurl = 'https://tool.example.com/lti/initiatelogin';

        $expectedparams = [
            'iss' => 'https://moodle-lms.institution.example.org',
            'target_link_uri' => 'https://tool.example.com/lti/resource/1',
            'login_hint' => '103000',
            'client_id' => 'ab345cd5678',
            'lti_deployment_id' => '44444',
        ];

        $expectedjwtclaims = [
            'tool_registration_id' => '44444',
            'iss' => 'https://moodle-lms.institution.example.org',
            'aud' => 'ab345cd5678',
            'https://purl.imsglobal.org/spec/lti/claim/message_type' => 'LtiResourceLinkRequest',
            'https://purl.imsglobal.org/spec/lti/claim/deployment_id' => '44444',
            'https://purl.imsglobal.org/spec/lti/claim/version' => '1.3.0',
            'https://purl.imsglobal.org/spec/lti/claim/resource_link' => [
                'id' => '24',
                'title' => 'Resource 1',
                'description' => 'A plain text description of resource 1',
            ],
            'https://purl.imsglobal.org/spec/lti/claim/target_link_uri' => 'https://tool.example.com/lti/resource/1',
            'https://purl.imsglobal.org/spec/lti/claim/roles' => [
                'http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor',
                'http://purl.imsglobal.org/vocab/lis/v2/system/person#Administrator',
                'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Administrator',
            ],
            'https://purl.imsglobal.org/spec/lti/claim/launch_presentation' => [
                'return_url' => 'https://www.example.com/moodle/ltix/return.php?course=100000&launch_container=3',
                'locale' => 'en',
                'document_target' => 'iframe',
            ],
            'https://purl.imsglobal.org/spec/lti/claim/context' => [
                'id' => '100000',
                'label' => 'tc_1',
                'title' => 'Test course 1',
                'type' => [
                    'http://purl.imsglobal.org/vocab/lis/v2/course#CourseSection',
                ],
            ],
            'https://purl.imsglobal.org/spec/lti/claim/tool_platform' => [
                'product_family_code' => 'moodle',
                'version' => $CFG->version,
                'guid' => 'www.example.com',
                'name' => 'phpunit',
                'description' => 'PHPUnit test site',
            ],
            'https://purl.imsglobal.org/spec/lti-bo/claim/basicoutcome' => [
                'lis_result_sourcedid' => '{"data":{"instanceid":24,"userid":"103000","typeid":44444,"launchid":534814373},"hash":"8fe71e1e082b30289c09aaa41e291c2ed5253793ed889a72ad4824baf1599309"}',
                'lis_outcome_service_url' => 'https://www.example.com/moodle/ltix/service.php',
            ],
            'https://purl.imsglobal.org/spec/lti/claim/lis' => [
                'course_section_sourcedid' => 'CID:4567',
                'person_sourcedid' => 'UID:U123',
            ],
            'https://purl.imsglobal.org/spec/lti/claim/ext' => [
                'user_username' => 'username1',
                'lms' => 'moodle-2',
            ],
            'https://purl.imsglobal.org/spec/lti-ags/claim/endpoint' => [
                'lineitems' => 'https://lms.example.com/context/2/lineitems',
                'lineitem' => 'https://lms.example.com/context/2/lineitem/10',
            ],
            'https://purl.imsglobal.org/spec/lti-nrps/claim/namesroleservice' => [
                'context_memberships_url' => 'https://lms.example.com/CourseSection/2/bindings/3/memberships',
                'service_versions' => [
                    '1.0',
                    '2.0',
                ]
            ],
            'https://purl.imsglobal.org/spec/lti/claim/custom' => [
                'tool_param_1' => 'value1',
                'tool_PARAM#2' => 'VALUE2',
                'tool_param_2' => 'VALUE2',
                'toolParam3' => 'username1',
                'toolparam3' => 'username1',
                'link_param_1' => 'linkval1',
                'linkParam2' => '$Person.name.full', // User var substitution is resolved at auth time in LTI 1.3.
                'linkparam2' => '$Person.name.full', // User var substitution is resolved at auth time in LTI 1.3.
                'linkParam#3' => '$Not.resolvable',
                'linkparam_3' => '$Not.resolvable',
                'context_memberships_url' => 'https://lms.example.com/CourseSection/2/bindings/3/memberships',
            ],
        ];

        // The message should be configured to be sent to the initiate login URL.
        $this->assertEquals($expectedurl, $message->get_url());

        // Message params match those expected to be sent to the initiate login URL.
        $messageparams = $message->get_parameters();
        $ltimessagehint = $messageparams['lti_message_hint'];
        unset($messageparams['lti_message_hint']);
        $this->assertEquals($expectedparams, $messageparams);

        // Grab the main payload from the hint JWT.
        $decodedltimessagehint = JWT::decode(
            $ltimessagehint,
            JWK::parseKeySet(jwks_helper::get_jwks())
        );
        $hintclaims = json_decode(json_encode($decodedltimessagehint), true); // Coerce into array format.

        // Check those non-deterministic params first, and offload them from the array.
        // Return URL contains sesskey, which changes, so compare the URL without that.
        $returnurl = new url($hintclaims['https://purl.imsglobal.org/spec/lti/claim/launch_presentation']['return_url']);
        $returnurl->remove_params(['sesskey']);
        $expectedurl = new url($expectedjwtclaims['https://purl.imsglobal.org/spec/lti/claim/launch_presentation']['return_url']);
        $expectedurl->remove_params(['sesskey']);
        $this->assertEquals($expectedurl, $returnurl);
        unset($expectedjwtclaims['https://purl.imsglobal.org/spec/lti/claim/launch_presentation']['return_url']);
        unset($hintclaims['https://purl.imsglobal.org/spec/lti/claim/launch_presentation']['return_url']);

        // Non-deterministic JWT OAuth 2.0 claims.
        $this->assertArrayHasKey('nonce', $hintclaims);
        $this->assertArrayHasKey('exp', $hintclaims);
        $this->assertArrayHasKey('iat', $hintclaims);
        $hintclaims = array_diff_key(
            $hintclaims,
            [
                'nonce' => null,
                'iat' => null,
                'exp' => null
            ]
        );

        // LIS result sourcedid (Basic outcomes) contains randomness, so just verify the predicatable part.
        $this->assertJson($hintclaims['https://purl.imsglobal.org/spec/lti-bo/claim/basicoutcome']['lis_result_sourcedid']);
        $lrs = json_decode($hintclaims['https://purl.imsglobal.org/spec/lti-bo/claim/basicoutcome']['lis_result_sourcedid']);
        $expectedlrs = json_decode($expectedjwtclaims['https://purl.imsglobal.org/spec/lti-bo/claim/basicoutcome']['lis_result_sourcedid']);
        $this->assertEquals($expectedlrs->data->userid, $lrs->data->userid);
        $this->assertEquals($expectedlrs->data->instanceid, $lrs->data->instanceid);
        $this->assertEquals($expectedlrs->data->typeid, $lrs->data->typeid);
        unset($hintclaims['https://purl.imsglobal.org/spec/lti-bo/claim/basicoutcome']['lis_result_sourcedid']);
        unset($expectedjwtclaims['https://purl.imsglobal.org/spec/lti-bo/claim/basicoutcome']['lis_result_sourcedid']);

        // Verify all other, deterministic parameters are present and correct.
        $this->assertEquals($expectedjwtclaims, $hintclaims);
    }
}
