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
 * This file contains an abstract definition of an LTI service
 *
 * @package    mod_lti
 * @copyright  2014 Vital Source Technologies http://vitalsource.com
 * @author     Stephen Vickers
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace mod_lti\local\ltiservice;

defined('MOODLE_INTERNAL') || die;

/**
 * The mod_lti\local\ltiservice\response class.
 *
 * @deprecated since Moodle 4.5 use \core_ltix\local\ltiservice\response instead.
 * @package    mod_lti
 * @since      Moodle 2.8
 * @copyright  2014 Vital Source Technologies http://vitalsource.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class response {

    /** @var int HTTP response code. */
    private $code;
    /** @var string HTTP response reason. */
    private $reason;
    /** @var string HTTP request method. */
    private $requestmethod;
    /** @var string HTTP request accept header. */
    private $accept;
    /** @var string HTTP response content type. */
    private $contenttype;
    /** @var string HTTP request body. */
    private $data;
    /** @var string HTTP response body. */
    private $body;
    /** @var array HTTP response codes. */
    private $responsecodes;
    /** @var array HTTP additional headers. */
    private $additionalheaders;

    /**
     * Class constructor.
     * @deprecated since Moodle 4.5
     */
    public function __construct() {

        debugging('Class \mod_lti\local\ltiservice\response is deprecated, please use '.
            '\core_ltix\local\ltiservice\response instead.', DEBUG_DEVELOPER);

        $this->code = 200;
        $this->reason = '';
        $this->requestmethod = $_SERVER['REQUEST_METHOD'];
        $this->accept = '';
        $this->contenttype = '';
        $this->data = '';
        $this->body = '';
        $this->responsecodes = array(
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            300 => 'Multiple Choices',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            415 => 'Unsupported Media Type',
            500 => 'Internal Server Error',
            501 => 'Not Implemented'
        );
        $this->additionalheaders = array();

    }

    /**
     * Get the response code.
     *
     * @return int
     * @deprecated since Moodle 4.5
     */
    public function get_code() {
        debugging('Class \mod_lti\local\ltiservice\response is deprecated, please use '.
            '\core_ltix\local\ltiservice\response instead.', DEBUG_DEVELOPER);
        return $this->code;
    }

    /**
     * Set the response code.
     *
     * @param int $code Response code
     * @deprecated since Moodle 4.5
     */
    public function set_code($code) {
        debugging('Class \mod_lti\local\ltiservice\response is deprecated, please use '.
            '\core_ltix\local\ltiservice\response instead.', DEBUG_DEVELOPER);
        $this->code = $code;
        $this->reason = '';
    }

    /**
     * Get the response reason.
     *
     * @return string
     * @deprecated since Moodle 4.5
     */
    public function get_reason() {
        debugging('Class \mod_lti\local\ltiservice\response is deprecated, please use '.
            '\core_ltix\local\ltiservice\response instead.', DEBUG_DEVELOPER);
        $code = $this->code;
        if (($code < 200) || ($code >= 600)) {
            $code = 500;  // Status code must be between 200 and 599.
        }
        if (empty($this->reason) && array_key_exists($code, $this->responsecodes)) {
            $this->reason = $this->responsecodes[$code];
        }
        // Use generic reason for this category (based on first digit) if a specific reason is not defined.
        if (empty($this->reason)) {
            $this->reason = $this->responsecodes[intval($code / 100) * 100];
        }
        return $this->reason;
    }

    /**
     * Set the response reason.
     *
     * @param string $reason Reason
     * @deprecated since Moodle 4.5
     */
    public function set_reason($reason) {
        debugging('Class \mod_lti\local\ltiservice\response is deprecated, please use '.
            '\core_ltix\local\ltiservice\response instead.', DEBUG_DEVELOPER);
        $this->reason = $reason;
    }

    /**
     * Get the request method.
     *
     * @return string
     * @deprecated since Moodle 4.5
     */
    public function get_request_method() {
        debugging('Class \mod_lti\local\ltiservice\response is deprecated, please use '.
            '\core_ltix\local\ltiservice\response instead.', DEBUG_DEVELOPER);
        return $this->requestmethod;
    }

    /**
     * Get the request accept header.
     *
     * @return string
     * @deprecated since Moodle 4.5
     */
    public function get_accept() {
        debugging('Class \mod_lti\local\ltiservice\response is deprecated, please use '.
            '\core_ltix\local\ltiservice\response instead.', DEBUG_DEVELOPER);
        return $this->accept;
    }

    /**
     * Set the request accept header.
     *
     * @param string $accept Accept header value
     * @deprecated since Moodle 4.5
     */
    public function set_accept($accept) {
        debugging('Class \mod_lti\local\ltiservice\response is deprecated, please use '.
            '\core_ltix\local\ltiservice\response instead.', DEBUG_DEVELOPER);
        $this->accept = $accept;
    }

    /**
     * Get the response content type.
     *
     * @return string
     * @deprecated since Moodle 4.5
     */
    public function get_content_type() {
        debugging('Class \mod_lti\local\ltiservice\response is deprecated, please use '.
            '\core_ltix\local\ltiservice\response instead.', DEBUG_DEVELOPER);
        return $this->contenttype;
    }

    /**
     * Set the response content type.
     *
     * @param string $contenttype Content type
     * @deprecated since Moodle 4.5
     */
    public function set_content_type($contenttype) {
        debugging('Class \mod_lti\local\ltiservice\response is deprecated, please use '.
            '\core_ltix\local\ltiservice\response instead.', DEBUG_DEVELOPER);
        $this->contenttype = $contenttype;
    }

    /**
     * Get the request body.
     *
     * @return string
     * @deprecated since Moodle 4.5
     */
    public function get_request_data() {
        debugging('Class \mod_lti\local\ltiservice\response is deprecated, please use '.
            '\core_ltix\local\ltiservice\response instead.', DEBUG_DEVELOPER);
        return $this->data;
    }

    /**
     * Set the response body.
     *
     * @param string $data Body data
     * @deprecated since Moodle 4.5
     */
    public function set_request_data($data) {
        debugging('Class \mod_lti\local\ltiservice\response is deprecated, please use '.
            '\core_ltix\local\ltiservice\response instead.', DEBUG_DEVELOPER);
        $this->data = $data;
    }

    /**
     * Get the response body.
     *
     * @return string
     * @deprecated since Moodle 4.5
     */
    public function get_body() {
        debugging('Class \mod_lti\local\ltiservice\response is deprecated, please use '.
            '\core_ltix\local\ltiservice\response instead.', DEBUG_DEVELOPER);
        return $this->body;
    }

    /**
     * Set the response body.
     *
     * @param string $body Body data
     * @deprecated since Moodle 4.5
     */
    public function set_body($body) {
        debugging('Class \mod_lti\local\ltiservice\response is deprecated, please use '.
            '\core_ltix\local\ltiservice\response instead.', DEBUG_DEVELOPER);
        $this->body = $body;
    }

    /**
     * Add an additional header.
     *
     * @param string $header The new header
     * @deprecated since Moodle 4.5
     */
    public function add_additional_header($header) {
        debugging('Class \mod_lti\local\ltiservice\response is deprecated, please use '.
            '\core_ltix\local\ltiservice\response instead.', DEBUG_DEVELOPER);
        array_push($this->additionalheaders, $header);
    }

    /**
     * Send the response.
     * @deprecated since Moodle 4.5
     */
    public function send() {
        debugging('Class \mod_lti\local\ltiservice\response is deprecated, please use '.
            '\core_ltix\local\ltiservice\response instead.', DEBUG_DEVELOPER);
        header("HTTP/1.0 {$this->code} {$this->get_reason()}");
        foreach ($this->additionalheaders as $header) {
            header($header);
        }
        if ((($this->code >= 200) && ($this->code < 300)) || !empty($this->body)) {
            if (!empty($this->contenttype)) {
                header("Content-Type: {$this->contenttype}; charset=utf-8");
            }
            if (!empty($this->body)) {
                echo $this->body;
            }
        } else if ($this->code >= 400) {
            header("Content-Type: application/json; charset=utf-8");
            $body = new \stdClass();
            $body->status = $this->code;
            $body->reason = $this->get_reason();
            $body->request = new \stdClass();
            $body->request->method = $_SERVER['REQUEST_METHOD'];
            $body->request->url = $_SERVER['REQUEST_URI'];
            if (isset($_SERVER['HTTP_ACCEPT'])) {
                $body->request->accept = $_SERVER['HTTP_ACCEPT'];
            }
            if (isset($_SERVER['CONTENT_TYPE'])) {
                $body->request->contentType = explode(';', $_SERVER['CONTENT_TYPE'], 2)[0];
            }
            echo json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
    }

}
