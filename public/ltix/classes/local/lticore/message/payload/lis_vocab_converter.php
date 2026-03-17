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

/**
 * Class supporting conversion of LIS vocabulary.
 *
 * Supports conversion between the LIS v1 vocabulary and LIS v2 vocabulary, for LIS:
 * - Context types
 * - Roles
 *
 * @package    core_ltix
 * @copyright  2025 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class lis_vocab_converter {

    /** @var string The LIS v2 prefix for system roles. */
    private const LIS_V2_SYSTEM_ROLE_PREFIX = 'http://purl.imsglobal.org/vocab/lis/v2/system/person#';

    /** @var string The LIS v2 prefix for institution roles. */
    private const LIS_V2_INSTITUTION_ROLE_PREFIX = 'http://purl.imsglobal.org/vocab/lis/v2/institution/person#';

    /** @var string The LIS v2 LEGACY prefix for system roles (deprecated, but used in LTI 2p0). */
    private const LIS_V2_LEGACY_SYSTEM_ROLE_PREFIX = 'http://purl.imsglobal.org/vocab/lis/v2/person#';

    /** @var string The LIS v2 LEGACY prefix for institution roles (deprecated, but used in LTI 2p0). */
    private const LIS_V2_LEGACY_INSTITUTION_ROLE_PREFIX = 'http://purl.imsglobal.org/vocab/lis/v2/person#';

    /** @var string The LIS v1 prefix for context types. */
    private const LIS_V1_CONTEXT_TYPE_PREFIX = 'urn:lti:context-type:ims/lis/';

    /** @var string The LIS v1 prefix for context roles. */
    private const LIS_V1_CONTEXT_ROLE_PREFIX = 'urn:lti:role:ims/lis/';

    /** @var string[] Full list of v1 context types, mapped to v2 context types. */
    private const LIS_V1_TO_V2_CONTEXT_TYPE_MAP = [
        'urn:lti:context-type:ims/lis/CourseTemplate' => 'http://purl.imsglobal.org/vocab/lis/v2/course#CourseTemplate',
        'urn:lti:context-type:ims/lis/CourseOffering' => 'http://purl.imsglobal.org/vocab/lis/v2/course#CourseOffering',
        'urn:lti:context-type:ims/lis/CourseSection' => 'http://purl.imsglobal.org/vocab/lis/v2/course#CourseSection',
        'urn:lti:context-type:ims/lis/Group' => 'http://purl.imsglobal.org/vocab/lis/v2/course#Group',
    ];

    /** @var string[] Full list of v1 roles, mapped to their LIS v2 role equivalents. */
    private const LIS_V1_TO_V2_ROLES_MAP = [
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

        // The context roles and sub-roles below don't map to v2 using the exact same principal role name (TeachingAssistant
        // doesn't exist in v2). Sub-roles are mapped to replacement roles within the 'Instructor' principal role in v2, using the
        // same sub-role name. The context role itself is mapped to the v2 sub-role Instructor#SecondaryTeacher.
        'urn:lti:role:ims/lis/TeachingAssistant' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#SecondaryInstructor',
        'urn:lti:role:ims/lis/TeachingAssistant/TeachingAssistant' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistant',
        'urn:lti:role:ims/lis/TeachingAssistant/TeachingAssistantSection' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistantSection',
        'urn:lti:role:ims/lis/TeachingAssistant/TeachingAssistantSectionAssociation' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistantSectionAssociation',
        'urn:lti:role:ims/lis/TeachingAssistant/TeachingAssistantOffering' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistantOffering',
        'urn:lti:role:ims/lis/TeachingAssistant/TeachingAssistantTemplate' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistantTemplate',
        'urn:lti:role:ims/lis/TeachingAssistant/TeachingAssistantGroup' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistantGroup',
        'urn:lti:role:ims/lis/TeachingAssistant/Grader' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#Grader',
    ];

    /** @var string[] List of LIS v2 roles which don't map to v1 roles. */
    private const LIS_V1_TO_V2_UNMAPPABLE_ROLES = [
        // LIS v2 roles which don't map to v1.
        'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#SecondaryInstructor',
        'http://purl.imsglobal.org/vocab/lis/v2/membership/Manager#Manager',
        'http://purl.imsglobal.org/vocab/lis/v2/membership#Officer',
        'http://purl.imsglobal.org/vocab/lis/v2/membership/Officer#Chair',
        'http://purl.imsglobal.org/vocab/lis/v2/membership/Officer#Communications',
        'http://purl.imsglobal.org/vocab/lis/v2/membership/Officer#Secretary',
        'http://purl.imsglobal.org/vocab/lis/v2/membership/Officer#Treasurer',
        'http://purl.imsglobal.org/vocab/lis/v2/membership/Officer#Vice-Chair',
    ];

    /**
     * Convert a list of role strings to fully qualified v1 role strings.
     *
     *  Note:
     *  - Supports v1 FQ roles, v2 FQ roles, and v1 simple names/context role handles in the input array.
     *  - Does not remove duplicates from the output array. Output will match input in size.
     *  - Returns null for any roles not mappable to v1 roles.
     *
     * @param array $roles the array of roles to convert.
     * @return array the array of converted roles.
     */
    public function to_v1_roles(array $roles): array {
        // This is the dictionary used to look up the v1 role (value) for a given input value (key).
        // It supports conversion of:
        // v2 roles => v1 roles;
        // v1 context role simple names (aka handles, e.g. 'Learner' or 'Learner/Learner') => v1 roles; and
        // v1 roles => v1 roles.
        $fullmap = array_merge(
            // Add v2 => v1 roles.
            array_flip(self::LIS_V1_TO_V2_ROLES_MAP),
            // Add context role handles => v1 roles.
            $this->get_context_role_handle_to_v1_role_map(),
            // Add v1 roles => v1 roles.
            array_combine(array_keys(self::LIS_V1_TO_V2_ROLES_MAP), array_keys(self::LIS_V1_TO_V2_ROLES_MAP))
        );

        // Get the v1 values from the map, preserving the order of the input array.
        return array_map(
            fn($key) => $fullmap[$key] ?? null,
            $roles
        );
    }

    /**
     * Convert a list of role strings to fully qualified v2 role strings.
     *
     * Note:
     * - Supports v1 FQ roles, v2 FQ roles, and v1 simple names/context role handles in the input array.
     * - Does not remove duplicates from the output array. Output will match input in size.
     * - Returns null for any roles not mappable to v2 roles.
     *
     * @param array $roles the array of roles strings to convert.
     * @param bool $usedeprecatedv2roleprefixes whether to return the v2 roles using the deprecated system/institution role prefix.
     * @return string[] the array of converted role strings.
     */
    public function to_v2_roles(array $roles, bool $usedeprecatedv2roleprefixes = false): array {
        // This is the dictionary used to look up the v2 role (value) for a given input value (key).
        // It supports conversion of:
        // v1 roles => v2 roles;
        // v1 context role simple names (aka handles, e.g. 'Learner' or 'Learner/Learner') => v2 roles; and
        // v2 roles => v2 roles.
        $fullmap = array_merge(
            // Add v1 => v2 roles.
            self::LIS_V1_TO_V2_ROLES_MAP,
            // Add context role simple names => v2 roles.
            $this->get_context_role_handle_to_v2_role_map(),
            // Add v2 roles => v2 roles.
            array_combine($this->get_all_v2_roles(), $this->get_all_v2_roles())
        );

        // Get the v2 values from the map, or null, preserving the order of the input array.
        $v2roles = array_map(
            fn($key) => $fullmap[$key] ?? null,
            $roles
        );

        return $usedeprecatedv2roleprefixes ? $this->convert_to_deprecated_v2_role_prefixes($v2roles) : $v2roles;
    }

    /**
     * Convert a list of context type strings to fully qualified v1 context type strings.
     *
     * @param array $contexttypes the array of context types to convert.
     * @return array the converted context types.
     */
    public function to_v1_context_types(array $contexttypes): array {
        // This is the dictionary used to look up the v1 context type (value) for a given input value (key).
        // It supports conversion of:
        // v2 context types => v1 context types;
        // v1 context type simple names (aka handles, e.g. 'CourseTemplate') => v1 context types; and
        // v1 context types => v1 context types.
        $fullmap = array_merge(
            // Add v2 => v1 context types.
            array_flip(self::LIS_V1_TO_V2_CONTEXT_TYPE_MAP),
            // Add context type handles => v1 roles.
            $this->get_context_type_handle_to_v1_context_type_map(),
            // Add v1 context types => v1 context types.
            array_combine(array_keys(self::LIS_V1_TO_V2_CONTEXT_TYPE_MAP), array_keys(self::LIS_V1_TO_V2_CONTEXT_TYPE_MAP))
        );

        // Get the v1 values from the map, preserving the order of the input array.
        return array_map(
            fn($key) => $fullmap[$key] ?? null,
            $contexttypes
        );
    }

    /**
     * Convert a list of context type strings to fully qualified v2 context type strings.
     *
     * @param array $contexttypes the array of context types to convert.
     * @return array the converted context types.
     */
    public function to_v2_context_types(array $contexttypes): array {
        // This is the dictionary used to look up the v2 context type (value) for a given input value (key).
        // It supports conversion of:
        // v1 context types => v2 context types;
        // v1 context type simple names (aka handles, e.g. 'CourseTemplate') => v2 context types; and
        // v2 context types => v2 context types.
        $fullmap = array_merge(
            // Add v1 contexts.
            self::LIS_V1_TO_V2_CONTEXT_TYPE_MAP,
            // Add v1 simple context names.
            $this->get_context_type_handle_to_v2_context_type_map(),
            // Add v2 contexts.
            array_combine($this->get_all_v2_context_types(), $this->get_all_v2_context_types()),
        );

        // Get the v2 values from the map, or null, preserving the order of the input array.
        return array_map(
            fn($key) => $fullmap[$key] ?? null,
            $contexttypes
        );
    }

    /**
     * Helper to derive the full list of v2 roles from several of the internal maps.
     *
     * @return array the array of v2 roles.
     */
    private function get_all_v2_roles(): array {
        return array_merge(
            // V2 roles which are mappable to v1.
            array_values(self::LIS_V1_TO_V2_ROLES_MAP),
            // V2 roles which cannot be mapped to v1.
            self::LIS_V1_TO_V2_UNMAPPABLE_ROLES,
        );
    }

    /**
     * Helper to return the full list of v2 context types.
     *
     * @return array the array of v2 context types.
     */
    private function get_all_v2_context_types(): array {
        return array_values(self::LIS_V1_TO_V2_CONTEXT_TYPE_MAP);
    }

    /**
     * Convert any v2 context and institution roles to use their legacy path prefix.
     *
     * Converts any roles in the array having one of the v2 path prefixes:
     * http://purl.imsglobal.org/vocab/lis/v2/system/person#
     * http://purl.imsglobal.org/vocab/lis/v2/institution/person#
     *
     * To roles using the legacy prefix, which is/was used in LTI 2p0 (note the absence of system/institution in the path prefix):
     * http://purl.imsglobal.org/vocab/lis/v2/person#
     *
     * Does not change other roles, does not remove duplicates after conversion.
     *
     * @param array $v2roles the list of LIS v2 roles
     * @return array the converted list of roles.
     */
    private function convert_to_deprecated_v2_role_prefixes(array $v2roles): array {
        return array_map(function($role) {
            if (str_contains($role, self::LIS_V2_SYSTEM_ROLE_PREFIX)) {
                $handle = substr($role, strlen(self::LIS_V2_SYSTEM_ROLE_PREFIX));
                return self::LIS_V2_LEGACY_SYSTEM_ROLE_PREFIX . $handle;
            } else if (str_contains($role, self::LIS_V2_INSTITUTION_ROLE_PREFIX)) {
                $handle = substr($role, strlen(self::LIS_V2_INSTITUTION_ROLE_PREFIX));
                return self::LIS_V2_LEGACY_INSTITUTION_ROLE_PREFIX . $handle;
            }
            return $role;
        }, $v2roles);
    }

    /**
     * Returns an array mapping v1 context role handles to v2 context roles.
     *
     * @return array the map.
     */
    private function get_context_role_handle_to_v2_role_map(): array {
        $v1tov2contextrolesmap = array_filter(
            self::LIS_V1_TO_V2_ROLES_MAP,
            fn($v1role) => str_contains($v1role, self::LIS_V1_CONTEXT_ROLE_PREFIX),
            ARRAY_FILTER_USE_KEY
        );
        return array_combine(
            // Get just the handle.
            array_map(fn($v1role) => substr($v1role, strlen(self::LIS_V1_CONTEXT_ROLE_PREFIX)), array_keys($v1tov2contextrolesmap)),
            // And map it to the v2 role.
            array_values($v1tov2contextrolesmap)
        );
    }

    /**
     * Returns an array mapping v1 context role handles to v1 context roles.
     *
     * @return array the map.
     */
    private function get_context_role_handle_to_v1_role_map(): array {
        // Since v1 defined the simple names/handles, only the v1 side of the roles map is needed to
        // derive the map between handles v1 context roles.
        $v1roles = array_keys(self::LIS_V1_TO_V2_ROLES_MAP);
        $v1contextroles = array_filter(
            $v1roles,
            fn($v1role) => str_contains($v1role, self::LIS_V1_CONTEXT_ROLE_PREFIX),
        );

        return array_combine(
            // Get just the handle.
            array_map(fn($v1role) => substr($v1role, strlen(self::LIS_V1_CONTEXT_ROLE_PREFIX)), $v1contextroles),
            // And map it to the v1 role.
            $v1contextroles
        );
    }

    /**
     * Returns an array mapping v1 context type handles to v2 context types.
     *
     * @return array the map.
     */
    private function get_context_type_handle_to_v2_context_type_map(): array {
        return array_combine(
            // Get just the handle.
            array_map(
                fn($v1contexttype) => substr($v1contexttype, strlen(self::LIS_V1_CONTEXT_TYPE_PREFIX)),
                array_keys(self::LIS_V1_TO_V2_CONTEXT_TYPE_MAP)
            ),
            // And map it to the v2 context type.
            array_values(self::LIS_V1_TO_V2_CONTEXT_TYPE_MAP)
        );
    }

    /**
     * Returns an array mapping v1 context type handles to v1 context types.
     *
     * @return array the map.
     */
    private function get_context_type_handle_to_v1_context_type_map(): array {
        return array_combine(
            // Get just the handle.
            array_map(
                fn($v1contexttype) => substr($v1contexttype, strlen(self::LIS_V1_CONTEXT_TYPE_PREFIX)),
                array_keys(self::LIS_V1_TO_V2_CONTEXT_TYPE_MAP)
            ),
            // And map it to the v1 context type.
            array_keys(self::LIS_V1_TO_V2_CONTEXT_TYPE_MAP)
        );
    }
}
