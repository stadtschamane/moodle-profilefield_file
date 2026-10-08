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
 * Strings for component 'profilefield_file', language 'en'.
 *
 * @package   profilefield_file
 * @copyright 2014 onwards Shamim Rezaie  {@link http://foodle.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['forceunique'] = 'Should the data be unique?';
$string['forceunique_error'] = 'This field should be set to \'No\'.';
$string['forceunique_help'] = 'This property is not applicable in File user profile field. Set to \'No\'.';
$string['pluginname'] = 'File upload';
$string['signup'] = 'Display on signup page?';
$string['signup_error'] = 'This field should be set to \'No\' for security reasons.';
$string['signup_help'] = 'File fields cannot be displayed on signup page for security reasons. Moodle never ever allows guests/not logged in users to upload anything or use filemanager element at all.';

// Strings added in v1.1.0 (stadtschamane maintained fork). Existing string IDs are never modified.
$string['maximumfileslabel'] = 'Maximum number of files';
$string['maximumfileslabel_help'] = 'The maximum number of files a user can upload into this profile field.';
$string['nouploadedfiles'] = 'Files are managed by an administrator. No files have been uploaded yet.';
$string['privacy:metadata:profilefield_file:userid'] = 'The ID of the user whose data is stored by the File upload user profile field';
$string['privacy:metadata:profilefield_file:fieldid'] = 'The ID of the profile field';
$string['privacy:metadata:profilefield_file:data'] = 'Whether files have been uploaded for the File upload user profile field';
$string['privacy:metadata:profilefield_file:tableexplanation'] = 'Flag record for uploaded profile files';
$string['privacy:metadata:profilefield_file:filessubsystem'] = 'The uploaded files are stored by the core file storage system, in the profile fields file areas of the user context.';
