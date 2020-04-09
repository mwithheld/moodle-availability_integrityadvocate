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
 * Language strings.
 *
 * @package availability_integrityadvocate
 * @copyright IntegrityAdvocate.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// Put this string first for re-use below.
$string['title'] = 'IntegrityAdvocate';

// Other strings go here.
$string['description'] = 'Require ' . $string['title'] . ' proctor completion';
$string['error_selectcmid'] = 'You must select an activity for the completion condition.';
$string['label_cm'] = 'Activity or resource';
$string['label_validation_status'] = 'Required IA validation status';
$string['label_completion'] = 'Required IA validation status';
$string['missing'] = '(Missing activity)';
$string['option_valid'] = 'must be marked valid';
$string['option_invalid'] = 'must be marked invalid';
$string['pluginname'] = 'Restriction by ' . $string['title'];
$string['requires_valid'] = 'The IA result for activity <strong>{$a}</strong> is valid';
$string['requires_invalid'] = 'The IA result for activity <strong>{$a}</strong> is marked invalid';
$string['privacy:metadata'] = 'The ' . $string['pluginname'] . ' plugin does not store any personal data.';
