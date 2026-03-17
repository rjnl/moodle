<?php

namespace core_ltix\local\lticore\message\substitution;

/**
 * Map-resolve helper to resolve variables to values in some runtime array, based on a fixed mapping.
 *
 * The relationship between map and source data is explained via the example below.
 *
 * Map is of the form:
 * ['Context.title' => 'context_title']
 * Mapping a variable, e.g. Context.title in the above, to a key in sourcedata.
 *
 * Sourcedata is of the form:
 * ['context_title' = 'An example context']
 *
 * Thus, if resolve() were called with $str='Context.id', the resolved value would be 'An example context'.
 *
 * If the $str does not exist in map, or the resulting key does not exist in sourcedata, then null would be returned.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final readonly class variable_map {

    /**
     * Ctor.
     *
     * @param array $map the map of input:sourcedatakey
     */
    public function __construct(private array $map) {
    }

    /**
     * Try to resolve the parameter to a value present in the sourcedata.
     *
     * @param array $sourcedata key:value pairs of source data.
     * @param string $str the variable to resolve, NOT $-prefixed. E.g. 'User.username'.
     * @return string|null the resolved value, otherwise null.
     */
    public function resolve(array $sourcedata, string $str): ?string {
        if (array_key_exists($str, $this->map)) {
            $sourcedatakey = $this->map[$str];
            if (array_key_exists($sourcedatakey, $sourcedata)) {
                return $sourcedata[$sourcedatakey];
            }
        }

        return null;
    }
}
