<?php

require_once "downloadthread/hooks.php";

function downloadthread_info()
{
    $author1link = "<a href=\"https://community.mybb.com/user-24419.html\" target=\"_blank\">Dragonexpert</a>";
    $author2link = "<a href=\"https://community.mybb.com/user-116662.html\" target=\"_blank\">Laird</a>";
    return array(
        "name"	=> "Download Thread",
        "description" => "Enables users to download a thread.",
        "author" => $author1link . " &amp; " . $author2link,
        "version" => "1.0",
        "codename" 	=> "downloadthread",
        "compatibility"	=> "18*"
    );
}

function downloadthread_install()
{
    global $db, $cache, $groupscache;

    require_once "downloadthread/settings.php";
    downloadthread_settings_install();
    require_once "downloadthread/db.php";
    downloadthread_db_install();
    $cache->update_usergroups();
    $groupscache = $cache->read('usergroups');
}

function downloadthread_is_installed()
{
    global $cache;
    $active_plugins = $cache->read("plugins");
    return isset($active_plugins['active']['downloadthread']);
}

function downloadthread_activate()
{
    require_once "downloadthread/templates.php";
    downloadthread_templates_install();
    require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';
    find_replace_templatesets('showthread', '({\\$addpoll})', "{\$addpoll}\n\t\t\t{\$downloadthread}");
}

function downloadthread_deactivate()
{
    require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';
    find_replace_templatesets('showthread', '(\\r?\\n\\t\\t\\t{\\$downloadthread})', '', 0);
    require_once "downloadthread/templates.php";
    downloadthread_templates_uninstall();
}

function downloadthread_uninstall()
{
    global $db, $cache, $groupscache;

    require_once "downloadthread/settings.php";
    downloadthread_settings_uninstall();
    require_once "downloadthread/db.php";
    downloadthread_db_uninstall();
    $cache->update_usergroups();
    $groupscache = $cache->read('usergroups');
}
