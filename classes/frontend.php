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
    protected $cacheparams = array();

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
     * Parameters here which will be passed into the JavaScript init method.
     *
     * @param type $course
     * @param \cm_info $cm
     * @param \section_info $section
     * @return type
     */
    protected function get_javascript_init_params($course, \cm_info $cm = null, \section_info $section = null) {
        $debug = true;
        $debug && error_log(__FILE__ . '::' . __FUNCTION__ . '::Started with $course->id' . $course->id . '; cmid=' . (isset($cm->id) ? $cm->id : '') . '; $section=' . print_r($section, true));

        // Use cached result if available. The cache is just because we call it
        // twice (once from allow_add) so it's nice to avoid doing all the
        // print_string calls twice.
        $cachekey = $course->id . ',' . ($cm ? $cm->id : '') . ($section ? $section->id : '');
        if ($debug || $cachekey !== $this->cachekey) {
            if (!availability_integrityadvocate_is_known_block_type()) {
                $debug && error_log(__FILE__ . '::' . __FUNCTION__ . '::block_integrityadvocate must be installed and visible');
                // Do not set any parameters.
                $this->cachekey = $cachekey;
                $this->cacheinitparams = array();
                return $this->cacheinitparams;
            }

            $activities = \block_integrityadvocate_get_course_ia_activities($course, array('visible' => 1, 'configured' => 1));
            // Disabled on purpose: $debug && error_log(__FILE__ . '::' . __FUNCTION__ . '::Got $activities=' . print_r($activities, true));.

            if (!is_array($activities)) {
                $debug && error_log(__FILE__ . '::' . __FUNCTION__ . '::No activites in this course have block_integrityadvocate configured and visible');
                // Do not set any parameters.
                $this->cachekey = $cachekey;
                $this->cacheinitparams = array();
                return $this->cacheinitparams;
            }

            // Filter out the current activity and activities being deleted.
            // Add the rest to a list so we can return it.
            $coursecontext = \context_course::instance($course->id);
            $modinfo = get_fast_modinfo($course);

            $cms = array();
            foreach ($activities as $othercm) {
                // Disabled on purpose: $debug && error_log(__FILE__ . '::' . __FUNCTION__ . '::Looking at activity=' . print_r($othercm, true));.
                if (gettype($othercm) !== 'cm_info') {
                    $othercm = $modinfo->get_cm($othercm['context']->instanceid);
                }
                // Disabled on purpose: $debug && error_log(__FILE__ . '::' . __FUNCTION__ . '::Looking at activity=' . print_r($othercm, true));.
                // I.
                // Skip the current activity or if it is being deleted.
                if (($othercm->id === $cm->id) || $othercm->deletioninprogress) {
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
        $debug = true;
        $debug && error_log(__FILE__ . '::' . __FUNCTION__ . '::Started with $course->id' . $course->id . '; cmid=' . (isset($cm->id) ? $cm->id : '') . '; $section=' . print_r($section, true));

        // Check if there's at least one other module with completion info.
        $params = $this->get_javascript_init_params($course, $cm, $section);
        $debug && error_log(__FILE__ . '::' . __FUNCTION__ . '::Got params=' . print_r($params, true));
        if (empty($params)) {
            error_log(__FILE__ . '::' . __FUNCTION__ . '::No activites in this course have block_integrityadvocate configured and visible');
            return false;
        }

        $result = ((array) $params[0]) != false;
        $debug && error_log(__FILE__ . '::' . __FUNCTION__ . '::About to return $result=' . $result);
        return $result;
    }

}
