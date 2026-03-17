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

namespace core_ltix\local\lticore\message;

/**
 * Class lti_message.
 *
 * @package    core_ltix
 * @copyright  2025 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class lti_message {

    /**
     * Constructor.
     *
     * @param string $url
     * @param array $parameters
     */
    public function __construct(private string $url, private array $parameters) {
    }

    /**
     * Get the URL to post to.
     *
     * @return string
     */
    public function get_url(): string {
        return $this->url;
    }

    /**
     * Get the parameters to post.
     *
     * @return array
     */
    public function get_parameters(): array {
        return $this->parameters;
    }

    /**
     * Output the message as an automatically-posting HTML form.
     *
     * @return string the string of HTML + JS.
     */
    public function to_html_form(): string {
        $html = "<form action=\"" . $this->get_url() . "\" name=\"ltiMessagePostForm\" id=\"ltiMessagePostForm\" method=\"post\" " .
            "encType=\"application/x-www-form-urlencoded\">\n";

        foreach ($this->get_parameters() as $key => $value) {
            $key = htmlspecialchars($key, ENT_COMPAT);
            $value = htmlspecialchars($value, ENT_COMPAT);
            $html .= "  <input type=\"hidden\" name=\"{$key}\" value=\"{$value}\"/>\n";
        }
        $html .= "</form>\n";

        $html .= "<script type=\"text/javascript\">\n" .
            "//<![CDATA[\n" .
            "document.ltiMessagePostForm.submit();\n" .
            "//]]>\n" .
            "</script>\n";

        return $html;
    }
}
