<?php

$plugins->add_hook("showthread_start", "downloadthread_showthread_start");

function downloadthread_showthread_start()
{
    global $mybb, $db, $forum;
    if ($mybb->get_input("downloadthread", MyBB::INPUT_INT) == 1 && $mybb->request_method == "post" && verify_post_check($mybb->get_input("my_post_key")))
    {
        $tid = $mybb->get_input("tid", MyBB::INPUT_INT);
        $query = $db->simple_select("posts", "pid,username,dateline,message", "tid=" . $tid);
        $posts = array();
        if($mybb->get_input("format") == "json")
        {
            while($post = $db->fetch_array($query))
            {
                $posts[$post['pid']] = $post;
            }
            $html = json_encode($posts);
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

            global $thread;
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
        }
        // TODO actually download the file.
        $db->free_result($query);
    }
}