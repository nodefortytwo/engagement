<?php
define('FB_TOKEN', 'AAAAAAITEghMBANUnDvub0V8mx0YiXRXSvZCisH4Pe6aFbVIME2UmnPy9bt1KE2KIpvHZAxb0EzhKOdijCgVw0T7tsXWdjtcLYUJ3EZBfMCqDZCkvVZAYH');
define('FB_GURL', 'https://graph.facebook.com');
global $last_request;
function engagement_register_sites() {
    return array('name' => 'Engagement', 'path' => 'engagement', 'database' => 'engagement');
}

function engagement_routes() {
    $paths = array();
    $paths['engagement'] = array('callback' => 'engagement_landing');
    $paths['engagement/page'] = array('callback' => 'engagement_page');
    $paths['engagement/api/update_page'] = array('callback' => 'engagement_api_update_page');
    $paths['engagement/api/update_posts'] = array('callback' => 'engagement_api_update_posts');
    return $paths;
}

function engagement_landing() {
    db()->select_db('engagement');
    process_pages();
    process_posts();
    $page = new Template();
    $page->c('<div class="row"><div class="span11"><div class="well"><form method="post">
    <h2>Add Page</h2>
    <input type="text" name="url" id="url"/>
    <input type="submit"/>
    </form></div></div></div>');

    if (!empty($_POST)) {
        $fb_page = engagement_get_page($_POST['url']);
        redirect('/engagement', 301, true);
        //$posts = engagement_get_posts($fb_page->id);
    }

    $pages = db()->query('SELECT *, (select count(id) from post WHERE page_id = pages.id) as post_count FROM pages')->fetch_all();
    $page->c('<div class="row"><table class="table span11">');
    $page->c('<tr>
            <th>Page Name</th>
            <th>Talking About</th>
            <th>Likes</th>
            <th>Posts (in our system)</th>
            <th>Engagement</th>
            <th>Page Details Updated</th>
            <th></th>
        </tr>');
    foreach ($pages as $p) {
        $data = json_decode($p['data']);
        //var_dump($data);
        //die();

        $actions = array();
        $actions[] = '<a href="./api/update_page/?fb_page=' . $p['id'] . '">' . 'Update Page Stats' . '</a>';
        $actions[] = '<a href="./api/update_posts/?fb_page=' . $p['id'] . '">' . 'Update Posts' . '</a>';

        $page->c('<tr>');
        $page->c('<td> <a href="page/~/' . $p['id'] . '">' . $p['title'] . '</a></td>');
        $page->c('<td>' . $data->talking_about_count . '</td>');
        $page->c('<td>' . $data->likes . '</td>');
        $page->c('<td>' . $p['post_count'] . '</td>');
        $page->c('<td>' . round($p['engagement'], 3) . '%</td>');
        $page->c('<td>' . template_date($p['updated']) . '</td>');
        $page->c('<td>' . implode(' - ', $actions) . '</td>');
        $page->c('</tr>');
    }
    $page->c('</table></div>');
    return $page->render();
}

function engagement_page($page_id = 0) {
    db()->select_db('engagement');
    if (!$page_id) {
        die('provide a page id');
    }
    $to = '';
    $fto = '';
    if (array_key_exists('to', $_POST)) {
        $fto = $_POST['to'];
        $to = ' AND posted < ' . strtotime($_POST['to']);
    }
    $from = '';
    $ffrom = '';
    if (array_key_exists('from', $_POST)) {
        $ffrom = $_POST['from'];
        $from = ' AND posted > ' . strtotime($_POST['from']);
    }

    $title = db()->query('SELECT title FROM pages WHERE id = ' . $page_id . ';')->fetch_all();
    $title = $title[0]['title'];

    $latest = db()->query('SELECT posted FROM post WHERE page_id = ' . $page_id . ' ORDER BY posted DESC LIMIT 1;')->fetch_all();
    $latest = $latest[0]['posted'];
    $latest = strtotime('yesterday', $latest);

    $oldest = db()->query('SELECT posted FROM post WHERE page_id = ' . $page_id . ' ORDER BY posted ASC LIMIT 1;')->fetch_all();
    $oldest = $oldest[0]['posted'];
    $oldest = strtotime('tomorrow', $oldest);

    $bytype = db()->query('SELECT type, count(*) as count, avg(engagement) as engagement FROM post WHERE page_id = ' . $page_id . $to . $from .' GROUP BY type ORDER BY count desc')->fetch_all();
    $all = db()->query('SELECT type, count(*) AS count, avg(engagement) as engagement FROM post WHERE page_id = ' . $page_id .  $to . $from .' ORDER BY count desc')->fetch_all();
    $html = '<table class="table">';
    $html .= '<tr>';
    $html .= '<th>Post Type</th>';
    $html .= '<th>Posts</th>';
    $html .= '<th>Engagement</th>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td>All</td>';
    $html .= '<td>' . $all[0]['count'] . '</td>';
    $html .= '<td>' . round($all[0]['engagement'], 3) . '%</td>';
    $html .= '</tr>';
    foreach ($bytype as $e) {
        $html .= '<tr>';
        $html .= '<td>' . ucwords($e['type']) . '</td>';
        $html .= '<td>' . $e['count'] . '</td>';
        $html .= '<td>' . round($e['engagement'], 3) . '%</td>';
        $html .= '</tr>';
    }
    $html .= '</table>';
    $page = new Template();
    $page->add_js('script.js', 'engagement');
    $page->c('<div class="row"><div class="span11"><h1>' . $title . ' Stats</h1></div></div>');
    $page->c('<div class="row"><div class="span11"><div class="well">
    <strong>Ealiest: ' . date('d-m-y', $oldest) . ' - Latest: ' . date('d-m-y', $latest) . '</strong><br/><br/>
    <form method="post">
    <label>From:</label>
    <input type="text" name="from" id="from" value="'.$ffrom.'"/>
    <label>To:</label>
    <input type="text" name="to" id="to" value="'.$fto.'"/>
    <input type="submit"/>
    </form></div></div></div>');

    $page->c('<div class="row"><div class="span11">' . $html . '</div></div>');
    return $page->render();
}

function engagement_get_page($url, $update = false) {
    $page = db()->query('SELECT * FROM pages WHERE url = "' . $url . '"')->fetch_all();
    if (!empty($page) && !$update) {
        return json_decode($page[0]['data']);
    } else {
        $page = fb_get_page($url);

        $sql = 'REPLACE INTO pages (id, title, updated, url, data) VALUES (
        ' . $page->id . ', 
        "' . $page->name . '",
        ' . time() . ',
        "' . $url . '",
        "' . mysql_real_escape_string(json_encode($page)) . '")';

        db()->query($sql);
        return $page;
    }
}

function engagement_get_posts($page_id, $update = false) {
    $posts = db()->query('SELECT data FROM post WHERE page_id = ' . $page_id)->fetch_all();
    if (empty($posts) || $update) {
        $posts = fb_get_posts($page_id);

        foreach ($posts as $post) {
            db()->query('REPLACE INTO post (id, page_id, data, updated) VALUES ("' . $post->id . '", ' . $page_id . ', "' . mysql_real_escape_string(json_encode($post)) . '", ' . time() . ')');
        }
        return $posts;
    } else {
        return $posts;
    }
}

function fb_get_page($url) {
    $url = str_replace('http://www.facebook.com/', '', $url);
    $url = str_replace('https://www.facebook.com/', '', $url);

    $graph = FB_GURL . '/' . $url;
    return json_decode(get_data($graph));
}

function fb_get_posts($id, $url = null) {
    $limit = 25;
    if (!$url) {
        $latest = db()->query('SELECT posted FROM post WHERE page_id = ' . $id . ' ORDER BY posted DESC LIMIT 1;')->fetch_all();
        if (!empty($latest)){
            $latest = $latest[0]['posted'];
            $latest = '&since=' . $latest;
        }else{$latest = '';}
        //$latest = '';
        $url = FB_GURL . '/' . $id . '/posts?access_token=' . FB_TOKEN . '&limit=' . $limit . $latest;
    }
    if (!isset($posts)) {
        $posts = array();
    }
    $data = json_decode(get_data($url));
    if (is_object($data) && property_exists($data, 'data')) {
        $posts = array_merge($posts, $data->data);
        if (count($data->data) == $limit) {
            $posts = array_merge($posts, fb_get_posts($id, $data->paging->next));
        }
    }
    return $posts;
}

function process_pages() {
    db()->select_db('engagement');
    $pages = db()->query('SELECT * FROM pages')->fetch_all();
    foreach ($pages as $p) {
        $data = json_decode($p['data']);
        db()->query('UPDATE pages SET likes = ' . $data->likes . ' WHERE id="' . $p['id'] . '"');
    }
}

function process_posts() {
    db()->select_db('engagement');
    $posts = db()->query('SELECT post.*, pages.likes FROM post LEFT JOIN pages ON pages.id = post.page_id;')->fetch_all();
    $averages = array();
    foreach ($posts as $p) {
        $data = json_decode($p['data']);
        $sql = 'UPDATE post SET 
                posted = :posted,
                shares = :shares,
                likes = :likes,
                total_interactions = :total_interactions,
                engagement = :engagement,
                comments = :comments,
                type = ":type",
                status_type = ":status_type"
                WHERE id = ":id"
        ';
        if (!array_key_exists($p['page_id'], $averages)) {
            $averages[$p['page_id']] = array('total' => 0, 'count' => 0, 'average' => 0);
        }
        $args[':posted'] = strtotime($data->created_time);
        $args[':shares'] = 0;
        $args[':likes'] = 0;
        $args[':comments'] = 0;
        $args[':status_type'] = 'not set';
        if (property_exists($data, 'shares')) {
            $args[':shares'] = $data->shares->count;
        }
        if (property_exists($data, 'likes')) {
            $args[':likes'] = $data->likes->count;
        }
        if (property_exists($data, 'comments')) {
            $args[':comments'] = $data->comments->count;
        }
        $args[':type'] = $data->type;
        if (property_exists($data, 'status_type')) {
            $args[':status_type'] = $data->status_type;
        }

        $args[':total_interactions'] = $args[':shares'] + $args[':likes'] + $args[':comments'];
        $args[':engagement'] = $args[':total_interactions'] / $p['likes'] * 100;
        $args[':id'] = $p['id'];
        db()->dquery($sql)->arg($args)->execute();
        $averages[$p['page_id']]['count']++;
        $averages[$p['page_id']]['total'] = $averages[$p['page_id']]['total'] + $args[':engagement'];
        $averages[$p['page_id']]['average'] = $averages[$p['page_id']]['total'] / $averages[$p['page_id']]['count'];

    }
    foreach ($averages as $pid => $avg) {
        db()->query('UPDATE pages SET engagement = ' . $avg['average'] . ' WHERE id="' . $pid . '"');
    }
}


function engagement_api_update_page() {
    db()->select_db('engagement');
    if (!empty($_GET) && !empty($_GET['fb_page'])) {
        $page = db()->query('SELECT url FROM pages WHERE id = ' . $_GET['fb_page'])->fetch_all();
        if (!empty($page)) {
            engagement_get_page($page[0]['url'], true);
        }
    }
    redirect('/engagement', 301, true);
}

function engagement_api_update_posts() {
    db()->select_db('engagement');
    if (!empty($_GET) && !empty($_GET['fb_page'])) {
        engagement_get_posts($_GET['fb_page'], true);
    }
    redirect('/engagement', 301, true);
}
