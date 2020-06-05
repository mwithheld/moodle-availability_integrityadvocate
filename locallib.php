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
 * @package    availability_integrityadvocate
 * @copyright  IntegrityAdvocate.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__, 4) . '/config.php');
require_once(dirname(__FILE__, 4) . '/blocks/integrityadvocate/lib.php');

require_login();

/** @var string Longer name for this plugin. */
const INTEGRITYADVOCATE_AVAILABILITY_NAME = 'availability_integrityadvocate';

/**
 * Find out if a block type is known by the system.
 * Adapted from lib/blocklib.php::is_known_block_type()
 *
 * @return boolean true if this block in installed.
 */
function availability_integrityadvocate_is_known_block_type() {
    global $DB;

    $count = $DB->count_records('block', array('visible' => 1, 'name' => 'integrityadvocate'));

    return $count > 0;
}
