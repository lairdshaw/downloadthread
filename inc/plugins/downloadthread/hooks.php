<?php

$plugins->add_hook("showthread_start"             , "downloadthread_showthread_start"             );
$plugins->add_hook("admin_formcontainer_end"      , "download_admin_formcontainer_end"            );
$plugins->add_hook("admin_user_groups_edit_commit", "downloadthread_admin_user_groups_edit_commit");

function downloadthread_showthread_start()
{
    global $mybb, $db, $forum, $thread;
    if($mybb->get_input("downloadthread", MyBB::INPUT_INT) == 1 && $mybb->request_method == "post" && verify_post_check($mybb->get_input("my_post_key")))
    {
        if($mybb->settings['downloadthread_forums'] != -1 && !in_array($thread['fid'], explode(',', (string)$mybb->settings['downloadthread_forums'])))
        {
            /** @todo Create a language file and move all hard-coded strings like this into it. */
            error('Thread downloading is disabled for this forum.');
        }
        else if(!$mybb->usergroup['dlt_candlthread'])
        {
            error('Your user group does not have permission to download threads.');
        }

        $tid = $mybb->get_input("tid", MyBB::INPUT_INT);
        $query = $db->simple_select("posts", "pid,username,dateline,message", "tid=" . $tid, array("order_by" => "pid", "order_dir" => "asc"));
        $posts = array();
        if($mybb->get_input("format") == "json")
        {
            while($post = $db->fetch_array($query))
            {
                $posts[$post['pid']] = $post;
            }
            $json = json_encode($posts);
            $content = $json;
            $contenttype = 'application/json';
            $fname = 'thread-download.json';
        }
        else
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

            $html = "<html><head><title>" . $thread['subject'] . "</title></head><body><table border='1px solid black' width='80%'>";
            while($post = $db->fetch_array($query))
            {
                $post['message'] = $parser->parse_message($post['message'], $parser_options);
                $post['time'] = my_date("relative", $post['dateline']);
                $html .= "<tr><td>" . $post['username'] . "</td>";
                $html .= "<td>" . $post['message'] . "</td>";
                $html .= "<td>" . $post['time'] . "</td></tr>";
            }
            $html .= "</table></body</html>";
            $content = $html;
            $contenttype = 'text/html';
            $fname = 'thread-download.html';
        }
        $db->free_result($query);

        header('Content-Description: File Transfer');
        header("Content-Disposition: attachment; filename=$fname");
        header("Content-type: $contenttype");
        echo $content;
        exit;
    }
    else
    {
        global $downloadthread;

        $downloadthread = '<li style="background-image: none;">Download thread [<form method="post" action="showthread.php?downloadthread=1&amp;tid='.$thread['tid'].'" style="display: inline;"><input type="hidden" name="my_post_key" value="'.$mybb->post_code.'" /><input type="submit" style="background: none; border: none; color: #0072BC; font-family: Tahoma, Verdana, Arial, Sans-Serif; cursor: pointer; display: inline; margin: 0; padding: 0; font-size: 11px;" name="format" value="json" /> | <input type="submit" style="background: none; border: none; color: #0072BC; font-family: Tahoma, Verdana, Arial, Sans-Serif; cursor: pointer; display: inline; margin: 0; padding: 0; font-size: 11px;" name="format" value="html" /></form>]</li>';
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