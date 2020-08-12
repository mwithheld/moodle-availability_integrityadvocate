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
 * Privacy Subsystem  for availability_integrityadvocate.
 *
 * @package    availability_integrityadvocate
 * @copyright  IntegrityAdvocate.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_integrityadvocate\privacy;

use \core_privacy\local\metadata\collection;
use \core_privacy\local\request\approved_contextlist;
use \core_privacy\local\request\approved_userlist;
use \core_privacy\local\request\contextlist;
use \core_privacy\local\request\userlist;
use block_integrityadvocate\MoodleUtility as ia_mu;
use block_integrityadvocate\Utility as ia_u;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/integrityadvocate/lib.php');
require_once(dirname(__FILE__, 3) . '/locallib.php');

/**
 * Privacy Subsystem for availability_integrityadvocate.
 */
class provider implements \core_privacy\local\metadata\provider,
        \core_privacy\local\request\core_userlist_provider,
        \core_privacy\local\request\plugin\provider {

    /** @var string Re-usable name for this medatadata */
    const PRIVACYMETADATA_STR = 'privacy:metadata';

    /** @var str HTML linebreak */
    const BRNL = "<br>\n";

    /**
     * Get information about the user data stored by this plugin.
     * This does not include data that is set on the remote API side.
     *
     * @param  collection $collection An object for storing metadata.
     * @return collection The metadata.
     */
    public static function get_metadata(collection $collection): collection {
        $debug = false;
        $fxn = __CLASS__ . '::' . __FUNCTION__;
        $debug && ia_mu::log($fxn . '::Started with $collection=' . var_export($collection, true));

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
                self::PRIVACYMETADATA_STR . ':' . INTEGRITYADVOCATE_AVAILABILITY_NAME . ':tableexplanation');

        return $collection;
    }

    /**
     * Get the list of users who have data within a context.
     * This will include users who are no longer enrolled in the context if they still have remote IA participant data.
     *
     * @param   \userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $debug = false;
        $fxn = __CLASS__ . '::' . __FUNCTION__;
        $debug && ia_mu::log($fxn . '::Started with $userlist=' . var_export($userlist, true));

        if (empty($userlist->count())) {
            return;
        }

        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }

        $blockinstance = ia_mu::get_first_block($context, INTEGRITYADVOCATE_BLOCK_NAME, false);
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
    public static function delete_data_for_users(\core_privacy\local\request\approved_userlist $userlist) {
        $debug = false;
        $fxn = __CLASS__ . '::' . __FUNCTION__;
        $debug && ia_mu::log($fxn . '::Started with $userlist=' . var_export($userlist, true));

        if (empty($userlist->count())) {
            return;
        }

        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }

        $blockinstance = ia_mu::get_first_block($context, INTEGRITYADVOCATE_BLOCK_NAME, false);
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

        if (!($context instanceof \context_module)) {
            return;
        }

        $blockinstance = ia_mu::get_first_block($context, INTEGRITYADVOCATE_BLOCK_NAME, false);
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
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        $debug = false;
        $fxn = __CLASS__ . '::' . __FUNCTION__;
        $debug && ia_mu::log($fxn . '::Started with $contextlist=' . var_export($contextlist, true));

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();
        if (!isset($user->id)) {
            return;
        }

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

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param   int           $userid       The user to search.
     * @return  contextlist   $contextlist  The list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        // Gets all IA blocks in the site.
        $blockinstances = ia_mu::get_all_blocks(\INTEGRITYADVOCATE_SHORTNAME, false);

        $contextlist = new contextlist();
        if (empty($blockinstances)) {
            return $contextlist;
        }

        // For each visible IA block instance, get the context id.
        $contextids = array();
        foreach ($blockinstances as $b) {
            $blockcontext = $b->context;
            $parentcontext = $blockcontext->get_parent_context();
            // We only have data for IA blocks in modules.
            if (intval($parentcontext->contextlevel) !== intval(CONTEXT_MODULE)) {
                continue;
            }
            if (\is_enrolled($parentcontext, $userid)) {
                $contextids[] = $b->context->id;
            }
        }

        if (empty($contextids)) {
            return $contextlist;
        }
        $contextlist->add_user_contexts($contextids);

        return $contextlist;
    }

    /**
     * Export all user data for the specified user, in the specified contexts, using the supplied exporter instance.
     *
     * @param   approved_contextlist    $contextlist    The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        if (empty($contextlist->count())) {
            return;
        }
        $user = $contextlist->get_user();
        if (!isset($user->id)) {
            return;
        }

        foreach ($contextlist->get_contexts() as $context) {
            // Get IA participant data from the remote API.
            $participants = \block_integrityadvocate_get_participants_for_blockcontext($context);
            if (ia_u::is_empty($participants)) {
                continue;
            }

            // If we got participants, we are in the block context and the parent is a module.
            if (isset($participants[$user->id]) && !empty($p = $participants[$user->id])) {
                $data = (object) \block_integrityadvocate\provider::get_participant_info_for_export($p);
                \core_privacy\local\request\writer::with_context($context)->export_data([INTEGRITYADVOCATE_BLOCK_NAME], $data);
            }
        }
    }

}
