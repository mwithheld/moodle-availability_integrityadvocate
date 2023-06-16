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
 * Front-end class.
 *
 * @package availability_integrityadvocate
 * @copyright IntegrityAdvocate.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_integrityadvocate;

use block_integrityadvocate\MoodleUtility as ia_mu;

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__, 2) . '/locallib.php');

/**
 * Front-end class.
 *
 * @package availability_integrityadvocate
 * @copyright IntegrityAdvocate.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class frontend extends \core_availability\frontend {

    /**
     * @var array Cached init parameters
     */
    protected $cacheinitparams = [];

    /**
     * @var string IDs of course, cm, and section for cache (if any)
     */
    protected $cachekey = '';

    /**
     * Return a list of names within your language file to use in JS.
     *
     * @return Array of strings
     */
    protected function get_javascript_strings() {
        return array('option_valid', 'option_invalid', 'label_cm', 'label_completion');
    }

    /**
     * Gets additional parameters for the plugin's initInner function.
     *
     * Default returns no parameters.
     *
     * @param \stdClass $course Course object
     * @param \cm_info $cm Course-module currently being edited (null if none)
     * @param \section_info $section Section currently being edited (null if none)
     * @return array Array of parameters for the JavaScript function
     */
    protected function get_javascript_init_params($course, \cm_info $cm = null, \section_info $section = null) {
        $debug = false;
        $fxn = __CLASS__ . '::' . __FUNCTION__;
        $debug && debugging($fxn . '::Started with $course->id' . $course->id . '; cmid=' . (isset($cm->id) ? $cm->id : '') . '; $section=' . var_export($section, true));

        // Use cached result if available. The cache is just because we call it
        // twice (once from allow_add) so it's nice to avoid doing all the
        // print_string calls twice.
        $cachekey = ia_mu::get_cache_key($course->id . '_' . ($cm ? $cm->id : '') . '_' . ($section ? $section->id : ''));
        if ($debug || $cachekey !== $this->cachekey) {
            if (!availability_integrityadvocate_is_known_block_type()) {
                $debug && debugging($fxn . '::block_integrityadvocate must be installed and visible');
                // Do not set any parameters.
                $this->cachekey = $cachekey;
                $this->cacheinitparams = [];
                return $this->cacheinitparams;
            }

            $activities = \block_integrityadvocate_get_course_ia_modules($course, array('visible' => 1, 'configured' => 1));

            if (!is_array($activities)) {
                $debug && debugging($fxn . '::No activities in this course have block_integrityadvocate configured and visible');
                // Do not set any parameters.
                $this->cachekey = $cachekey;
                $this->cacheinitparams = [];
                return $this->cacheinitparams;
            }

            // Filter out the current activity and activities being deleted.
            // Add the rest to a list so we can return it.
            $coursecontext = \context_course::instance($course->id);
            $modinfo = get_fast_modinfo($course);

            $cms = [];
            foreach ($activities as $id => $othercm) {
                if (gettype($othercm) !== 'cm_info') {
                    $othercm = $modinfo->get_cm($othercm['context']->instanceid);
                }

                // Do not list the activity if completion is turned off, it is the current one, or if it is being deleted.
                if (!$othercm->completion || !(empty($cm) || isset($cm->id) || $cm->id != $id) || $othercm->deletioninprogress) {
                    continue;
                }

                // Add each course-module to the displayed list of choices.
                $cms[] = (object) array(
                            'id' => $othercm->id,
                            'name' => format_string($othercm->name, true, array('context' => $coursecontext)),
                            'completiongradeitemnumber' => $othercm->completiongradeitemnumber
                );
            }

            $this->cachekey = $cachekey;
            $this->cacheinitparams = array($cms);
        }

        // Return the list of items to fill the activities dropdown.
        return $this->cacheinitparams;
    }

    /**
     * Control whether the 'add' button  appears.
     * For example, the grouping plugin does not appear if there are no
     * groupings on the course. This helps to simplify the user interface.
     * If you don't include this function, it will appear.
     *
     * @param stdClass $course The course to add
     * @param \cm_info $cm Optional CourseModule info
     * @param \section_info $section Optional Section info
     * @return boolean True if the condition can be added.
     */
    protected function allow_add($course, \cm_info $cm = null, \section_info $section = null) {
        $debug = false;
        $fxn = __CLASS__ . '::' . __FUNCTION__;
        $debug && debugging($fxn . '::Started with $course->id' . $course->id . '; cmid=' . (isset($cm->id) ? $cm->id : '') . '; $section=' . var_export($section, true));

        // Check if there's at least one other module with completion info.
        $params = $this->get_javascript_init_params($course, $cm, $section);
        $debug && debugging($fxn . '::Got params=' . var_export($params, true));
        if (empty($params)) {
            $debug && debugging($fxn . '::No activities in this course have block_integrityadvocate configured and visible');
            return false;
        }

        $result = ((array) $params[0]) != false;
        $debug && debugging($fxn . '::About to return $result=' . $result);
        return $result;
    }
}
