<?php
require_once(dirname(__FILE__, 4) . '/config.php');
require_once(dirname(__FILE__, 4) . '/blocks/integrityadvocate/lib.php');

/**
 * Find out if a block type is known by the system.
 * Adapted from lib/blocklib.php::is_known_block_type()
 *
 * @return boolean true if this block in installed.
 */
function availability_integrityadvocate_is_known_block_type() {
    global $DB;

    //($table, array $conditions=null)
    $count = $DB->count_records('block', array('visible' => 1, 'name' => 'integrityadvocate'));

    return $count > 0;
}
