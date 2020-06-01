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
 * Privacy Subsystem implementation for availability_integrityadvocate.
 *
 * @package    availability_integrityadvocate
 * @copyright  IntegrityAdvocate.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_integrityadvocate;

use \core_privacy\local\metadata\collection;
use \core_privacy\local\request;
use block_integrityadvocate\Api as ia_api;
use block_integrityadvocate\MoodleUtility as ia_mu;
use block_integrityadvocate\Utility as ia_u;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/integrityadvocate/lib.php');
require_once(dirname(__FILE__, 3) . '/locallib.php');

class provider implements \core_privacy\local\metadata\provider \core_userlist_provider {

    const PRIVACYMETADATA_STR = 'privacy:metadata';
    const BRNL = "<br>\n";

    /**
     * Get information about the user data stored by this plugin.
     *
     * @param  collection $collection An object for storing metadata.
     * @return collection The metadata.
     */
    public static function get_metadata(collection $collection): collection {
        $privacyitems = array(
            // Course info.
            'cmid',
            // Moodle user info.
            'userid',
        );

        // Combine the above keys with corresponding values into a new key-value array.
        $privacyitemsarr = array();
        foreach ($privacyitems as $key) {
            $privacyitemsarr[$key] = self::PRIVACYMETADATA_STR . ':' . INTEGRITYADVOCATE_AVAILABILITY_NAME . ':' . $key;
        }

        $collection->add_external_location_link(INTEGRITYADVOCATE_AVAILABILITY_NAME, $privacyitemsarr,
                self::PRIVACYMETADATA_STR . ':' . INTEGRITYADVOCATE_AVAILABILITY_NAME);

        return $collection;
    }

    /**
     * Get the list of users who have data within a context.
     * This will include users who are no longer enrolled in the context if they still have remote IA participant data.
     *
     * @param   \userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(\userlist $userlist) {
        global $DB;
        $debug = false;
        $fxn = __CLASS__ . '::' . __FUNCTION__;
        $debug && ia_mu::log($fxn . '::Started with $userlist=' . var_export($userlist, true));

        if (empty($userlist->count())) {
            return;
        }

        $modulecontext = $userlist->get_context();
        if ($modulecontext->contextlevel !== CONTEXT_MODULE) {
            return;
        }

        $blockinstance = ia_mu::get_first_block($modulecontext, INTEGRITYADVOCATE_BLOCK_NAME, false);
        if (!$blockinstance) {
            return;
        }

        $userlist->add_users(\block_integrityadvocate\provider::get_participants_from_blockcontext($blockinstance->context));
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(\approved_userlist $userlist) {
        $debug = false;
        $fxn = __CLASS__ . '::' . __FUNCTION__;
        $debug && ia_mu::log($fxn . '::Started with $userlist=' . var_export($userlist, true));

        if (empty($userlist->count())) {
            return;
        }

        $modulecontext = $userlist->get_context();
        if ($modulecontext->contextlevel !== CONTEXT_MODULE) {
            return;
        }

        $blockinstance = ia_mu::get_first_block($modulecontext, INTEGRITYADVOCATE_BLOCK_NAME, false);
        if (!$blockinstance) {
            return;
        }

        // Get IA participant data from the remote API.
        $participants = \block_integrityadvocate_get_participants_for_blockcontext($blockinstance->context);
        $debug && ia_mu::log($fxn . '::Got count($participants)=' . (is_countable($participants) ? count($participants) : 0));
        if (ia_u::is_empty($participants) || ia_u::is_empty($userlist) || ia_u::is_empty($userids = $userlist->get_userids())) {
            return;
        }

        // If we got participants, we are in the block context and the parent is a module.
        \block_integrityadvocate\provider::delete_participants($blockinstance->context, $participants, $userids);
    }

    /**
     * Delete all personal data for all users in the specified context.
     *
     * @param context $context Context to delete data from.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        $debug = false;
        $fxn = __CLASS__ . '::' . __FUNCTION__;
        $debug && ia_mu::log($fxn . '::Started with $context=' . var_export($context, true));

        $modulecontext = $userlist->get_context();
        if ($modulecontext->contextlevel !== CONTEXT_MODULE) {
            return;
        }

        $blockinstance = ia_mu::get_first_block($modulecontext, INTEGRITYADVOCATE_BLOCK_NAME, false);
        if (!$blockinstance) {
            return;
        }

        \block_integrityadvocate\provider::delete_data_for_all_users_in_context($blockinstance->context);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param   approved_contextlist    $contextlist    The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(\approved_contextlist $contextlist) {
        $debug = false;
        $fxn = __CLASS__ . '::' . __FUNCTION__;
        $debug && ia_mu::log($fxn . '::Started with $contextlist=' . var_export($contextlist, true));

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();

        foreach ($contextlist->get_contexts() as $context) {
            // Get IA participant data from the remote API.
            $participants = \block_integrityadvocate_get_participants_for_blockcontext($context);
            $debug && ia_mu::log($fxn . '::Got count($participants)=' . (is_countable($participants) ? count($participants) : 0));
            if (ia_u::is_empty($participants)) {
                continue;
            }

            // If we got participants, we are in the block context and the parent is a module.
            \block_integrityadvocate\provider::delete_participants($context, $participants, array($user->id));
        }
    }

}
