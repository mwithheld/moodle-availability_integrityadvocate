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
 * Activity completion condition.
 *
 * @package availability_integrityadvocate
 * @copyright IntegrityAdvocate.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_integrityadvocate;

use block_integrityadvocate\MoodleUtility as ia_mu;

defined('MOODLE_INTERNAL') || die();

// There are only two valid expected statuses.
// These are not neccesarily the same values as ia_status values.
// We relate those to the ia_status::is_valid_status() and is_invalid_status().
define('INTEGRITYADVOCATE_EXPECTED_STATUS_INVALID', 0);
define('INTEGRITYADVOCATE_EXPECTED_STATUS_VALID', 1);

require_once($CFG->libdir . '/completionlib.php');
require_once(dirname(__FILE__, 2) . '/locallib.php');

/**
 * Activity completion condition.
 *
 * @package availability_integrityadvocate
 * @copyright IntegrityAdvocate.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \core_availability\condition {

    /** @var int ID of module that this depends on */
    protected $cmid;

    /** @var int Expected completion type (one of the COMPLETE_xx constants) */
    protected $expectedstatus;

    /** @var array Array of modules used in these conditions for course */
    protected static $modsusedincondition = [];

    /**
     * Retrieve any necessary data from the $structure here. The
     * structure is extracted from JSON data stored in the database
     * as part of the tree structure of conditions relating to an
     * activity or section.
     *
     * @param \stdClass $structure Data structure from JSON decode
     * @throws \coding_exception If invalid data structure.
     */
    public function __construct(\stdClass $structure) {
        $debug = false;
        $fxn = __CLASS__ . '::' . __FUNCTION__;
        $debug && debugging($fxn . '::Started with $structure=' . var_export($structure, true));

        // Get cmid.
        if (isset($structure->cm) && is_number($structure->cm)) {
            $this->cmid = (int) $structure->cm;
            $debug && debugging($fxn . "::Set this->cmid={$this->cmid}");
        } else {
            $msg = 'Missing or invalid value in cm for completion condition';
            debugging($fxn . "::{$msg}");
            throw new \coding_exception($msg);
        }

        /* Here's what $structure looks like:...
          $structure=stdClass Object
          (
          [type] => integrityadvocate
          [cm] => 2
          [e] => 1
          )
         */
        // Get expected completion.
        if (isset($structure->e) && in_array($structure->e,
                        array(INTEGRITYADVOCATE_EXPECTED_STATUS_VALID, INTEGRITYADVOCATE_EXPECTED_STATUS_INVALID)
                )
        ) {
            $this->expectedstatus = $structure->e;
            $debug && debugging($fxn . "::Set this->expectedstatus={$this->expectedstatus}");
        } else {
            $msg = 'Missing or invalid value in e for completion condition';
            debugging($fxn . "::{$msg}");
            throw new \coding_exception($msg);
        }
    }

    /**
     * Save back the data into a plain array similar to $structure above.
     *
     * @return stdClass A completion object
     */
    public function save() {
        $debug = false;
        $fxn = __CLASS__ . '::' . __FUNCTION__;
        $debug && debugging($fxn . '::Started');

        $data = (object) array('type' => INTEGRITYADVOCATE_SHORTNAME, 'cm' => $this->cmid, 'e' => $this->expectedstatus);
        $debug && debugging($fxn . '::About to return $data=' . var_export($data, true));

        return $data;
    }

    /**
     * Returns a JSON object which corresponds to a condition of this type.
     *
     * Intended for unit testing, as normally the JSON values are constructed
     * by JavaScript code.
     *
     * @param int $cmid Course-module id of other activity
     * @param int $expectedstatus Expected completion value (COMPLETION_xx)
     * @return stdClass Object representing condition
     */
    public static function get_json($cmid, $expectedstatus) {
        return (object) array('type' => INTEGRITYADVOCATE_SHORTNAME, 'cm' => (int) $cmid, 'e' => (int) $expectedstatus);
    }

    /**
     * Determines whether a particular item is currently available
     * according to this availability condition.
     *
     * If implementations require a course or modinfo, they should use
     * the get methods in $info.
     *
     * The $not option is potentially confusing. This option always indicates
     * the 'real' value of NOT. For example, a condition inside a 'NOT AND'
     * group will get this called with $not = true, but if you put another
     * 'NOT OR' group inside the first group, then a condition inside that will
     * be called with $not = false. We need to use the real values, rather than
     * the more natural use of the current value at this point inside the tree,
     * so that the information displayed to users makes sense.
     *
     * @param bool $not Set true if we are inverting the condition
     * @param info $info Item we're checking
     * @param bool $grabthelot Performance hint: if true, caches information
     *   required for all course-modules, to make the front page and similar
     *   pages work more quickly (works only for current user)
     * @param int $userid User ID to check availability for
     * @return bool True if available
     */
    public function is_available($not, \core_availability\info $info, $grabthelot, $userid) {
        $debug = false;
        $fxn = __CLASS__ . '::' . __FUNCTION__;
        $debug && debugging($fxn . '::Started with $not=' . $not .
                        '; sha1(info)=' . sha1(json_encode($info, JSON_PARTIAL_OUTPUT_ON_ERROR)) .
                        '; $grabthelot=' . $grabthelot . '; $userid=' . $userid);
        // This valus is non-null when $allow should be forced to true or false.
        $allowoverridden = null;

        // Cache responses in a per-request cache so multiple calls in one request don't repeat the same work.
        $cache = \cache::make(__NAMESPACE__, 'perrequest');
        $cachekey = ia_mu::get_cache_key(__CLASS__ . '_' . __FUNCTION__ . '_' . $this->cmid . json_encode($info, JSON_PARTIAL_OUTPUT_ON_ERROR) . $grabthelot . $userid);

        // The cached value is serialized so we can store false and distinguish it from when cache lookup fails.
        $cachedvalue = $cache->get($cachekey);
        $debug && debugging($fxn . '::Got $cachedvalue=' . var_export($cachedvalue, true));
        if ($cachedvalue) {
            $debug && debugging($fxn . '::Found a cached value, so return that');
            return unserialize($cachedvalue);
        }

        if (!availability_integrityadvocate_is_known_block_type()) {
            $debug && debugging($fxn . '::block_integrityadvocate not found, so condition ' . INTEGRITYADVOCATE_AVAILABILITY_NAME . ' is not available');
            // If block_integrityadvocate does not exist, always allow the user access.
            $allowoverridden = true;
        }

        if (is_null($allowoverridden)) {
            $modulecontext = $info->get_context();
            if ($modulecontext->contextlevel !== CONTEXT_MODULE) {
                $msg = 'Called with invalid contextlevel=' . $modulecontext->contextlevel;
                debugging($fxn . "::$msg");
                throw new \Exception($msg);
            }

            $modinfo = $info->get_modinfo();

            if (!array_key_exists($this->cmid, $modinfo->cms)) {
                // If the cmid cannot be found, always return false regardless of the...
                // Condition or $not state. (Will be displayed in the information message).
                $allowoverridden = false;
            }
        }

        if (is_null($allowoverridden)) {
            // Get the IA data so we can decide whether to show the activity to the user.
            $course = $modinfo->get_course();
            $debug && debugging($fxn . '::Got $course->id=' . $course->id);

            $othercm = $modinfo->get_cm($this->cmid);
            $debug && debugging($fxn . '::Got $othercm->id=' . $othercm->id . '; name=' . $othercm->name);
        }

        if (is_null($allowoverridden)) {
            switch ($this->expectedstatus) {
                case INTEGRITYADVOCATE_EXPECTED_STATUS_VALID:
                    $allow = \block_integrityadvocate\Api::is_status_valid($othercm->context, $userid);
                    $debug && debugging($fxn . "::\$othercm={$othercm->id}; We require status=Valid, did it?=" . $allow);
                    break;
                case INTEGRITYADVOCATE_EXPECTED_STATUS_INVALID:
                    $allow = \block_integrityadvocate\Api::is_status_invalid($othercm->context, $userid);
                    $debug && debugging($fxn . "::\$othercm={$othercm->id}; We require status=Invalid, did it?=" . $allow);
                    break;
                default:
                    $msg = 'Invalid $this->expectedstatus=' . $this->expectedstatus;
                    debugging($fxn . "::$msg");
                    throw new \Exception($msg);
            }
        }

        if (is_null($allowoverridden) && $not) {
            $allow = !$allow;
        }

        if (!is_null($allowoverridden)) {
            $allow = $allowoverridden;
        }

        if (!$cache->set($cachekey, serialize($allow))) {
            throw new \Exception('Failed to set value in perrequest cache');
        }

        $debug && debugging($fxn . "::\$othercm={$othercm->id}; About to return $allow=" . $allow);
        return $allow;
    }

    /**
     * Obtains a string describing this restriction (whether or not
     * it actually applies). Used to obtain information that is displayed to
     * students if the activity is not available to them, and for staff to see
     * what conditions are.
     *
     * The $full parameter can be used to distinguish between 'staff' cases
     * (when displaying all information about the activity) and 'student' cases
     * (when displaying only conditions they don't meet).
     *
     * If implementations require a course or modinfo, they should use
     * the get methods in $info.
     *
     * The special string <AVAILABILITY_CMNAME_123/> can be returned, where
     * 123 is any number. It will be replaced with the correctly-formatted
     * name for that activity.
     *
     * @param bool $full Set true if this is the 'full information' view
     * @param bool $not Set true if we are inverting the condition
     * @param info $info Item we're checking
     * @return string Information string (for admin) about all restrictions on
     *   this item
     */
    public function get_description($full, $not, \core_availability\info $info) {
        $debug = false;
        $fxn = __CLASS__ . '::' . __FUNCTION__;
        $debug && debugging($fxn . '::Started with $full=' . var_export($full, true) . "; \$not={$not}; sha1(info)=" . sha1(json_encode($info, JSON_PARTIAL_OUTPUT_ON_ERROR)));

        // Cache responses in a per-request cache so multiple calls in one request don't repeat the same work.
        $cache = \cache::make(__NAMESPACE__, 'perrequest');
        $cachekey = ia_mu::get_cache_key(__CLASS__ . '_' . __FUNCTION__ . '_' . $this->cmid . $full . $not . json_encode($info, JSON_PARTIAL_OUTPUT_ON_ERROR));

        $cachedvalue = $cache->get($cachekey);
        $debug && debugging($fxn . '::Got $cachedvalue=' . var_export($cachedvalue, true));
        if ($cachedvalue) {
            $debug && debugging($fxn . '::Found a cached value, so return that');
            return $cachedvalue;
        }

        // Get name for module.
        $modinfo = $info->get_modinfo();
        if (!array_key_exists($this->cmid, $modinfo->cms)) {
            $modname = get_string('missing', INTEGRITYADVOCATE_AVAILABILITY_NAME);
        } else {
            $modname = '<AVAILABILITY_CMNAME_' . $modinfo->cms[$this->cmid]->id . '/>';
        }
        $debug && debugging($fxn . "::Got \$modname={$modname}; " . "\$this->expectedstatus={$this->expectedstatus}");

        // Work out which lang string to use.
        switch ($this->expectedstatus) {
            case INTEGRITYADVOCATE_EXPECTED_STATUS_INVALID :
                $debug && debugging($fxn . "::Found \$this->expectedstatus={$this->expectedstatus} == INTEGRITYADVOCATE_EXPECTED_STATUS_INVALID");
                if ($not) {
                    $str = 'requires_valid';
                } else {
                    $str = 'requires_invalid';
                }
                break;
            case INTEGRITYADVOCATE_EXPECTED_STATUS_VALID :
            default:
                $debug && debugging($fxn . "::Found \$this->expectedstatus={$this->expectedstatus} == INTEGRITYADVOCATE_EXPECTED_STATUS_VALID or default");
                if ($not) {
                    $str = 'requires_invalid';
                } else {
                    $str = 'requires_valid';
                }
        }

        $debug && debugging($fxn . "::About to get_string($str)");
        $str = get_string($str, INTEGRITYADVOCATE_AVAILABILITY_NAME, $modname);
        $debug && debugging($fxn . "::Got str={$str}");

        if (!$cache->set($cachekey, $str)) {
            throw new \Exception('Failed to set value in perrequest cache');
        }

        return $str;
    }

    /**
     * Obtains a representation of the options of this condition as a string,
     * for debugging.
     *
     * @return string Text representation of parameters
     */
    protected function get_debug_string() {
        switch ($this->expectedstatus) {
            case INTEGRITYADVOCATE_EXPECTED_STATUS_VALID :
                $type = 'valid';
                break;
            case INTEGRITYADVOCATE_EXPECTED_STATUS_INVALID :
                $type = 'invalid';
                break;
            default:
                throw new \coding_exception('Unexpected expectedstatus value');
        }
        return 'cm' . $this->cmid . ' ' . $type;
    }

    /**
     * Called during restore (near end of restore). Updates any necessary ids
     * and writes the updated tree to the database. May output warnings if
     * necessary (e.g. if a course-module cannot be found after restore).
     *
     * @param string $restoreid Restore identifier
     * @param int $courseid Target course id
     * @param \base_logger $logger Logger for any warnings
     * @param string $name Name of this item (for use in warning messages)
     * @return bool True if there was any change
     */
    public function update_after_restore($restoreid, $courseid, \base_logger $logger, $name) {
        global $DB;

        $debug = false;
        $fxn = __CLASS__ . '::' . __FUNCTION__;
        $debug && debugging($fxn . "::Started with \$courseid={$courseid}; \$name={$name}");

        $rec = \restore_dbops::get_backup_ids_record($restoreid, 'course_module', $this->cmid);
        if (!$rec || !$rec->newitemid) {
            // If we are on the same course (e.g. duplicate) then we can just use the existing one.
            if ($DB->record_exists('course_modules', array('id' => $this->cmid, 'course' => $courseid))) {
                return false;
            }
            // Otherwise it's a warning.
            $this->cmid = 0;
            $logger->process('Restored item (' . $name . ') has availability condition on module that was not restored',
                    \backup::LOG_WARNING);
        } else {
            $this->cmid = (int) $rec->newitemid;
        }
        return true;
    }

    /**
     * Used in course/lib.php because we need to disable the completion JS if
     * a completion value affects a conditional activity.
     *
     * @param \stdClass $course Moodle course object
     * @param int $cmid Course-module id
     * @return bool True if this is used in a condition, false otherwise
     */
    public static function completion_value_used($course, $cmid) {
        $debug = false;
        $fxn = __CLASS__ . '::' . __FUNCTION__;
        $debug && debugging($fxn . "::Started with \$course->id={$course->id}; \$cmid={$cmid}");

        // Have we already worked out a list of required completion values
        // for this course? If so just use that.
        if (!array_key_exists($course->id, self::$modsusedincondition)) {
            // We don't have data for this course, build it.
            $modinfo = get_fast_modinfo($course);
            self::$modsusedincondition[$course->id] = [];

            // Activities.
            foreach ($modinfo->cms as $othercm) {
                if (is_null($othercm->availability)) {
                    continue;
                }
                $ci = new \core_availability\info_module($othercm);
                $tree = $ci->get_availability_tree();
                foreach ($tree->get_all_children(INTEGRITYADVOCATE_AVAILABILITY_NAME . '\condition') as $cond) {
                    self::$modsusedincondition[$course->id][$cond->cmid] = true;
                }
            }

            // Sections.
            foreach ($modinfo->get_section_info_all() as $section) {
                if (is_null($section->availability)) {
                    continue;
                }
                $ci = new \core_availability\info_section($section);
                $tree = $ci->get_availability_tree();
                foreach ($tree->get_all_children(INTEGRITYADVOCATE_AVAILABILITY_NAME . '\condition') as $cond) {
                    self::$modsusedincondition[$course->id][$cond->cmid] = true;
                }
            }
        }
        return array_key_exists($cmid, self::$modsusedincondition[$course->id]);
    }

    /**
     * Wipes the static cache of modules used in a condition (for unit testing).
     */
    public static function wipe_static_cache() {
        self::$modsusedincondition = [];
    }

    /**
     * In rare cases the system may want to change all references to one ID
     * (e.g. one course-module ID) to another one, within a course. This
     * function does that for the conditional availability data for all
     * modules and sections on the course.
     *
     * @param string $table Table name e.g. 'course_modules'
     * @param int $oldid Previous ID
     * @param int $newid New ID
     * @return bool True if anything changed, otherwise false
     */
    public function update_dependency_id($table, $oldid, $newid) {
        $debug = false;
        $fxn = __CLASS__ . '::' . __FUNCTION__;
        $debug && debugging($fxn . "::Started with \$table={$table}; \$oldid={$oldid}; \$newid={$newid}");
        if ($table === 'course_modules' && (int) $this->cmid === (int) $oldid) {
            $this->cmid = $newid;
            return true;
        } else {
            return false;
        }
    }
}
