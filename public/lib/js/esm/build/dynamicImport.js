/**
 * Native dynamic import of an ES module resolved through the Moodle import map.
 *
 * This lives in its own module so that callers can be unit tested: Jest cannot
 * run a native `import()`, but it can mock this module.
 *
 * @module     core/dynamicImport
 * @copyright  2026 Rajneel Totaram <rajneel.totaram@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */const r=async o=>import(o);export{r as dynamicImport};
