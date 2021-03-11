<?php

$plugins->add_hook("global_start", "downloadthread_global_start");
$plugins->add_hook("showthread_start", "downloadthread_showthread_start");
$plugins->add_hook("admin_formcontainer_end", "download_admin_formcontainer_end");
$plugins->add_hook("admin_user_groups_edit_commit", "downloadthread_admin_user_groups_edit_commit");

function downloadthread_global_start()
{
    global $templatelist;
    if(!defined("THIS_SCRIPT"))
    {
        return;
    }
    if(THIS_SCRIPT == "showthread.php")
    {
        $templatelist .= ",downloadthread_form,downloadthread_thread,downloadthread_post";
    }
}

function downloadthread_showthread_start()
{
    global $mybb, $db, $forum, $thread, $lang, $templates;
    $lang->load("downloadthread");
    if($mybb->get_input("downloadthread", MyBB::INPUT_INT) == 1 && $mybb->request_method == "post" && verify_post_check($mybb->get_input("my_post_key")))
    {
        if($mybb->settings['downloadthread_forums'] != -1 && !in_array($thread['fid'], explode(',', (string)$mybb->settings['downloadthread_forums'])))
        {
            error($lang->downloadthread_download_disabled);
        }
        else if(!$mybb->usergroup['dlt_candlthread'])
        {
            error($lang->downloadthread_usergroup_no_permission);
        }
        else
        {
            $tid = $mybb->get_input("tid", MyBB::INPUT_INT);
            $query = $db->simple_select("posts", "pid,username,dateline,message", "tid=" . $tid, array("order_by" => "pid", "order_dir" => "asc"));
            $posts = array();
            $safe_name = str_replace(' ', '-', $thread['subject']);
            $safe_name = preg_replace('([^A-Za-z0-9_-])', '', $safe_name);
            $safe_name = str_replace("--", "-", $safe_name);
            if ($mybb->get_input("format") == "json")
            {
                while ($post = $db->fetch_array($query))
                {
                    $posts[$post['pid']] = $post;
                }
                $json = json_encode($posts);
                $content = $json;
                $contenttype = 'application/json';
                $fname = $safe_name . '.json';
            }
            else if($mybb->get_input("format") == "html")
            {
                // They want HTML so we need to include the parser.
                require_once MYBB_ROOT . "inc/class_parser.php";
                $parser = new postParser;
                $parser_options = array(
                    "allow_html" => $forum['allowhtml'],
                    "allow_mycode" => $forum['allowmycode'],
                    "allow_smilies" => $forum['allowsmilies'],
                    "allow_imgcode" => $forum['allowimgcode'],
                    "allow_videocode" => $forum['allowvideocode'],
                    "filter_badwords" => 1
                );

                $threadposts = "";
                while ($post = $db->fetch_array($query))
                {
                    $post['message'] = $parser->parse_message($post['message'], $parser_options);
                    $post['time'] = my_date("relative", $post['dateline']);
                    eval("\$threadposts .= \"" . $templates->get("downloadthread_post") . "\";");
                }
                eval("\$html = \"" . $templates->get("downloadthread_thread") . "\";");
                $content = $html;
                $contenttype = 'text/html';
                $fname = $safe_name . '.html';
            }
            else
            {
                // CSV Format
                $content = "";
                $first = true;
                while($post = $db->fetch_array($query))
                {
                    if($first)
                    {
                        $first = false;
                        $content = implode(",", array_keys($post));
                    }
                    $safe_post = array_map(function($value) {return '"'.my_escape_csv($value).'"';}, $post);
                    $content .= "\n" . implode(",", $safe_post);
                }
                $contenttype = "text/csv";
                $fname = $safe_name . '.csv';
            }
            $db->free_result($query);

            $zip = new ZipArchive;
            $res = $zip->open($safe_name . ".zip", ZipArchive::CREATE);
            if(!$res)
            {
                header('Content-Description: File Transfer');
                header("Content-Disposition: attachment; filename=$fname");
                header("Content-type: $contenttype");
                echo $content;
                exit;
            }
            else
            {
                // Successfully created zip file.
                $contenttype = "application/zip";
                $zip->addFromString($fname, $content);
                $zip->close();
                $fname = $safe_name . ".zip";
                header('Content-Description: File Transfer');
                header("Content-Disposition: attachment; filename=$fname");
                header("Content-type: $contenttype");
                header("Content-Length: " . filesize($fname));
                readfile($fname);
                exit;
            }
        }
    }
    else
    {
        global $downloadthread;
        if($mybb->settings['downloadthread_forums'] != -1 && !in_array($thread['fid'], explode(',', (string)$mybb->settings['downloadthread_forums'])))
        {
            $downloadthread = "";
        }
        else if(!$mybb->usergroup['dlt_candlthread'])
        {
            $downloadthread = "";
        }
        else
        {
            eval("\$downloadthread =\"".$templates->get("downloadthread_form")."\";");
        }
    }
}

function download_admin_formcontainer_end()
{
    global $mybb, $lang, $form, $form_container, $groupscache;

    $gid = $mybb->get_input('gid', MyBB::INPUT_INT);
    $usergroup = $groupscache[$gid];

    $cbx_opts = array('id' => 'id_dlt_candlthread');
    if ($usergroup['dlt_candlthread']) {
        $cbx_opts['checked'] = true;
    }
    if (!empty($form_container->_title) && !empty($lang->forums_posts) && $form_container->_title == $lang->forums_posts) {
        $form_container->output_row('Downloading Options', "", '<div class="group_settings_bit">'.$form->generate_check_box('dlt_candlthread', '1', "Can download threads?", $cbx_opts).'</div>');
    }
}

function downloadthread_admin_user_groups_edit_commit()
{
    global $mybb, $updated_group;

    $updated_group['dlt_candlthread'] = $mybb->get_input('dlt_candlthread', MyBB::INPUT_INT);
}
