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

namespace core_ltix\local\lticore\message\payload;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests covering lis_vocab_converter.
 *
 * @package    core_ltix
 * @copyright  2025 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(lis_vocab_converter::class)]
class lis_vocab_converter_test extends \basic_testcase {

    /**
     * Test covering to_v1_roles().
     *
     * @param array $inputexpectedmap the array containing input values (keys) and expected output values (values).
     * @return void
     */
    #[DataProvider('to_v1_roles_provider')]
    public function test_to_v1_roles(array $inputexpectedmap): void {
        $roles = array_keys($inputexpectedmap);
        $expected = array_values($inputexpectedmap);

        $vc = new lis_vocab_converter();
        $convertedroles = $vc->to_v1_roles($roles);

        $this->assertEquals($expected, $convertedroles);
    }

    /**
     * Data provider for testing to_v1_roles().
     *
     * @return array the test data.
     */
    public static function to_v1_roles_provider(): array {
        return [
            'Full v1 roles list' => [
                'inputexpectedmap' => [
                    // For v1 role handles, see:
                    // https://www.imsglobal.org/specs/ltiv1p1/implementation-guide#toc-8.
                    // System roles.
                    'urn:lti:sysrole:ims/lis/SysAdmin' => 'urn:lti:sysrole:ims/lis/SysAdmin',
                    'urn:lti:sysrole:ims/lis/SysSupport' => 'urn:lti:sysrole:ims/lis/SysSupport',
                    'urn:lti:sysrole:ims/lis/Creator' => 'urn:lti:sysrole:ims/lis/Creator',
                    'urn:lti:sysrole:ims/lis/AccountAdmin' => 'urn:lti:sysrole:ims/lis/AccountAdmin',
                    'urn:lti:sysrole:ims/lis/User' => 'urn:lti:sysrole:ims/lis/User',
                    'urn:lti:sysrole:ims/lis/Administrator' => 'urn:lti:sysrole:ims/lis/Administrator',
                    'urn:lti:sysrole:ims/lis/None' => 'urn:lti:sysrole:ims/lis/None',
                    // Institution roles.
                    'urn:lti:instrole:ims/lis/Student' => 'urn:lti:instrole:ims/lis/Student',
                    'urn:lti:instrole:ims/lis/Faculty' => 'urn:lti:instrole:ims/lis/Faculty',
                    'urn:lti:instrole:ims/lis/Member' => 'urn:lti:instrole:ims/lis/Member',
                    'urn:lti:instrole:ims/lis/Learner' => 'urn:lti:instrole:ims/lis/Learner',
                    'urn:lti:instrole:ims/lis/Instructor' => 'urn:lti:instrole:ims/lis/Instructor',
                    'urn:lti:instrole:ims/lis/Mentor' => 'urn:lti:instrole:ims/lis/Mentor',
                    'urn:lti:instrole:ims/lis/Staff' => 'urn:lti:instrole:ims/lis/Staff',
                    'urn:lti:instrole:ims/lis/Alumni' => 'urn:lti:instrole:ims/lis/Alumni',
                    'urn:lti:instrole:ims/lis/ProspectiveStudent' => 'urn:lti:instrole:ims/lis/ProspectiveStudent',
                    'urn:lti:instrole:ims/lis/Guest' => 'urn:lti:instrole:ims/lis/Guest',
                    'urn:lti:instrole:ims/lis/Other' => 'urn:lti:instrole:ims/lis/Other',
                    'urn:lti:instrole:ims/lis/Administrator' => 'urn:lti:instrole:ims/lis/Administrator',
                    'urn:lti:instrole:ims/lis/Observer' => 'urn:lti:instrole:ims/lis/Observer',
                    'urn:lti:instrole:ims/lis/None' => 'urn:lti:instrole:ims/lis/None',
                    // Context roles.
                    'urn:lti:role:ims/lis/Learner' => 'urn:lti:role:ims/lis/Learner',
                    'urn:lti:role:ims/lis/Instructor' => 'urn:lti:role:ims/lis/Instructor',
                    'urn:lti:role:ims/lis/ContentDeveloper' => 'urn:lti:role:ims/lis/ContentDeveloper',
                    'urn:lti:role:ims/lis/Member' => 'urn:lti:role:ims/lis/Member',
                    'urn:lti:role:ims/lis/Manager' => 'urn:lti:role:ims/lis/Manager',
                    'urn:lti:role:ims/lis/Mentor' => 'urn:lti:role:ims/lis/Mentor',
                    'urn:lti:role:ims/lis/Administrator' => 'urn:lti:role:ims/lis/Administrator',
                    'urn:lti:role:ims/lis/TeachingAssistant' => 'urn:lti:role:ims/lis/TeachingAssistant',
                    // Context sub-roles.
                    'urn:lti:role:ims/lis/Learner/Learner' => 'urn:lti:role:ims/lis/Learner/Learner',
                    'urn:lti:role:ims/lis/Learner/NonCreditLearner' => 'urn:lti:role:ims/lis/Learner/NonCreditLearner',
                    'urn:lti:role:ims/lis/Learner/GuestLearner' => 'urn:lti:role:ims/lis/Learner/GuestLearner',
                    'urn:lti:role:ims/lis/Learner/ExternalLearner' => 'urn:lti:role:ims/lis/Learner/ExternalLearner',
                    'urn:lti:role:ims/lis/Learner/Instructor' => 'urn:lti:role:ims/lis/Learner/Instructor',
                    'urn:lti:role:ims/lis/Instructor/PrimaryInstructor' => 'urn:lti:role:ims/lis/Instructor/PrimaryInstructor',
                    'urn:lti:role:ims/lis/Instructor/Lecturer' => 'urn:lti:role:ims/lis/Instructor/Lecturer',
                    'urn:lti:role:ims/lis/Instructor/GuestInstructor' => 'urn:lti:role:ims/lis/Instructor/GuestInstructor',
                    'urn:lti:role:ims/lis/Instructor/ExternalInstructor' => 'urn:lti:role:ims/lis/Instructor/ExternalInstructor',
                    'urn:lti:role:ims/lis/ContentDeveloper/ContentDeveloper' => 'urn:lti:role:ims/lis/ContentDeveloper/ContentDeveloper',
                    'urn:lti:role:ims/lis/ContentDeveloper/Librarian' => 'urn:lti:role:ims/lis/ContentDeveloper/Librarian',
                    'urn:lti:role:ims/lis/ContentDeveloper/ContentExpert' => 'urn:lti:role:ims/lis/ContentDeveloper/ContentExpert',
                    'urn:lti:role:ims/lis/ContentDeveloper/ExternalContentExpert' => 'urn:lti:role:ims/lis/ContentDeveloper/ExternalContentExpert',
                    'urn:lti:role:ims/lis/Member/Member' => 'urn:lti:role:ims/lis/Member/Member',
                    'urn:lti:role:ims/lis/Manager/AreaManager' => 'urn:lti:role:ims/lis/Manager/AreaManager',
                    'urn:lti:role:ims/lis/Manager/CourseCoordinator' => 'urn:lti:role:ims/lis/Manager/CourseCoordinator',
                    'urn:lti:role:ims/lis/Manager/Observer' => 'urn:lti:role:ims/lis/Manager/Observer',
                    'urn:lti:role:ims/lis/Manager/ExternalObserver' => 'urn:lti:role:ims/lis/Manager/ExternalObserver',
                    'urn:lti:role:ims/lis/Mentor/Mentor' => 'urn:lti:role:ims/lis/Mentor/Mentor',
                    'urn:lti:role:ims/lis/Mentor/Reviewer' => 'urn:lti:role:ims/lis/Mentor/Reviewer',
                    'urn:lti:role:ims/lis/Mentor/Advisor' => 'urn:lti:role:ims/lis/Mentor/Advisor',
                    'urn:lti:role:ims/lis/Mentor/Auditor' => 'urn:lti:role:ims/lis/Mentor/Auditor',
                    'urn:lti:role:ims/lis/Mentor/Tutor' => 'urn:lti:role:ims/lis/Mentor/Tutor',
                    'urn:lti:role:ims/lis/Mentor/LearningFacilitator' => 'urn:lti:role:ims/lis/Mentor/LearningFacilitator',
                    'urn:lti:role:ims/lis/Mentor/ExternalMentor' => 'urn:lti:role:ims/lis/Mentor/ExternalMentor',
                    'urn:lti:role:ims/lis/Mentor/ExternalReviewer' => 'urn:lti:role:ims/lis/Mentor/ExternalReviewer',
                    'urn:lti:role:ims/lis/Mentor/ExternalAdvisor' => 'urn:lti:role:ims/lis/Mentor/ExternalAdvisor',
                    'urn:lti:role:ims/lis/Mentor/ExternalAuditor' => 'urn:lti:role:ims/lis/Mentor/ExternalAuditor',
                    'urn:lti:role:ims/lis/Mentor/ExternalTutor' => 'urn:lti:role:ims/lis/Mentor/ExternalTutor',
                    'urn:lti:role:ims/lis/Mentor/ExternalLearningFacilitator' => 'urn:lti:role:ims/lis/Mentor/ExternalLearningFacilitator',
                    'urn:lti:role:ims/lis/Administrator/Administrator' => 'urn:lti:role:ims/lis/Administrator/Administrator',
                    'urn:lti:role:ims/lis/Administrator/Support' => 'urn:lti:role:ims/lis/Administrator/Support',
                    'urn:lti:role:ims/lis/Administrator/Developer' => 'urn:lti:role:ims/lis/Administrator/Developer',
                    'urn:lti:role:ims/lis/Administrator/SystemAdministrator' => 'urn:lti:role:ims/lis/Administrator/SystemAdministrator',
                    'urn:lti:role:ims/lis/Administrator/ExternalSystemAdministrator' => 'urn:lti:role:ims/lis/Administrator/ExternalSystemAdministrator',
                    'urn:lti:role:ims/lis/Administrator/ExternalDeveloper' => 'urn:lti:role:ims/lis/Administrator/ExternalDeveloper',
                    'urn:lti:role:ims/lis/Administrator/ExternalSupport' => 'urn:lti:role:ims/lis/Administrator/ExternalSupport',
                    'urn:lti:role:ims/lis/TeachingAssistant/TeachingAssistant' => 'urn:lti:role:ims/lis/TeachingAssistant/TeachingAssistant',
                    'urn:lti:role:ims/lis/TeachingAssistant/TeachingAssistantSection' => 'urn:lti:role:ims/lis/TeachingAssistant/TeachingAssistantSection',
                    'urn:lti:role:ims/lis/TeachingAssistant/TeachingAssistantSectionAssociation' => 'urn:lti:role:ims/lis/TeachingAssistant/TeachingAssistantSectionAssociation',
                    'urn:lti:role:ims/lis/TeachingAssistant/TeachingAssistantOffering' => 'urn:lti:role:ims/lis/TeachingAssistant/TeachingAssistantOffering',
                    'urn:lti:role:ims/lis/TeachingAssistant/TeachingAssistantTemplate' => 'urn:lti:role:ims/lis/TeachingAssistant/TeachingAssistantTemplate',
                    'urn:lti:role:ims/lis/TeachingAssistant/TeachingAssistantGroup' => 'urn:lti:role:ims/lis/TeachingAssistant/TeachingAssistantGroup',
                    'urn:lti:role:ims/lis/TeachingAssistant/Grader' => 'urn:lti:role:ims/lis/TeachingAssistant/Grader',
                ]
            ],
            'Full v2 roles list' => [
                'inputexpectedmap' => [
                    // System roles - core.
                    'http://purl.imsglobal.org/vocab/lis/v2/system/person#Administrator' => 'urn:lti:sysrole:ims/lis/Administrator',
                    'http://purl.imsglobal.org/vocab/lis/v2/system/person#None' => 'urn:lti:sysrole:ims/lis/None',
                    'http://purl.imsglobal.org/vocab/lis/v2/system/person#AccountAdmin' => 'urn:lti:sysrole:ims/lis/AccountAdmin',
                    'http://purl.imsglobal.org/vocab/lis/v2/system/person#Creator' => 'urn:lti:sysrole:ims/lis/Creator',
                    'http://purl.imsglobal.org/vocab/lis/v2/system/person#SysAdmin' => 'urn:lti:sysrole:ims/lis/SysAdmin',
                    'http://purl.imsglobal.org/vocab/lis/v2/system/person#SysSupport' => 'urn:lti:sysrole:ims/lis/SysSupport',
                    'http://purl.imsglobal.org/vocab/lis/v2/system/person#User' => 'urn:lti:sysrole:ims/lis/User',
                    // Institution roles.
                    'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Administrator' => 'urn:lti:instrole:ims/lis/Administrator',
                    'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Faculty' => 'urn:lti:instrole:ims/lis/Faculty',
                    'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Guest' => 'urn:lti:instrole:ims/lis/Guest',
                    'http://purl.imsglobal.org/vocab/lis/v2/institution/person#None' => 'urn:lti:instrole:ims/lis/None',
                    'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Other' => 'urn:lti:instrole:ims/lis/Other',
                    'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Staff' => 'urn:lti:instrole:ims/lis/Staff',
                    'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Student' => 'urn:lti:instrole:ims/lis/Student',
                    'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Alumni' => 'urn:lti:instrole:ims/lis/Alumni',
                    'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Instructor' => 'urn:lti:instrole:ims/lis/Instructor',
                    'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Learner' => 'urn:lti:instrole:ims/lis/Learner',
                    'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Member' => 'urn:lti:instrole:ims/lis/Member',
                    'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Mentor' => 'urn:lti:instrole:ims/lis/Mentor',
                    'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Observer' => 'urn:lti:instrole:ims/lis/Observer',
                    'http://purl.imsglobal.org/vocab/lis/v2/institution/person#ProspectiveStudent' => 'urn:lti:instrole:ims/lis/ProspectiveStudent',
                    // Context roles.
                    'http://purl.imsglobal.org/vocab/lis/v2/membership#Administrator' => 'urn:lti:role:ims/lis/Administrator',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership#ContentDeveloper' => 'urn:lti:role:ims/lis/ContentDeveloper',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor' => 'urn:lti:role:ims/lis/Instructor',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership#Learner' => 'urn:lti:role:ims/lis/Learner',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership#Mentor' => 'urn:lti:role:ims/lis/Mentor',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership#Manager' => 'urn:lti:role:ims/lis/Manager',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership#Member' => 'urn:lti:role:ims/lis/Member',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership#Officer' => null,
                    // Context sub-roles.
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Administrator#Administrator' => 'urn:lti:role:ims/lis/Administrator/Administrator',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Administrator#Developer' => 'urn:lti:role:ims/lis/Administrator/Developer',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Administrator#ExternalDeveloper' => 'urn:lti:role:ims/lis/Administrator/ExternalDeveloper',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Administrator#ExternalSupport' => 'urn:lti:role:ims/lis/Administrator/ExternalSupport',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Administrator#ExternalSystemAdministrator' => 'urn:lti:role:ims/lis/Administrator/ExternalSystemAdministrator',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Administrator#Support' => 'urn:lti:role:ims/lis/Administrator/Support',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Administrator#SystemAdministrator' => 'urn:lti:role:ims/lis/Administrator/SystemAdministrator',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/ContentDeveloper#ContentDeveloper' => 'urn:lti:role:ims/lis/ContentDeveloper/ContentDeveloper',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/ContentDeveloper#ContentExpert' => 'urn:lti:role:ims/lis/ContentDeveloper/ContentExpert',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/ContentDeveloper#ExternalContentExpert' => 'urn:lti:role:ims/lis/ContentDeveloper/ExternalContentExpert',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/ContentDeveloper#Librarian' => 'urn:lti:role:ims/lis/ContentDeveloper/Librarian',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#ExternalInstructor' => 'urn:lti:role:ims/lis/Instructor/ExternalInstructor',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#Grader' => 'urn:lti:role:ims/lis/TeachingAssistant/Grader',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#GuestInstructor' => 'urn:lti:role:ims/lis/Instructor/GuestInstructor',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#Lecturer' => 'urn:lti:role:ims/lis/Instructor/Lecturer',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#PrimaryInstructor' => 'urn:lti:role:ims/lis/Instructor/PrimaryInstructor',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#SecondaryInstructor' => 'urn:lti:role:ims/lis/TeachingAssistant',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistant' => 'urn:lti:role:ims/lis/TeachingAssistant/TeachingAssistant',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistantGroup' => 'urn:lti:role:ims/lis/TeachingAssistant/TeachingAssistantGroup',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistantOffering' => 'urn:lti:role:ims/lis/TeachingAssistant/TeachingAssistantOffering',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistantSection' => 'urn:lti:role:ims/lis/TeachingAssistant/TeachingAssistantSection',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistantSectionAssociation' => 'urn:lti:role:ims/lis/TeachingAssistant/TeachingAssistantSectionAssociation',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistantTemplate' => 'urn:lti:role:ims/lis/TeachingAssistant/TeachingAssistantTemplate',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Learner#ExternalLearner' => 'urn:lti:role:ims/lis/Learner/ExternalLearner',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Learner#GuestLearner' => 'urn:lti:role:ims/lis/Learner/GuestLearner',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Learner#Instructor' => 'urn:lti:role:ims/lis/Learner/Instructor',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Learner#Learner' => 'urn:lti:role:ims/lis/Learner/Learner',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Learner#NonCreditLearner' => 'urn:lti:role:ims/lis/Learner/NonCreditLearner',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Manager#AreaManager' => 'urn:lti:role:ims/lis/Manager/AreaManager',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Manager#CourseCoordinator' => 'urn:lti:role:ims/lis/Manager/CourseCoordinator',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Manager#ExternalObserver' => 'urn:lti:role:ims/lis/Manager/ExternalObserver',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Manager#Manager' => null,
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Manager#Observer' => 'urn:lti:role:ims/lis/Manager/Observer',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Member#Member' => 'urn:lti:role:ims/lis/Member/Member',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#Advisor' => 'urn:lti:role:ims/lis/Mentor/Advisor',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#Auditor' => 'urn:lti:role:ims/lis/Mentor/Auditor',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#ExternalAdvisor' => 'urn:lti:role:ims/lis/Mentor/ExternalAdvisor',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#ExternalAuditor' => 'urn:lti:role:ims/lis/Mentor/ExternalAuditor',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#ExternalLearningFacilitator' => 'urn:lti:role:ims/lis/Mentor/ExternalLearningFacilitator',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#ExternalMentor' => 'urn:lti:role:ims/lis/Mentor/ExternalMentor',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#ExternalReviewer' => 'urn:lti:role:ims/lis/Mentor/ExternalReviewer',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#ExternalTutor' => 'urn:lti:role:ims/lis/Mentor/ExternalTutor',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#LearningFacilitator' => 'urn:lti:role:ims/lis/Mentor/LearningFacilitator',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#Mentor' => 'urn:lti:role:ims/lis/Mentor/Mentor',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#Reviewer' => 'urn:lti:role:ims/lis/Mentor/Reviewer',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#Tutor' => 'urn:lti:role:ims/lis/Mentor/Tutor',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Officer#Chair' => null,
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Officer#Communications' => null,
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Officer#Secretary' => null,
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Officer#Treasurer' => null,
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Officer#Vice-Chair' => null,
                ],
            ],
            'LIS v1 simple names/context role handles' => [
                'inputexpectedmap' => [
                    // For v1 context role handles, see:
                    // https://www.imsglobal.org/specs/ltiv1p1/implementation-guide#toc-32.
                    'Learner' => 'urn:lti:role:ims/lis/Learner',
                    'Learner/Learner' => 'urn:lti:role:ims/lis/Learner/Learner',
                    'Learner/NonCreditLearner' => 'urn:lti:role:ims/lis/Learner/NonCreditLearner',
                    'Learner/GuestLearner' => 'urn:lti:role:ims/lis/Learner/GuestLearner',
                    'Learner/ExternalLearner' => 'urn:lti:role:ims/lis/Learner/ExternalLearner',
                    'Learner/Instructor' => 'urn:lti:role:ims/lis/Learner/Instructor',
                    'Instructor' => 'urn:lti:role:ims/lis/Instructor',
                    'Instructor/PrimaryInstructor' => 'urn:lti:role:ims/lis/Instructor/PrimaryInstructor',
                    'Instructor/Lecturer' => 'urn:lti:role:ims/lis/Instructor/Lecturer',
                    'Instructor/GuestInstructor' => 'urn:lti:role:ims/lis/Instructor/GuestInstructor',
                    'Instructor/ExternalInstructor' => 'urn:lti:role:ims/lis/Instructor/ExternalInstructor',
                    'ContentDeveloper' => 'urn:lti:role:ims/lis/ContentDeveloper',
                    'ContentDeveloper/ContentDeveloper' => 'urn:lti:role:ims/lis/ContentDeveloper/ContentDeveloper',
                    'ContentDeveloper/Librarian' => 'urn:lti:role:ims/lis/ContentDeveloper/Librarian',
                    'ContentDeveloper/ContentExpert' => 'urn:lti:role:ims/lis/ContentDeveloper/ContentExpert',
                    'ContentDeveloper/ExternalContentExpert' => 'urn:lti:role:ims/lis/ContentDeveloper/ExternalContentExpert',
                    'Member' => 'urn:lti:role:ims/lis/Member',
                    'Member/Member' => 'urn:lti:role:ims/lis/Member/Member',
                    'Manager' => 'urn:lti:role:ims/lis/Manager',
                    'Manager/AreaManager' => 'urn:lti:role:ims/lis/Manager/AreaManager',
                    'Manager/CourseCoordinator' => 'urn:lti:role:ims/lis/Manager/CourseCoordinator',
                    'Manager/Observer' => 'urn:lti:role:ims/lis/Manager/Observer',
                    'Manager/ExternalObserver' => 'urn:lti:role:ims/lis/Manager/ExternalObserver',
                    'Mentor' => 'urn:lti:role:ims/lis/Mentor',
                    'Mentor/Mentor' => 'urn:lti:role:ims/lis/Mentor/Mentor',
                    'Mentor/Reviewer' => 'urn:lti:role:ims/lis/Mentor/Reviewer',
                    'Mentor/Advisor' => 'urn:lti:role:ims/lis/Mentor/Advisor',
                    'Mentor/Auditor' => 'urn:lti:role:ims/lis/Mentor/Auditor',
                    'Mentor/Tutor' => 'urn:lti:role:ims/lis/Mentor/Tutor',
                    'Mentor/LearningFacilitator' => 'urn:lti:role:ims/lis/Mentor/LearningFacilitator',
                    'Mentor/ExternalMentor' => 'urn:lti:role:ims/lis/Mentor/ExternalMentor',
                    'Mentor/ExternalReviewer' => 'urn:lti:role:ims/lis/Mentor/ExternalReviewer',
                    'Mentor/ExternalAdvisor' => 'urn:lti:role:ims/lis/Mentor/ExternalAdvisor',
                    'Mentor/ExternalAuditor' => 'urn:lti:role:ims/lis/Mentor/ExternalAuditor',
                    'Mentor/ExternalTutor' => 'urn:lti:role:ims/lis/Mentor/ExternalTutor',
                    'Mentor/ExternalLearningFacilitator' => 'urn:lti:role:ims/lis/Mentor/ExternalLearningFacilitator',
                    'Administrator' => 'urn:lti:role:ims/lis/Administrator',
                    'Administrator/Administrator' => 'urn:lti:role:ims/lis/Administrator/Administrator',
                    'Administrator/Support' => 'urn:lti:role:ims/lis/Administrator/Support',
                    'Administrator/Developer' => 'urn:lti:role:ims/lis/Administrator/Developer',
                    'Administrator/SystemAdministrator' => 'urn:lti:role:ims/lis/Administrator/SystemAdministrator',
                    'Administrator/ExternalSystemAdministrator' => 'urn:lti:role:ims/lis/Administrator/ExternalSystemAdministrator',
                    'Administrator/ExternalDeveloper' => 'urn:lti:role:ims/lis/Administrator/ExternalDeveloper',
                    'Administrator/ExternalSupport' => 'urn:lti:role:ims/lis/Administrator/ExternalSupport',
                    'TeachingAssistant' => 'urn:lti:role:ims/lis/TeachingAssistant',
                    'TeachingAssistant/TeachingAssistant' => 'urn:lti:role:ims/lis/TeachingAssistant/TeachingAssistant',
                    'TeachingAssistant/TeachingAssistantSection' => 'urn:lti:role:ims/lis/TeachingAssistant/TeachingAssistantSection',
                    'TeachingAssistant/TeachingAssistantSectionAssociation' => 'urn:lti:role:ims/lis/TeachingAssistant/TeachingAssistantSectionAssociation',
                    'TeachingAssistant/TeachingAssistantOffering' => 'urn:lti:role:ims/lis/TeachingAssistant/TeachingAssistantOffering',
                    'TeachingAssistant/TeachingAssistantTemplate' => 'urn:lti:role:ims/lis/TeachingAssistant/TeachingAssistantTemplate',
                    'TeachingAssistant/TeachingAssistantGroup' => 'urn:lti:role:ims/lis/TeachingAssistant/TeachingAssistantGroup',
                    'TeachingAssistant/Grader' => 'urn:lti:role:ims/lis/TeachingAssistant/Grader',
                ]
            ],
            'Invalid role strings examples' => [
                'inputexpectedmap' => [
                    'invalid' => null,
                    null => null,
                    'TeachingAssistant/INVALID' => null,
                    'urn:lti:role:ims/lis/INVALID' => null,
                    'Learner#Learner' => null,
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Learner#INVALID' => null,
                ]
            ]
        ];
    }

    /**
     * Test covering role conversion to LISv2 vocabulary.
     *
     * @param array $inputexpectedmap the array containing input values (keys) and expected output values (values).
     * @param bool $usedeprecatedv2roleprefixes whether to convert to the deprecated role prefixes or not.
     * @return void
     */
    #[DataProvider('to_v2_roles_provider')]
    public function test_to_v2_roles(array $inputexpectedmap, bool $usedeprecatedv2roleprefixes = false): void {
        $roles = array_keys($inputexpectedmap);
        $expected = array_values($inputexpectedmap);

        $vc = new lis_vocab_converter();
        $convertedroles = $vc->to_v2_roles($roles, $usedeprecatedv2roleprefixes);

        $this->assertEquals($expected, $convertedroles);
    }

    /**
     * Data provider for testing role conversion to LIS v2 roles.
     *
     * @return array the test data.
     */
    public static function to_v2_roles_provider(): array {

        return [
            'Full list of v1 roles' => [
                'inputexpectedmap' => [
                    // System roles.
                    'urn:lti:sysrole:ims/lis/SysAdmin' => 'http://purl.imsglobal.org/vocab/lis/v2/system/person#SysAdmin',
                    'urn:lti:sysrole:ims/lis/SysSupport' => 'http://purl.imsglobal.org/vocab/lis/v2/system/person#SysSupport',
                    'urn:lti:sysrole:ims/lis/Creator' => 'http://purl.imsglobal.org/vocab/lis/v2/system/person#Creator',
                    'urn:lti:sysrole:ims/lis/AccountAdmin' => 'http://purl.imsglobal.org/vocab/lis/v2/system/person#AccountAdmin',
                    'urn:lti:sysrole:ims/lis/User' => 'http://purl.imsglobal.org/vocab/lis/v2/system/person#User',
                    'urn:lti:sysrole:ims/lis/Administrator' => 'http://purl.imsglobal.org/vocab/lis/v2/system/person#Administrator',
                    'urn:lti:sysrole:ims/lis/None' => 'http://purl.imsglobal.org/vocab/lis/v2/system/person#None',
                    // Institution roles.
                    'urn:lti:instrole:ims/lis/Student' => 'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Student',
                    'urn:lti:instrole:ims/lis/Faculty' => 'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Faculty',
                    'urn:lti:instrole:ims/lis/Member' => 'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Member',
                    'urn:lti:instrole:ims/lis/Learner' => 'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Learner',
                    'urn:lti:instrole:ims/lis/Instructor' => 'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Instructor',
                    'urn:lti:instrole:ims/lis/Mentor' => 'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Mentor',
                    'urn:lti:instrole:ims/lis/Staff' => 'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Staff',
                    'urn:lti:instrole:ims/lis/Alumni' => 'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Alumni',
                    'urn:lti:instrole:ims/lis/ProspectiveStudent' => 'http://purl.imsglobal.org/vocab/lis/v2/institution/person#ProspectiveStudent',
                    'urn:lti:instrole:ims/lis/Guest' => 'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Guest',
                    'urn:lti:instrole:ims/lis/Other' => 'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Other',
                    'urn:lti:instrole:ims/lis/Administrator' => 'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Administrator',
                    'urn:lti:instrole:ims/lis/Observer' => 'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Observer',
                    'urn:lti:instrole:ims/lis/None' => 'http://purl.imsglobal.org/vocab/lis/v2/institution/person#None',
                    // Context roles.
                    'urn:lti:role:ims/lis/Learner' => 'http://purl.imsglobal.org/vocab/lis/v2/membership#Learner',
                    'urn:lti:role:ims/lis/Instructor' => 'http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor',
                    'urn:lti:role:ims/lis/ContentDeveloper' => 'http://purl.imsglobal.org/vocab/lis/v2/membership#ContentDeveloper',
                    'urn:lti:role:ims/lis/Member' => 'http://purl.imsglobal.org/vocab/lis/v2/membership#Member',
                    'urn:lti:role:ims/lis/Manager' => 'http://purl.imsglobal.org/vocab/lis/v2/membership#Manager',
                    'urn:lti:role:ims/lis/Mentor' => 'http://purl.imsglobal.org/vocab/lis/v2/membership#Mentor',
                    'urn:lti:role:ims/lis/Administrator' => 'http://purl.imsglobal.org/vocab/lis/v2/membership#Administrator',
                    'urn:lti:role:ims/lis/TeachingAssistant' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#SecondaryInstructor',
                    // Context sub-roles.
                    'urn:lti:role:ims/lis/Learner/Learner' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Learner#Learner',
                    'urn:lti:role:ims/lis/Learner/NonCreditLearner' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Learner#NonCreditLearner',
                    'urn:lti:role:ims/lis/Learner/GuestLearner' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Learner#GuestLearner',
                    'urn:lti:role:ims/lis/Learner/ExternalLearner' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Learner#ExternalLearner',
                    'urn:lti:role:ims/lis/Learner/Instructor' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Learner#Instructor',
                    'urn:lti:role:ims/lis/Instructor/PrimaryInstructor' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#PrimaryInstructor',
                    'urn:lti:role:ims/lis/Instructor/Lecturer' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#Lecturer',
                    'urn:lti:role:ims/lis/Instructor/GuestInstructor' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#GuestInstructor',
                    'urn:lti:role:ims/lis/Instructor/ExternalInstructor' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#ExternalInstructor',
                    'urn:lti:role:ims/lis/ContentDeveloper/ContentDeveloper' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/ContentDeveloper#ContentDeveloper',
                    'urn:lti:role:ims/lis/ContentDeveloper/Librarian' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/ContentDeveloper#Librarian',
                    'urn:lti:role:ims/lis/ContentDeveloper/ContentExpert' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/ContentDeveloper#ContentExpert',
                    'urn:lti:role:ims/lis/ContentDeveloper/ExternalContentExpert' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/ContentDeveloper#ExternalContentExpert',
                    'urn:lti:role:ims/lis/Member/Member' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Member#Member',
                    'urn:lti:role:ims/lis/Manager/AreaManager' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Manager#AreaManager',
                    'urn:lti:role:ims/lis/Manager/CourseCoordinator' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Manager#CourseCoordinator',
                    'urn:lti:role:ims/lis/Manager/Observer' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Manager#Observer',
                    'urn:lti:role:ims/lis/Manager/ExternalObserver' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Manager#ExternalObserver',
                    'urn:lti:role:ims/lis/Mentor/Mentor' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#Mentor',
                    'urn:lti:role:ims/lis/Mentor/Reviewer' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#Reviewer',
                    'urn:lti:role:ims/lis/Mentor/Advisor' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#Advisor',
                    'urn:lti:role:ims/lis/Mentor/Auditor' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#Auditor',
                    'urn:lti:role:ims/lis/Mentor/Tutor' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#Tutor',
                    'urn:lti:role:ims/lis/Mentor/LearningFacilitator' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#LearningFacilitator',
                    'urn:lti:role:ims/lis/Mentor/ExternalMentor' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#ExternalMentor',
                    'urn:lti:role:ims/lis/Mentor/ExternalReviewer' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#ExternalReviewer',
                    'urn:lti:role:ims/lis/Mentor/ExternalAdvisor' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#ExternalAdvisor',
                    'urn:lti:role:ims/lis/Mentor/ExternalAuditor' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#ExternalAuditor',
                    'urn:lti:role:ims/lis/Mentor/ExternalTutor' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#ExternalTutor',
                    'urn:lti:role:ims/lis/Mentor/ExternalLearningFacilitator' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#ExternalLearningFacilitator',
                    'urn:lti:role:ims/lis/Administrator/Administrator' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Administrator#Administrator',
                    'urn:lti:role:ims/lis/Administrator/Support' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Administrator#Support',
                    'urn:lti:role:ims/lis/Administrator/Developer' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Administrator#Developer',
                    'urn:lti:role:ims/lis/Administrator/SystemAdministrator' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Administrator#SystemAdministrator',
                    'urn:lti:role:ims/lis/Administrator/ExternalSystemAdministrator' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Administrator#ExternalSystemAdministrator',
                    'urn:lti:role:ims/lis/Administrator/ExternalDeveloper' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Administrator#ExternalDeveloper',
                    'urn:lti:role:ims/lis/Administrator/ExternalSupport' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Administrator#ExternalSupport',
                    'urn:lti:role:ims/lis/TeachingAssistant/TeachingAssistant' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistant',
                    'urn:lti:role:ims/lis/TeachingAssistant/TeachingAssistantSection' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistantSection',
                    'urn:lti:role:ims/lis/TeachingAssistant/TeachingAssistantSectionAssociation' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistantSectionAssociation',
                    'urn:lti:role:ims/lis/TeachingAssistant/TeachingAssistantOffering' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistantOffering',
                    'urn:lti:role:ims/lis/TeachingAssistant/TeachingAssistantTemplate' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistantTemplate',
                    'urn:lti:role:ims/lis/TeachingAssistant/TeachingAssistantGroup' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistantGroup',
                    'urn:lti:role:ims/lis/TeachingAssistant/Grader' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#Grader',
                ],
            ],
            'Full list of v2 roles' => [
                'inputexpectedmap' => [
                    // System roles - core.
                    'http://purl.imsglobal.org/vocab/lis/v2/system/person#Administrator' => 'http://purl.imsglobal.org/vocab/lis/v2/system/person#Administrator',
                    'http://purl.imsglobal.org/vocab/lis/v2/system/person#None' => 'http://purl.imsglobal.org/vocab/lis/v2/system/person#None',
                    // System roles - non-core.
                    'http://purl.imsglobal.org/vocab/lis/v2/system/person#AccountAdmin' => 'http://purl.imsglobal.org/vocab/lis/v2/system/person#AccountAdmin',
                    'http://purl.imsglobal.org/vocab/lis/v2/system/person#Creator' => 'http://purl.imsglobal.org/vocab/lis/v2/system/person#Creator',
                    'http://purl.imsglobal.org/vocab/lis/v2/system/person#SysAdmin' => 'http://purl.imsglobal.org/vocab/lis/v2/system/person#SysAdmin',
                    'http://purl.imsglobal.org/vocab/lis/v2/system/person#SysSupport' => 'http://purl.imsglobal.org/vocab/lis/v2/system/person#SysSupport',
                    'http://purl.imsglobal.org/vocab/lis/v2/system/person#User' => 'http://purl.imsglobal.org/vocab/lis/v2/system/person#User',
                    // Institution roles - core.
                    'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Administrator' => 'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Administrator',
                    'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Faculty' => 'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Faculty',
                    'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Guest' => 'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Guest',
                    'http://purl.imsglobal.org/vocab/lis/v2/institution/person#None' => 'http://purl.imsglobal.org/vocab/lis/v2/institution/person#None',
                    'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Other' => 'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Other',
                    'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Staff' => 'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Staff',
                    'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Student' => 'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Student',
                    // Institution roles - non-core.
                    'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Alumni' => 'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Alumni',
                    'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Instructor' => 'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Instructor',
                    'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Learner' => 'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Learner',
                    'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Member' => 'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Member',
                    'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Mentor' => 'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Mentor',
                    'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Observer' => 'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Observer',
                    'http://purl.imsglobal.org/vocab/lis/v2/institution/person#ProspectiveStudent' => 'http://purl.imsglobal.org/vocab/lis/v2/institution/person#ProspectiveStudent',
                    // Context roles - core.
                    'http://purl.imsglobal.org/vocab/lis/v2/membership#Administrator' => 'http://purl.imsglobal.org/vocab/lis/v2/membership#Administrator',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership#ContentDeveloper' => 'http://purl.imsglobal.org/vocab/lis/v2/membership#ContentDeveloper',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor' => 'http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership#Learner' => 'http://purl.imsglobal.org/vocab/lis/v2/membership#Learner',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership#Mentor' => 'http://purl.imsglobal.org/vocab/lis/v2/membership#Mentor',
                    // Context roles - non-core.
                    'http://purl.imsglobal.org/vocab/lis/v2/membership#Manager' => 'http://purl.imsglobal.org/vocab/lis/v2/membership#Manager',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership#Member' => 'http://purl.imsglobal.org/vocab/lis/v2/membership#Member',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership#Officer' => 'http://purl.imsglobal.org/vocab/lis/v2/membership#Officer',
                    // Context sub-roles.
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Administrator#Administrator' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Administrator#Administrator',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Administrator#Developer' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Administrator#Developer',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Administrator#ExternalDeveloper' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Administrator#ExternalDeveloper',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Administrator#ExternalSupport' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Administrator#ExternalSupport',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Administrator#ExternalSystemAdministrator' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Administrator#ExternalSystemAdministrator',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Administrator#Support' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Administrator#Support',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Administrator#SystemAdministrator' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Administrator#SystemAdministrator',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/ContentDeveloper#ContentDeveloper' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/ContentDeveloper#ContentDeveloper',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/ContentDeveloper#ContentExpert' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/ContentDeveloper#ContentExpert',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/ContentDeveloper#ExternalContentExpert' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/ContentDeveloper#ExternalContentExpert',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/ContentDeveloper#Librarian' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/ContentDeveloper#Librarian',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#ExternalInstructor' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#ExternalInstructor',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#Grader' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#Grader',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#GuestInstructor' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#GuestInstructor',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#Lecturer' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#Lecturer',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#PrimaryInstructor' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#PrimaryInstructor',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#SecondaryInstructor' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#SecondaryInstructor',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistant' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistant',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistantGroup' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistantGroup',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistantOffering' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistantOffering',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistantSection' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistantSection',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistantSectionAssociation' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistantSectionAssociation',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistantTemplate' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistantTemplate',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Learner#ExternalLearner' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Learner#ExternalLearner',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Learner#GuestLearner' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Learner#GuestLearner',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Learner#Instructor' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Learner#Instructor',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Learner#Learner' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Learner#Learner',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Learner#NonCreditLearner' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Learner#NonCreditLearner',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Manager#AreaManager' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Manager#AreaManager',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Manager#CourseCoordinator' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Manager#CourseCoordinator',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Manager#ExternalObserver' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Manager#ExternalObserver',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Manager#Manager' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Manager#Manager',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Manager#Observer' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Manager#Observer',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Member#Member' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Member#Member',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#Advisor' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#Advisor',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#Auditor' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#Auditor',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#ExternalAdvisor' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#ExternalAdvisor',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#ExternalAuditor' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#ExternalAuditor',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#ExternalLearningFacilitator' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#ExternalLearningFacilitator',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#ExternalMentor' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#ExternalMentor',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#ExternalReviewer' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#ExternalReviewer',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#ExternalTutor' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#ExternalTutor',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#LearningFacilitator' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#LearningFacilitator',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#Mentor' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#Mentor',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#Reviewer' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#Reviewer',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#Tutor' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#Tutor',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Officer#Chair' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Officer#Chair',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Officer#Communications' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Officer#Communications',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Officer#Secretary' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Officer#Secretary',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Officer#Treasurer' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Officer#Treasurer',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Officer#Vice-Chair' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Officer#Vice-Chair',
                ],
            ],
            'Full list of v1 simple names/ context role handles' => [
                'inputexpectedmap' => [
                    'Learner' => 'http://purl.imsglobal.org/vocab/lis/v2/membership#Learner',
                    'Learner/Learner' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Learner#Learner',
                    'Learner/NonCreditLearner' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Learner#NonCreditLearner',
                    'Learner/GuestLearner' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Learner#GuestLearner',
                    'Learner/ExternalLearner' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Learner#ExternalLearner',
                    'Learner/Instructor' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Learner#Instructor',
                    'Instructor' => 'http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor',
                    'Instructor/PrimaryInstructor' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#PrimaryInstructor',
                    'Instructor/Lecturer' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#Lecturer',
                    'Instructor/GuestInstructor' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#GuestInstructor',
                    'Instructor/ExternalInstructor' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#ExternalInstructor',
                    'ContentDeveloper' => 'http://purl.imsglobal.org/vocab/lis/v2/membership#ContentDeveloper',
                    'ContentDeveloper/ContentDeveloper' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/ContentDeveloper#ContentDeveloper',
                    'ContentDeveloper/Librarian' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/ContentDeveloper#Librarian',
                    'ContentDeveloper/ContentExpert' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/ContentDeveloper#ContentExpert',
                    'ContentDeveloper/ExternalContentExpert' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/ContentDeveloper#ExternalContentExpert',
                    'Member' => 'http://purl.imsglobal.org/vocab/lis/v2/membership#Member',
                    'Member/Member' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Member#Member',
                    'Manager' => 'http://purl.imsglobal.org/vocab/lis/v2/membership#Manager',
                    'Manager/AreaManager' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Manager#AreaManager',
                    'Manager/CourseCoordinator' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Manager#CourseCoordinator',
                    'Manager/Observer' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Manager#Observer',
                    'Manager/ExternalObserver' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Manager#ExternalObserver',
                    'Mentor' => 'http://purl.imsglobal.org/vocab/lis/v2/membership#Mentor',
                    'Mentor/Mentor' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#Mentor',
                    'Mentor/Reviewer' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#Reviewer',
                    'Mentor/Advisor' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#Advisor',
                    'Mentor/Auditor' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#Auditor',
                    'Mentor/Tutor' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#Tutor',
                    'Mentor/LearningFacilitator' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#LearningFacilitator',
                    'Mentor/ExternalMentor' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#ExternalMentor',
                    'Mentor/ExternalReviewer' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#ExternalReviewer',
                    'Mentor/ExternalAdvisor' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#ExternalAdvisor',
                    'Mentor/ExternalAuditor' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#ExternalAuditor',
                    'Mentor/ExternalTutor' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#ExternalTutor',
                    'Mentor/ExternalLearningFacilitator' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Mentor#ExternalLearningFacilitator',
                    'Administrator' => 'http://purl.imsglobal.org/vocab/lis/v2/membership#Administrator',
                    'Administrator/Administrator' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Administrator#Administrator',
                    'Administrator/Support' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Administrator#Support',
                    'Administrator/Developer' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Administrator#Developer',
                    'Administrator/SystemAdministrator' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Administrator#SystemAdministrator',
                    'Administrator/ExternalSystemAdministrator' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Administrator#ExternalSystemAdministrator',
                    'Administrator/ExternalDeveloper' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Administrator#ExternalDeveloper',
                    'Administrator/ExternalSupport' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Administrator#ExternalSupport',
                    'TeachingAssistant' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#SecondaryInstructor',
                    'TeachingAssistant/TeachingAssistant' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistant',
                    'TeachingAssistant/TeachingAssistantSection' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistantSection',
                    'TeachingAssistant/TeachingAssistantSectionAssociation' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistantSectionAssociation',
                    'TeachingAssistant/TeachingAssistantOffering' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistantOffering',
                    'TeachingAssistant/TeachingAssistantTemplate' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistantTemplate',
                    'TeachingAssistant/TeachingAssistantGroup' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistantGroup',
                    'TeachingAssistant/Grader' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#Grader',
                ],
            ],
            'A list of invalid role strings and other unmappable types' => [
                'inputexpectedmap' => [
                    'invalid' => null,
                    null => null,
                    'urn:lti:role:ims/lis/INVALID' => null,
                    'Learner#Learner' => null,
                    'http://purl.imsglobal.org/vocab/lis/v2/membership#INVALID' => null,
                    'http://purl.imsglobal.org/vocab/lis/v2/membership/Learner#INVALID' => null,
                ],
            ],
            'Test using the deprecated v2 role prefixes' => [
                'inputexpectedmap' => [
                    // System roles. Note the lack of the 'system' in the path prefix.
                    'http://purl.imsglobal.org/vocab/lis/v2/system/person#Administrator' => 'http://purl.imsglobal.org/vocab/lis/v2/person#Administrator',
                    'http://purl.imsglobal.org/vocab/lis/v2/system/person#None' => 'http://purl.imsglobal.org/vocab/lis/v2/person#None',
                    'http://purl.imsglobal.org/vocab/lis/v2/system/person#AccountAdmin' => 'http://purl.imsglobal.org/vocab/lis/v2/person#AccountAdmin',
                    'http://purl.imsglobal.org/vocab/lis/v2/system/person#Creator' => 'http://purl.imsglobal.org/vocab/lis/v2/person#Creator',
                    'http://purl.imsglobal.org/vocab/lis/v2/system/person#SysAdmin' => 'http://purl.imsglobal.org/vocab/lis/v2/person#SysAdmin',
                    'http://purl.imsglobal.org/vocab/lis/v2/system/person#SysSupport' => 'http://purl.imsglobal.org/vocab/lis/v2/person#SysSupport',
                    'http://purl.imsglobal.org/vocab/lis/v2/system/person#User' => 'http://purl.imsglobal.org/vocab/lis/v2/person#User',
                    // Institution roles. Note the lack of the 'institution' in the path prefix.
                    'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Administrator' => 'http://purl.imsglobal.org/vocab/lis/v2/person#Administrator',
                    'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Faculty' => 'http://purl.imsglobal.org/vocab/lis/v2/person#Faculty',
                    'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Guest' => 'http://purl.imsglobal.org/vocab/lis/v2/person#Guest',
                    'http://purl.imsglobal.org/vocab/lis/v2/institution/person#None' => 'http://purl.imsglobal.org/vocab/lis/v2/person#None',
                    'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Other' => 'http://purl.imsglobal.org/vocab/lis/v2/person#Other',
                    'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Staff' => 'http://purl.imsglobal.org/vocab/lis/v2/person#Staff',
                    'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Student' => 'http://purl.imsglobal.org/vocab/lis/v2/person#Student',
                    'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Alumni' => 'http://purl.imsglobal.org/vocab/lis/v2/person#Alumni',
                    'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Instructor' => 'http://purl.imsglobal.org/vocab/lis/v2/person#Instructor',
                    'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Learner' => 'http://purl.imsglobal.org/vocab/lis/v2/person#Learner',
                    'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Member' => 'http://purl.imsglobal.org/vocab/lis/v2/person#Member',
                    'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Mentor' => 'http://purl.imsglobal.org/vocab/lis/v2/person#Mentor',
                    'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Observer' => 'http://purl.imsglobal.org/vocab/lis/v2/person#Observer',
                    'http://purl.imsglobal.org/vocab/lis/v2/institution/person#ProspectiveStudent' => 'http://purl.imsglobal.org/vocab/lis/v2/person#ProspectiveStudent',
                    // Context roles. Nothing changed.
                    'http://purl.imsglobal.org/vocab/lis/v2/membership#Administrator' => 'http://purl.imsglobal.org/vocab/lis/v2/membership#Administrator',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership#ContentDeveloper' => 'http://purl.imsglobal.org/vocab/lis/v2/membership#ContentDeveloper',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor' => 'http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership#Learner' => 'http://purl.imsglobal.org/vocab/lis/v2/membership#Learner',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership#Mentor' => 'http://purl.imsglobal.org/vocab/lis/v2/membership#Mentor',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership#Manager' => 'http://purl.imsglobal.org/vocab/lis/v2/membership#Manager',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership#Member' => 'http://purl.imsglobal.org/vocab/lis/v2/membership#Member',
                    'http://purl.imsglobal.org/vocab/lis/v2/membership#Officer' => 'http://purl.imsglobal.org/vocab/lis/v2/membership#Officer',
                ],
                'usedeprecatedv2roleprefixes' => true,
            ]
        ];
    }

    /**
     * Test covering to_v1_context_types().
     *
     * @param array $inputexpectedmap the array containing input values (keys) and expected output values (values).
     * @return void
     */
    #[DataProvider('to_v1_context_types_provider')]
    public function test_to_v1_context_types(array $inputexpectedmap) {
        $contexttypes = array_keys($inputexpectedmap);
        $expected = array_values($inputexpectedmap);

        $vc = new lis_vocab_converter();
        $convertedcontexttypes = $vc->to_v1_context_types($contexttypes);

        $this->assertEquals($expected, $convertedcontexttypes);
    }

    /**
     * Data provider for testing to_v2_context_types().
     *
     * @return array the test data.
     */
    public static function to_v1_context_types_provider(): array {
        return [
            'Full list of v1 context types' => [
                'inputexpectedmap' => [
                    'urn:lti:context-type:ims/lis/CourseTemplate' => 'urn:lti:context-type:ims/lis/CourseTemplate',
                    'urn:lti:context-type:ims/lis/CourseOffering' => 'urn:lti:context-type:ims/lis/CourseOffering',
                    'urn:lti:context-type:ims/lis/CourseSection' => 'urn:lti:context-type:ims/lis/CourseSection',
                    'urn:lti:context-type:ims/lis/Group' => 'urn:lti:context-type:ims/lis/Group',
                ],
            ],
            'Full list of v2 context types' => [
                'inputexpectedmap' => [
                    'http://purl.imsglobal.org/vocab/lis/v2/course#CourseTemplate' => 'urn:lti:context-type:ims/lis/CourseTemplate',
                    'http://purl.imsglobal.org/vocab/lis/v2/course#CourseOffering' => 'urn:lti:context-type:ims/lis/CourseOffering',
                    'http://purl.imsglobal.org/vocab/lis/v2/course#CourseSection' => 'urn:lti:context-type:ims/lis/CourseSection',
                    'http://purl.imsglobal.org/vocab/lis/v2/course#Group' => 'urn:lti:context-type:ims/lis/Group',
                ],
            ],
            'Full list of v1 context simple names' => [
                'inputexpectedmap' => [
                    'CourseTemplate' => 'urn:lti:context-type:ims/lis/CourseTemplate',
                    'CourseOffering' => 'urn:lti:context-type:ims/lis/CourseOffering',
                    'CourseSection' => 'urn:lti:context-type:ims/lis/CourseSection',
                    'Group' => 'urn:lti:context-type:ims/lis/Group',
                ],
            ],
            'Invalid examples' => [
                'inputexpectedmap' => [
                    null => null,
                    '' => null,
                    'notfound' => null,
                ],
            ],
            'Duplicates in results' => [
                'inputexpectedmap' => [
                    'CourseTemplate' => 'urn:lti:context-type:ims/lis/CourseTemplate',
                    'CourseTemplate' => 'urn:lti:context-type:ims/lis/CourseTemplate',
                ],
            ],
        ];
    }

    /**
     * Test covering to_v2_context_types().
     *
     * @param array $inputexpectedmap the array containing input values (keys) and expected output values (values).
     * @return void
     */
    #[DataProvider('to_v2_context_types_provider')]
    public function test_to_v2_context_types(array $inputexpectedmap) {
        $contexttypes = array_keys($inputexpectedmap);
        $expected = array_values($inputexpectedmap);

        $vc = new lis_vocab_converter();
        $convertedcontexttypes = $vc->to_v2_context_types($contexttypes);

        $this->assertEquals($expected, $convertedcontexttypes);
    }

    /**
     * Data provider for testing to_v2_context_types().
     *
     * @return array the test data.
     */
    public static function to_v2_context_types_provider(): array {
        return [
            'Full list of v1 context types' => [
                'inputexpectedmap' => [
                    'urn:lti:context-type:ims/lis/CourseTemplate' => 'http://purl.imsglobal.org/vocab/lis/v2/course#CourseTemplate',
                    'urn:lti:context-type:ims/lis/CourseOffering' => 'http://purl.imsglobal.org/vocab/lis/v2/course#CourseOffering',
                    'urn:lti:context-type:ims/lis/CourseSection' => 'http://purl.imsglobal.org/vocab/lis/v2/course#CourseSection',
                    'urn:lti:context-type:ims/lis/Group' => 'http://purl.imsglobal.org/vocab/lis/v2/course#Group',
                ],
            ],
            'Full list of v2 context types' => [
                'inputexpectedmap' => [
                    'http://purl.imsglobal.org/vocab/lis/v2/course#CourseTemplate' => 'http://purl.imsglobal.org/vocab/lis/v2/course#CourseTemplate',
                    'http://purl.imsglobal.org/vocab/lis/v2/course#CourseOffering' => 'http://purl.imsglobal.org/vocab/lis/v2/course#CourseOffering',
                    'http://purl.imsglobal.org/vocab/lis/v2/course#CourseSection' => 'http://purl.imsglobal.org/vocab/lis/v2/course#CourseSection',
                    'http://purl.imsglobal.org/vocab/lis/v2/course#Group' => 'http://purl.imsglobal.org/vocab/lis/v2/course#Group',
                ],
            ],
            'Full list of v1 context simple names' => [
                'inputexpectedmap' => [
                    'CourseTemplate' => 'http://purl.imsglobal.org/vocab/lis/v2/course#CourseTemplate',
                    'CourseOffering' => 'http://purl.imsglobal.org/vocab/lis/v2/course#CourseOffering',
                    'CourseSection' => 'http://purl.imsglobal.org/vocab/lis/v2/course#CourseSection',
                    'Group' => 'http://purl.imsglobal.org/vocab/lis/v2/course#Group',
                ],
            ],
            'Invalid examples' => [
                'inputexpectedmap' => [
                    null => null,
                    '' => null,
                    'notfound' => null,
                ],
            ],
            'Duplicates in results' => [
                'inputexpectedmap' => [
                    'CourseTemplate' => 'http://purl.imsglobal.org/vocab/lis/v2/course#CourseTemplate',
                    'CourseTemplate' => 'http://purl.imsglobal.org/vocab/lis/v2/course#CourseTemplate',
                ],
            ],
        ];
    }
}
