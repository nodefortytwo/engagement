<?php
class Page
{
    public $id, $name = 0, $likes = 0, $talking = 0, $engagement = 0, $url = '',  $posts = array('count'=>0, 'posts' => array()), $updated = 0, $created = 0, $data = '';

    public function __construct($id, $entities = true) {
        if (isset($id)) {//if id isn't set do nothing, chances are we are going to pass in an object later instead.
            $this->id = $id;
            $loaded = $this->load();

            //if we haven't loaded from db, lets grab it from fb;
            if (!$loaded) {
                $this->load_from_fb();
            }
            
            if($entities){
                $this->get_posts(true);
            }
        }
    }

    //we except db row here so we can load multiple objects in one query (sexy eh?)
    public function load($db_row = array()){
        if (!empty($db_row)){
            $this->id = $db_row['id'];
            return $this->load_from_id($db_row);
            
        }
        if (is_int((int)$this->id) && (int)$this->id > 0) {
            $this->id = (int)$this->id;
            return $this->load_from_id();
        } else {
            if (beginsWith($this->id, 'http')) {
                return $this->load_from_url();
            } else {
                return $this->load_from_name();
            }
        }
    }

    private function load_from_id($db_row = array()) {
        if (!is_int($this->id)) {
            return false;
        }
        $return = false;
        if (empty($db_row)){
            $sql = 'SELECT data, updated, created, engagement, post_count FROM page WHERE id = :id';
            $page = db()->dquery($sql)->arg(":id", $this->id)->execute()->fetch_single();
        }else{
            $page = $db_row;
        }
        if (!empty($page)) {
            $this->created = $page['created'];
            $this->updated = $page['updated'];
            $this->engagement = $page['engagement'];
            $this->data = $page['data'];
            $this->posts['count'] = $page['post_count'];
            if (empty($this->data)) {
                $this->load_from_fb();
            }
            $return = $this->parse_fb_object();
        }
        return $return;
    }

    private function load_from_url() {
        if (empty($this->id)) {
            return false;
        }
        $this->id = str_replace('http://www.facebook.com/', '', $this->id);
        $this->id = str_replace('https://www.facebook.com/', '', $this->id);
        return $this->load_from_name();
    }

    private function load_from_name() {
        if (empty($this->id)) {
            return false;
        }
        $return = false;
        $sql = 'SELECT id, data, updated, created, post_count FROM page WHERE lower(url) LIKE "%:name";';
        $page = db()->dquery($sql)->arg(":name", strtolower($this->id))->execute()->fetch_single();
        if (!empty($page)) {
            $this->id = (int)$page['id'];
            $this->data = $page['data'];
            $this->created = $page['created'];
            $this->updated = $page['updated'];
            $this->engagement = $page['engagement'];
            $this->posts['count'] = $page['post_count'];
            $return = $this->parse_fb_object();
        }
        return $return;
    }

    public function parse_fb_object() {
        if (!isset($this->data)) {
            return false;
        }

        if ($data = json_decode($this->data)) {
            $this->id = (int)$data->id;
            $this->name = $data->name;
            $this->likes = $data->likes;
            $this->talking = $data->talking_about_count;
            $this->url = $data->link;
            return true;
        }
        return false;

    }

    private function load_from_fb($object) {
        if (!isset($object)) {
            global $facebook;
            $res = $facebook->api('/' . $this->id);
            $this->data = json_encode($res);
        } else {
            if (is_object($object)) {
                $object = json_encode($object);
            }
            $this->data = $object;
        }
        $this->parse_fb_object();
        $this->updated = time();
        $this->save();
    }

    public function get_posts($db_only = true) {
        $sql = 'SELECT id FROM post WHERE page = :id ORDER BY posted DESC';//order by date posted
        $posts = db()->dquery($sql)->arg(':id', $this->id)->execute()->fetch_col('id');
        $newest = 0;
        foreach ($posts as $p) {
            $post = new Post($p);
            if ($post->created > $newest) {
                $newest = $post->created;
            }
            $this->posts['posts'][] = $post;
        }
        $this->posts['count'] = count($this->posts['posts']);
        $this->calculate_engagement();
    }
    
    private function calculate_engagement(){
        foreach($this->posts['posts'] as $p){
            $en[] = $p->calculate_engagement($this->likes);
        }
        if (!empty($en)){    
            $this->engagement = array_sum($en) / count($en);
        }else{
            $this->engagement = 0;
        }
        
        $this->engagement = round($this->engagement, 4);
        $this->save();
    }
    
    public function get_posts_from_fb($url, $posts = array()) {
        $this->updated = time();
        $url = str_replace('https://graph.facebook.com', '', $url);
        $url = str_replace('http://graph.facebook.com', '', $url);
        global $facebook;
        $result = $facebook->api($url);
        $posts = array_merge($posts, $result['data']);
        foreach ($posts as $p) {
            $post = new Post();
            $post->data = json_encode($p);
            $post->updated = time();
            $post->parse_fb_object();
            
            //this rules out all non-shareable content
            if(isset($post->data->actions) && !empty($post->data->actions)){
                $post->save();
                $this->posts['posts'][] = $post;
            }
        }
        
        //update engagement to include new posts
        $this->calculate_engagement();
        if (isset($result['paging'])){
            $next = get_url_args($result['paging']['next']);
        }else{
            $next = array();
        }
        return array(
            'post_count' => count($result['data']),
            'next' => $next
        );
    }

    public function save() {

        $args = array(':id' => $this->id, ':name' => $this->name, ':likes' => $this->likes, ':engagement' => $this->engagement, ':url' => $this->url, ':updated' => $this->updated, ':data' => $this->data, ':post_count' => $this->posts['count']);

        if ($this->indb()) {
            $sql = 'UPDATE page SET name = ":name", likes = :likes, engagement = :engagement, url = ":url", updated = ":updated", data = ":data", post_count = :post_count WHERE id = :id';
        } else {
            $args[':created'] = time();
            $sql = 'INSERT INTO page (id, name, likes, engagement, url, updated, created, data, post_count) VALUES (:id, ":name", :likes, :engagement, ":url", :updated, :created, ":data", :post_count)';
        }

        $res = db()->dquery($sql)->arg($args)->execute();

    }

    public function indb() {
        if (!is_int($this->id)) {
            return false;
        }
        $sql = 'SELECT id FROM page WHERE id = :id';
        $res = db()->dquery($sql)->arg(':id', $this->id)->execute()->fetch_single();
        if (!empty($res)) {
            return true;
        } else {
            return false;
        }
    }
    
    public function most_recent_post_time(){
        if ($this->posts['count'] == 0){
            return 0;
        }else{
            if (empty($this->posts['posts'])){ //we know we have posts but they haven't been loaded
                $this->get_posts(true);
            }
            return $this->posts['posts'][0]->posted;
        }
    }

}
