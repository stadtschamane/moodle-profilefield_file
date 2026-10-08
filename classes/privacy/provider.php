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
 * Privacy class for requesting user data.
 *
 * @package    profilefield_file
 * @copyright  2026 Robert Sack {@link https://github.com/stadtschamane}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace profilefield_file\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\writer;

/**
 * Privacy class for requesting user data.
 *
 * @copyright  2026 Robert Sack {@link https://github.com/stadtschamane}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    core_privacy\local\metadata\provider,
    core_privacy\local\request\core_userlist_provider,
    core_privacy\local\request\plugin\provider {

    /**
     * Returns meta data about this system.
     *
     * @param   collection $collection The initialised collection to add items to.
     * @return  collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {
        // This plugin stores uploaded files in the core file storage and a
        // flag ('0'/'1') in the user_info_data table.
        $collection->add_database_table('user_info_data', [
            'userid' => 'privacy:metadata:profilefield_file:userid',
            'fieldid' => 'privacy:metadata:profilefield_file:fieldid',
            'data' => 'privacy:metadata:profilefield_file:data',
        ], 'privacy:metadata:profilefield_file:tableexplanation');

        // The uploaded files themselves are stored in the core file storage.
        $collection->add_subsystem('core_files',
            'privacy:metadata:profilefield_file:filessubsystem');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param   int         $userid     The user to search.
     * @return  contextlist $contextlist  The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $sql = "SELECT ctx.id
                  FROM {user_info_data} uda
                  JOIN {user_info_field} uif ON uda.fieldid = uif.id
                  JOIN {context} ctx ON ctx.instanceid = uda.userid
                       AND ctx.contextlevel = :contextlevel
                 WHERE uda.userid = :userid
                       AND uif.datatype = :datatype";
        $params = [
            'userid' => $userid,
            'contextlevel' => CONTEXT_USER,
            'datatype' => 'file',
        ];
        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users within a specific context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_user) {
            return;
        }

        $sql = "SELECT uda.userid
                  FROM {user_info_data} uda
                  JOIN {user_info_field} uif
                       ON uda.fieldid = uif.id
                 WHERE uda.userid = :userid
                       AND uif.datatype = :datatype";

        $params = [
            'userid' => $context->instanceid,
            'datatype' => 'file',
        ];

        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        $user = $contextlist->get_user();

        foreach ($contextlist->get_contexts() as $context) {
            // Check if the context is a user context.
            if ($context->contextlevel == CONTEXT_USER && $context->instanceid == $user->id) {
                // Export the field values (the 0/1 flags) as user data.
                $records = $DB->get_records_sql("
                    SELECT uda.id, uif.name, uda.data
                      FROM {user_info_data} uda
                      JOIN {user_info_field} uif ON uda.fieldid = uif.id
                     WHERE uda.userid = :userid
                           AND uif.datatype = :datatype", [
                    'userid' => $user->id,
                    'datatype' => 'file']);
                foreach ($records as $record) {
                    $data = (object) [
                        'name' => $record->name,
                        'hasfiles' => $record->data,
                    ];
                    writer::with_context($context)->export_data(
                        [get_string('pluginname', 'profilefield_file')], $data);
                }

                // Export the actual uploaded files from the file storage.
                $fs = get_file_storage();
                $fields = $DB->get_fieldset_select('user_info_field', 'id', "datatype = :datatype",
                    ['datatype' => 'file']);
                foreach ($fields as $fieldid) {
                    $files = $fs->get_area_files($context->id,
                        'profilefield_file', "files_{$fieldid}", 0,
                        'timemodified', false);
                    foreach ($files as $file) {
                        writer::with_context($context)->export_file(
                            [get_string('pluginname', 'profilefield_file')], $file);
                    }
                }
            }
        }
    }

    /**
     * Delete all user data which matches the specified context.
     *
     * @param   context $context A user context.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        // Delete data only for user context.
        if ($context->contextlevel == CONTEXT_USER) {
            static::delete_data($context->instanceid);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        $context = $userlist->get_context();

        if ($context instanceof \context_user) {
            static::delete_data($context->instanceid);
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param   approved_contextlist    $contextlist    The approved contexts to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        $user = $contextlist->get_user();
        foreach ($contextlist->get_contexts() as $context) {
            // Check if the context is a user context.
            if ($context->contextlevel == CONTEXT_USER && $context->instanceid == $user->id) {
                static::delete_data($user->id);
            }
        }
    }

    /**
     * Delete data related to a userid.
     *
     * @param  int $userid The user ID
     */
    protected static function delete_data($userid) {
        global $DB;

        // Find the file fields and delete the file areas for this user.
        $fields = $DB->get_fieldset_select('user_info_field', 'id', "datatype = :datatype",
            ['datatype' => 'file']);
        $fs = get_file_storage();
        $usercontext = \context_user::instance($userid);
        foreach ($fields as $fieldid) {
            $fs->delete_area_files($usercontext->id, 'profilefield_file', "files_{$fieldid}");
        }

        // Delete the flag records.
        $DB->delete_records_select('user_info_data', "fieldid IN (
                SELECT id FROM {user_info_field} WHERE datatype = :datatype)
                AND userid = :userid", [
            'userid' => $userid,
            'datatype' => 'file']);
    }
}