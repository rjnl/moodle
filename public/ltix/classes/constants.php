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

namespace core_ltix;

/**
 * LTI Constants
 *
 * @package    core_ltix
 * @author     Alex Morris <alex.morris@catalyst.net.nz>
 * @copyright  2023 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class constants
{
    const LTI_URL_DOMAIN_REGEX = '/(?:https?:\/\/)?(?:www\.)?([^\/]+)(?:\/|$)/i';

    const LTI_LAUNCH_CONTAINER_DEFAULT = 1;
    const LTI_LAUNCH_CONTAINER_EMBED = 2;
    const LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS = 3;
    const LTI_LAUNCH_CONTAINER_WINDOW = 4;
    const LTI_LAUNCH_CONTAINER_REPLACE_MOODLE_WINDOW = 5;

    const LTI_TOOL_STATE_ANY = 0;
    const LTI_TOOL_STATE_CONFIGURED = 1;
    const LTI_TOOL_STATE_PENDING = 2;
    const LTI_TOOL_STATE_REJECTED = 3;
    const LTI_TOOL_PROXY_TAB = 4;

    // TODO: deprecate these LTI 2 consts since LTI 2.0 support is being dropped.
    const LTI_TOOL_PROXY_STATE_CONFIGURED = 1;
    const LTI_TOOL_PROXY_STATE_PENDING = 2;
    const LTI_TOOL_PROXY_STATE_ACCEPTED = 3;
    const LTI_TOOL_PROXY_STATE_REJECTED = 4;

    const LTI_SETTING_NEVER = 0;
    const LTI_SETTING_ALWAYS = 1;
    const LTI_SETTING_DELEGATE = 2;

    const LTI_COURSEVISIBLE_NO = 0;
    const LTI_COURSEVISIBLE_PRECONFIGURED = 1;
    const LTI_COURSEVISIBLE_ACTIVITYCHOOSER = 2;

    const LTI_VERSION_1 = 'LTI-1p0';
    const LTI_VERSION_2 = 'LTI-2p0';
    const LTI_VERSION_1P3 = '1.3.0';

    const LTI_RSA_KEY = 'RSA_KEY';
    const LTI_JWK_KEYSET = 'JWK_KEYSET';

    const LTI_DEFAULT_ORGID_SITEID = 'SITEID';
    const LTI_DEFAULT_ORGID_SITEHOST = 'SITEHOST';

    const LTI_ACCESS_TOKEN_LIFE = 3600;

    // Standard prefix for JWT claims.
    const LTI_JWT_CLAIM_PREFIX = 'https://purl.imsglobal.org/spec/lti';

    // TODO: maybe these can live in mod_lti if only used there..
    const LTI_ITEM_TYPE = 'mod';
    const LTI_ITEM_MODULE = 'lti';
    const LTI_SOURCE = 'mod/lti';
}
