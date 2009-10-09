<?php
header('Content-Type: text/html; charset=utf-8'); 

require_once('./config.php');

if ($_GET['visitors']) {
    // the `data` column in the mint__config table holds a serialized PHP array
    $query = mysql_query("SELECT data FROM `".$mint_prefix."__config;");
    $result = mysql_fetch_row($query);
    foreach ($result as $field) {
        
        // unserialize the $field
        $f = unserialize($field);
        
        // $f is a associative array, sorted ascending by date.
        // by pop'ing the list twice, we get the stats for today and yesterday
        $today = array_pop($f[0]['visits'][2]);
        $yesterday = array_pop($f[0]['visits'][2]);
        $ret = array();
        $ret['today'] = array("unique" => $today['unique'], "total" => $today['total']);
        $ret['yesterday'] = array("unique" => $yesterday['unique'], "total" => $yesterday['total']);

        // echo the results as json, so that jQuery easily can pick it up.
        echo json_encode($ret);
    }
}

if ($_GET['referers']) {
    $t = time();
    // Get the referers that sent most traffic the last hour. Exclude local referers.
    $query = "SELECT `referer`, `resource`, `resource_title`, COUNT(`referer`) as `total`, `dt`
    			FROM `".$mint_prefix."_visit` 
    			WHERE `referer_is_local` = 0 AND `search_terms` = '' AND dt > $t-(60*60)
    			GROUP BY `referer_checksum` 
    			ORDER BY `total` DESC, `dt` DESC 
    			LIMIT 0,5";

    $query = mysql_query($query);
    $i = 0;
    while ($r = mysql_fetch_array($query)) {
        if ($odd = $i%2) {
            $class =' class="odd"';
        } else {
            $class = '';
        }
        $i++;
        // remove http://www from the referer, and 
        // trim the remaining value to 10 characters for display's sake.
        $ref = str_replace("http://", "", urldecode($r["referer"]));
        $ref = str_replace("www.", "", $ref);
        $ref = substr($ref, 0, 10);
        
        // You might not need to do this, but I did.. :-)
        $resource_title = substr(html_entity_decode($r['resource_title'], ENT_QUOTES, 'UTF-8'), 0, 28);
        $s = <<<END
        <dl$class>
            <dt>
                <span>$r[total]</span> fra <span>$ref</span>
                til <span>$resource_title</span>
            </dt>
            <dd></dd>
            <div class="clearer"></div>
        </dl>
END;
        echo $s;
    }
}

if ($_GET['popular']) {
    $t = time();
    // Get the most popular (most read) posts the last 72 hours, exclude the front page.
    $query = "SELECT `resource`, `resource_checksum`, `resource_title`, COUNT(`resource_checksum`) as `total`, `dt`
    			FROM `".$mint_prefix."_visit` WHERE dt > $t-(72*60*60) AND `resource`!='".$front_page_url."'
    			GROUP BY `resource_checksum` 
    			ORDER BY `total` DESC, `dt` DESC
    			LIMIT 0,5";
    $query = mysql_query($query);
    $i = 0;
    while ($r = mysql_fetch_array($query)) {
        if ($odd = $i%2) {
            $class =' class="odd"';
        } else {
            $class = '';
        }
        $i++;
        // You might not need to do this, but I did.. :-)
        $resource_title = html_entity_decode($r['resource_title'], ENT_QUOTES, 'UTF-8');
        echo <<<END
            <dl$class>
                <dt>$resource_title</dt>
                <dd>$r[total]</dd>
                <div class="clearer"></div>
            </dl>
END;
    }
}


if ($_GET['comments']) {
    $doc = simplexml_load_file($rss_url);
    $twitter = simplexml_load_file($twitter_search_url);
    $i = 0;
    $ret = array();
    $ret['comments'] = array();

    foreach($twitter->entry as $item) {
        if ($i == 3) {
            $i = 0;
            break;
        }
        $comment = <<<END
        <div class="comment">
        <p>$item->content</p>
END;
        $comment .= '<p class="author">Av @'.$item->author->name.'</p></div>';
        $ret['comments'][] = $comment;
    $i++;
    }

    foreach ($doc->channel->item as $item) {
        if ($i == 0) {
            $ret['last_comment'] = date('H:i', strtotime($item->pubDate));
        }
        if ($i == 3) {
            $i = 0;
            break;
        }
        $title = str_replace('Comment on', '<span>Kommentar til</span>', $item->title);
        $description = substr($item->description, 0, 300);
        $comment = <<<END
        <div class="comment">
        <p>$description</p>
        <p class="author">– $title</p>
        </div>
END;
        $ret['comments'][] = $comment;
    $i++;
    }
    echo json_encode($ret);
}

?>
