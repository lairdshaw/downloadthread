<?php

function downloadthread_db_install()
{
    global $db;
    if (!$db->field_exists('dlt_candlthread', 'usergroups'))
    {
        // By default, all members can download threads
        $db->add_column('usergroups', 'dlt_candlthread', "tinyint(1) NOT NULL DEFAULT '1'");
    }
}

function downloadthread_db_update()
{
    // Leave blank in this release.
}

function downloadthread_db_uninstall()
{
    global $db;
    if ($db->field_exists('dlt_candlthread', 'usergroups'))
    {
        $db->drop_column('usergroups', 'dlt_candlthread');
    }
}
