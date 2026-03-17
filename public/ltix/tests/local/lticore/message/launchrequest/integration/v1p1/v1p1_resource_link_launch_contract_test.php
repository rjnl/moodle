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

namespace core_ltix\local\lticore\message\launchrequest\integration\v1p1;

use core\di;
use core\url;
use core_ltix\constants;
use core_ltix\local\lticore\lti_version;
use core_ltix\local\lticore\message\context\collection\launch_context;
use core_ltix\local\lticore\message\launchrequest\builder\v1p1\v1p1_resource_link_launch_request_builder;
use core_ltix\local\lticore\message\launchrequest\role_mapper;
use core_ltix\local\lticore\message\launchrequest\service\datarepository\launch_data_repository;
use core_ltix\local\lticore\message\launchrequest\service\v1p1\v1p1_resource_link_launch_service;
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
use core_ltix\local\ltiservice\plugin_parameters_service_interface;
use core_ltix\local\ltiservice\plugin_substitution_service_interface;
use core_ltix\OAuthRequest;
use core_ltix\OAuthServer;
use core_ltix\OAuthSignatureMethod_HMAC_SHA1;
use core_ltix\TrivialOAuthDataStore;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Contract tests for LTI 1.1 resource link launch requests.
 *
 * This simulates a real use of the service:launch() and verifies it generates a complete, correctly signed payload.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(v1p1_resource_link_launch_service::class)]
class v1p1_resource_link_launch_contract_test extends \basic_testcase {

    /**
     * Test a typical launch generates a signed LTI 1.1 launch request.
     * @return void
     */
    public function test_complete_launch_matches_expected(): void {
        global $CFG;

        $toolconfig = (object) [
            'tool' => (object) [
                'id' => '44444',
                'baseurl' => 'https://tool.example.com',
                'ltiversion' => lti_version::LTI_VERSION_1->value,
            ],
            'config' => (object) [
                'launchcontainer' => constants::LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS,
                'customparameters' => "tool_param_1=value1\ntool_PARAM#2=VALUE2\ntoolParam3=\$User.username",
                'sendname' => constants::LTI_SETTING_ALWAYS,
                'sendemailaddr' => constants::LTI_SETTING_ALWAYS,
                'acceptgrades' => constants::LTI_SETTING_ALWAYS,
                'resourcekey' => 'CONSUMER_KEY',
                'password' => 'ApEPxhMpZQxA9bPWXLVtnpvkNrcuoe16',
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
            'urn:lti:sysrole:ims/lis/Administrator',
            'urn:lti:instrole:ims/lis/Administrator'
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
                    'custom_gradebookservices_scope' => 'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem.readonly,https://purl.imsglobal.org/spec/lti-ags/scope/result.readonly,https://purl.imsglobal.org/spec/lti-ags/scope/score,https://purl.imsglobal.org/spec/lti-ags/scope/lineitem',
                    'custom_lineitems_url' => '$LineItems.url',
                    'custom_lineitem_url' => '$LineItem.url',
                    'custom_context_memberships_url' => '$ToolProxyBinding.memberships.url',
                    'custom_context_memberships_v2_url' => '$ToolProxyBinding.memberships.url',
                    'custom_context_memberships_versions' => '1.0,2.0',
                ];
            });
        $ltixserviceprocessor = new ltixservice_resolver($parameterservicestub);

        // Registries are currently assembled in the DI container.
        // For now, just use DI to fetch those two things.
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

        $service = new v1p1_resource_link_launch_service(
            $optionalparamsbuilderfactory,
            new v1p1_resource_link_launch_request_builder(),
            $stubregrepo,
            $stublaunchdatarepo,
            $stubrolemap,
            new lis_vocab_converter(),
            new message_type_factory(
                di::get(message_type_registry::class),
            )
        );

        $message = $service->launch($resourcelink->get('id'), $user);

        $expectedurl = 'https://tool.example.com/lti/resource/1';

        // Note the following peculiarities with the expected payload:
        // 1. context_type: included, but NOT controlled by an LTI 2.0 capability. This is at odds with the other context_*
        // vars which ARE controlled by caps. This differs to legacy behaviour where that was omitted, but is more correct.
        // 2. ext_user_username: included, but NOT controlled by an LTI 2.0 capability. This is different to the legacy
        // behaviour, but again is more correct since extension params shouldn't be arbitrarily dropped.
        $expectedparams = [
            'lti_version' => lti_version::LTI_VERSION_1->value,
            'lti_message_type' => 'basic-lti-launch-request',
            'resource_link_id' => '24',
            'resource_link_title' => 'Resource 1',
            'resource_link_description' => 'A plain text description of resource 1',
            'roles' => 'urn:lti:role:ims/lis/Instructor,urn:lti:sysrole:ims/lis/Administrator,urn:lti:instrole:ims/lis/Administrator',
            'user_id' => '103000',
            'lis_person_name_full' => '美羽 斎藤',
            'lis_person_name_given' => '美羽',
            'lis_person_name_family' => '斎藤',
            'lis_person_contact_email_primary' => 'username1@example.com',
            'lis_person_sourcedid' => 'UID:U123',
            'context_id' => '100000',
            'context_title' => 'Test course 1',
            'context_label' => 'tc_1',
            'context_type' => 'CourseSection',
            'launch_presentation_locale' => 'en',
            'launch_presentation_document_target' => 'iframe',
            'launch_presentation_return_url' => 'https://www.example.com/moodle/ltix/return.php?course=100000&launch_container=3',
            'tool_consumer_info_product_family_code' => 'moodle',
            'tool_consumer_info_version' => $CFG->version,
            'tool_consumer_instance_guid' => 'www.example.com',
            'tool_consumer_instance_name' => 'phpunit',
            'tool_consumer_instance_description' => 'PHPUnit test site',
            'ext_user_username' => 'username1',
            'ext_lms' => 'moodle-2',
            'lis_result_sourcedid' => '{"data":{"instanceid":24,"userid":"103000","typeid":44444,"launchid":534814373},"hash":"8fe71e1e082b30289c09aaa41e291c2ed5253793ed889a72ad4824baf1599309"}',
            'lis_outcome_service_url' => 'https://www.example.com/moodle/ltix/service.php',
            'lis_course_section_sourcedid' => 'CID:4567',
            'custom_tool_param_1' => 'value1',
            'custom_tool_param_2' => 'VALUE2',
            'custom_toolparam3' => 'username1',
            'custom_link_param_1' => 'linkval1',
            'custom_linkparam2' => '美羽 斎藤',
            'custom_linkparam_3' => '$Not.resolvable',
            'custom_gradebookservices_scope' => 'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem.readonly,https://purl.imsglobal.org/spec/lti-ags/scope/result.readonly,https://purl.imsglobal.org/spec/lti-ags/scope/score,https://purl.imsglobal.org/spec/lti-ags/scope/lineitem',
            'custom_lineitems_url' => 'https://lms.example.com/context/2/lineitems',
            'custom_lineitem_url' => 'https://lms.example.com/context/2/lineitem/10',
            'custom_context_memberships_url' => 'https://lms.example.com/CourseSection/2/bindings/3/memberships',
            'custom_context_memberships_v2_url' => 'https://lms.example.com/CourseSection/2/bindings/3/memberships',
            'custom_context_memberships_versions' => '1.0,2.0',
        ];

        // The message should be configured to be sent to the launch URL.
        $this->assertEquals($expectedurl, $message->get_url());

        // The message should contain OAuth signed parameters.
        $messageparams = $message->get_parameters();

        // Verify the OAuth signature is valid.
        $store = new TrivialOAuthDataStore();
        $store->add_consumer($toolconfig->config->resourcekey, $toolconfig->config->password);
        $server = new OAuthServer($store);
        $method = new OAuthSignatureMethod_HMAC_SHA1();
        $server->add_signature_method($method);
        $request = new OAuthRequest('POST', $message->get_url(), $messageparams);
        // Note: verification will throw if the signature is invalid.
        $verification = $server->verify_request($request);
        $this->assertIsArray($verification);

        // Strip OAuth-specific parameters for comparison of the LTI-specific parameters.
        $nonoauthparams = array_diff_key(
            $messageparams,
            [
                'oauth_version' => null,
                'oauth_nonce' => null,
                'oauth_timestamp' => null,
                'oauth_consumer_key' => null,
                'oauth_signature_method' => null,
                'oauth_signature' => null,
                'oauth_callback' => null,
            ]
        );

        // Return URL contains sesskey, which changes, so compare the URL without that.
        $returnurl = new url($nonoauthparams['launch_presentation_return_url']);
        $returnurl->remove_params(['sesskey']);
        $expectedurl = new url($expectedparams['launch_presentation_return_url']);
        $expectedurl->remove_params(['sesskey']);
        $this->assertEquals($expectedurl, $returnurl);
        unset($nonoauthparams['launch_presentation_return_url']);
        unset($expectedparams['launch_presentation_return_url']);

        // LIS result sourcedid (Basic outcomes) contains randomness, so just assert JSON + the predicatable structure.
        $this->assertJson($nonoauthparams['lis_result_sourcedid']);
        $lrs = json_decode($nonoauthparams['lis_result_sourcedid']);
        $expectedlrs = json_decode($expectedparams['lis_result_sourcedid']);
        $this->assertEquals($expectedlrs->data->userid, $lrs->data->userid);
        $this->assertEquals($expectedlrs->data->instanceid, $lrs->data->instanceid);
        $this->assertEquals($expectedlrs->data->typeid, $lrs->data->typeid);
        unset($nonoauthparams['lis_result_sourcedid']);
        unset($expectedparams['lis_result_sourcedid']);

        // Verify all other, deterministic parameters are present and correct.
        $this->assertEquals($expectedparams, $nonoauthparams);
    }
}
