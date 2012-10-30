<?php
function engagement_init() {
    require 'engagement.page.class.php';
    require 'engagement.user.class.php';
    require 'engagement.post.class.php';
}

function engagement_routes() {
    $r = array();
    $r['page'] = array('callback' => 'engagement_view_page');
    $r['page/add'] = array('callback' => 'engagement_add_page');
    $r['page/get_posts'] = array('callback' => 'engagement_get_posts');
    $r['page/get_posts/json'] = array('callback' => 'engagement_get_posts_json');
    $r['user/process'] = array('callback' => 'engagement_process_users');
    $r['user/process/json'] = array('callback' => 'engagement_process_users_json');
    return $r;
}

function engagement_home() {
    global $facebook;
    $page = new Template();
    $page->c('<div class="row-fluid"><div class="span12"><div class="well"><form method="post" action="' . get_url('/page/add/') . '">
    <h2>Add Page</h2>
    <input type="text" name="url" id="url">
    <input type="submit"/>
    </form></div></div></div>');
    $page->c('<div class="row-fluid"><strong>'.l('Auth Facebook', get_url('/facebook/auth/')).'</strong></div>');
    $page->c('<div class="row-fluid"><strong>'.$facebook->getAccessToken().'</strong></div>');
    $page->c('<div class="row-fluid">' . engagement_page_table() . '</div>');
    return $page->render();
}


function engagement_view_page($id){
    $template = new Template();
    $page = new Page($id, true);
    $template->c('<div class="row-fluid"><div class="span12"><h1>' . $page->name . '</h1></div></div>');
    
    $to = '';
    $to_ts = time();
    $from_ts = 0;
    $fto = '';
    if (array_key_exists('to', $_POST)) {
        $fto = $_POST['to'];
        $to_ts = strtotime($_POST['to']);
        $to = ' AND posted < ' . strtotime($_POST['to']);
    }
    $from = '';
    $ffrom = '';
    if (array_key_exists('from', $_POST)) {
        $ffrom = $_POST['from'];
        $from_ts = strtotime($_POST['from']);
        $from = ' AND posted > ' . strtotime($_POST['from']);
    }

    $latest = db()->query('SELECT posted FROM post WHERE page = ' . $id . ' ORDER BY posted DESC LIMIT 1;')->fetch_all();
    $latest = $latest[0]['posted'];
    $latest = strtotime('yesterday', $latest);

    $oldest = db()->query('SELECT posted FROM post WHERE page = ' . $id . ' ORDER BY posted ASC LIMIT 1;')->fetch_all();
    $oldest = $oldest[0]['posted'];
    $oldest = strtotime('tomorrow', $oldest);

    $bytype = db()->query('SELECT type, count(*) as count, avg(engagement) as engagement FROM post WHERE page = ' . $id . $to . $from . ' GROUP BY type ORDER BY count desc')->fetch_all();
    $all = db()->query('SELECT type, count(*) AS count, avg(engagement) as engagement FROM post WHERE page = ' . $id . $to . $from . ' ORDER BY count desc')->fetch_all();

    $template->add_js('script.js', 'engagement');
    $template->c('<div class="row-fluid"><div class="span12"><div class="well">
    <strong>Ealiest: ' . date('d-m-y', $oldest) . ' - Latest: ' . date('d-m-y', $latest) . '</strong><br/><br/>
    <form method="post">
    <label>From:</label>
    <input type="text" name="from" id="from" value="' . $ffrom . '"/>
    <label>To:</label>
    <input type="text" name="to" id="to" value="' . $fto . '"/>
    <input type="submit"/>
    </form></div></div></div>');


    $html = '<div class="row-fluid"><div class="span12"><table class="table">';
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
    $html .= '</table></div></div>';
    $template->c($html);
    
    
    $html = '<div class="row-fluid"><div class="span12"><table class="table"><tbody>';
    $html .= '<tr>';
    $html .= '<th>ID</th>';
    $html .= '<th>When</th>';
    $html .= '<th>message</th>';
    $html .= '<th>type</th>';
    $html .= '<th>likes</th>';
    $html .= '<th>shares</th>';
    $html .= '<th>cmnts</th>';
    $html .= '<th>total</th>';
    $html .= '<th>%</th>';
    $html .= '</tr>';
    foreach ($page->posts['posts'] as $p) {
        if ($p->posted > $to_ts || $p->posted < $from_ts){
           continue; 
        }
        $html .= '<tr>';
        $html .= '<td>' . $p->id . '</td>';
        $html .= "<td>".template_time_ago($p->posted)." ago</td>";
        $html .= '<td style="word-break: break-all;">' . $p->message . '</td>';
        $html .= '<td>' . $p->type . '</td>';
        $html .= '<td>' . $p->likes . '</td>';
        $html .= '<td>' . $p->shares . '</td>';
        $html .= '<td>' . $p->comments . '</td>';
        $html .= '<td>' . $p->total_interactions . '</td>';
        $html .= '<td>' . $p->engagement . '</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody</table></div></div>';
    $template->c($html);
    return $template->render();
}

function engagement_add_page($id_arg = 0) {
    if ($id_arg > 0) {
        $page = new Page($id_arg);
    } elseif (isset($_POST['url']) && !empty($_POST['url'])) {
        $page = new Page($_POST['url']);
    }
    redirect(get_url('/'), 301, true);
}

function engagement_get_posts($id){
    if(!isset($id)){return false;}
    $template = new Template();
    $template->add_js('js/get_posts.engagement.js', current_module());
    $page = new Page($id);
    $template->c('<h1>Getting posts for ' . $page->name . '</h1>');
    $template->c('<data id="page-data" data-page-id="'.$page->id.'" data-recent-post="'.$page->most_recent_post_time().'"/>');
    $template->c('<div class="well"><pre id="log"</pre></div>');
    return $template->render();
}

function engagement_get_posts_json($id, $since = 0, $until = 0){
    
    $page = new Page($id);
    $params = array();
    
    $url = '/' . $id . '/posts?';
    $params = array();
    if($since != '0' && $since != 0){
        $params[] = 'since=' . $since;
    } 
    if($until != '0' && $until != 0){
        $params[] = 'until=' . $until;
    } 
    $url .= implode('&', $params);
    
    $res = $page->get_posts_from_fb($url);
    
    return json_encode($res);
    
}

function engagement_page_table() {
    $table = '<table class="table">';
    $table .= '<tr>';
    $table .= "<th>ID</th>";
    $table .= "<th>Name</th>";
    $table .= "<th>Posts</th>";
    $table .= "<th>Likes</th>";
    $table .= "<th>Talking</th>";
    $table .= "<th>Engagement</th>";
    $table .= "<th>Updated</th>";
    $table .= "<th></th>";
    $table .= '</tr>';

    $pages = engagement_get_pages();
    foreach ($pages as $page) {
        $actions = array();
        $actions[] = l('Get New Posts', get_url('/page/get_posts/~/'.$page->id .'/'));
        $actions = implode(' - ', $actions);
        
        $table .= '<tr>';
        $table .= "<td>$page->id</td>";
        $table .= "<td>".l($page->name, get_url('/page/~/' . $page->id))."</td>";
        $table .= "<td>".$page->posts['count']."</td>";
        $table .= "<td>$page->likes</td>";
        $table .= "<td>$page->talking</td>";
        $table .= "<td>$page->engagement</td>";
        $table .= "<td>".template_time_ago($page->updated)." ago</td>";
        $table .= "<td>$actions</td>";
        $table .= '</tr>';
    }
    $table .= '</table>';
    return $table;
}

function engagement_get_pages($limit = 50) {
    $sql = 'SELECT id FROM page ORDER BY updated DESC LIMIT :limit';
    $pages = db()->dquery($sql)->arg(':limit', $limit)->execute()->fetch_col('id');
    foreach ($pages as &$id) {
        $id = new Page($id, false); //load page but without connected posts
    }
    return $pages;

}


function engagement_process_users(){
    $template = new Template();
    $template->add_js('js/process_users.engagement.js', current_module());
    $template->c('<h1>Processing Users</h1>');
    $template->c('<div class="well"><pre id="log"</pre></div>');
    //$page->get_posts(false);
    //redirect(get_url('/'), 301, true);
    return $template->render();
}

function engagement_process_users_json(){
    $limit = 50;
    $users = db()->query('SELECT user FROM join_post_user LEFT JOIN user ON user.id = user WHERE user.id IS NULL GROUP BY user LIMIT ' . $limit)->fetch_col('user');
    $cnt = count($users);
    foreach($users as $user){
        $user = new User($user);
        sleep(0.5);
    }
    return json_encode(array('count' => $cnt));
}
