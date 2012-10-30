<?php
class Post
{

    public $id, $likes = 0, $comments = 0, $shares = 0, $data, $created = 0, $updated = 0, $engagement = 0, $connected_users = array(), $page_likes = 0;

    public function __construct($id = null) {
        if (isset($id)) {
            $this->id = $id;
            $this->load();
        }
    }

    public function load($db_row = array()) {
        if (empty($db_row)) {
            $sql = 'SELECT post.*, page.likes as page_likes FROM post LEFT JOIN page ON page.id = post.page WHERE post.id = ":id"';
            $post = db()->dquery($sql)->arg(':id', $this->id)->execute()->fetch_single();
        } else {
            $post = $db_row;
        }
        if (!empty($post)) {
            $this->data = $post['data'];
            $this->created = $post['created'];
            $this->engagement = $post['engagement'];
            $this->page = $post['page'];
            if (isset($post['page_likes'])) {
                $this->page_likes = $post['page_likes'];
            }
            $this->parse_fb_object();

        }

        if ($this->page_likes == 0) {
            $this->get_page_likes();
        }

        $this->get_connected_users();
    }

    public function parse_fb_object() {
        if (empty($this->data)) {
            return false;
        }

        if ($data = json_decode($this->data)) {

            $this->id = $data->id;
            $this->page = $data->from->id;
            if (isset($data->likes)) {
                $this->likes = $data->likes->count;
            }
            if (isset($data->message)) {
                $this->message = $data->message;
            } elseif (isset($d->story)) {
                $this->message = $data->story;
            } else {
                $this->message = '';
            }
            $this->type = $data->type;
            if (isset($data->likes)) {
                $this->status_type = $data->status_type;
            } else {
                $this->status_type = '';
            }
            $this->posted = strtotime($data->created_time);
            if (isset($data->comments)) {
                $this->comments = $data->comments->count;
            }
            if (isset($data->shares)) {
                $this->shares = $data->shares->count;
            }
            $this->total_interactions = $this->comments + $this->likes + $this->shares;

            //this rules out all non-shareable content
            if (isset($data->actions) && !empty($data->actions)) {
                $this->save();
            }
        }

    }

    public function calculate_engagement($page_likes = 0) {
        if ($page_likes == 0) {
            $this->get_page_likes();
        } else {
            $this->page_likes = $page_likes;
        }
        $this->engagement = ($this->total_interactions / $this->page_likes) * 100;
        $this->engagement = round($this->engagement, 4);
        $this->save();
        return $this->engagement;
    }

    public function get_connected_users($from_facebook = false) {
        $this->connected_users = db()->dquery('SELECT user as id FROM join_post_user WHERE post = ":post"')->arg(':post', $this->id)->execute()->fetch_all();
        if ($from_facebook) {

            global $facebook;
            $res = $facebook->api('/' . $this->id . '/likes?limit=500');

            foreach ($res['data'] as $user) {
                $this->connected_users[] = array('id' => $user);
                db()->dquery('REPLACE INTO join_post_user (post, user) VALUES (":id", ":user")')->arg(':id', $this->id)->arg(':user', $user['id'])->execute();
            }

        }

    }

    public function get_page_likes() {
        $likes = db()->dquery('SELECT likes FROM page WHERE id = :id')->arg(':id', $this->page)->execute()->fetch_single();
        if (!empty($likes)) {
            $this->page_likes = $likes['likes'];
        } else {
            $this->page_likes = 0;
        }
    }

    public function indb() {
        $sql = 'SELECT id FROM post WHERE id = ":id"';
        $res = db()->dquery($sql)->arg(':id', $this->id)->execute()->fetch_single();
        if (!empty($res)) {
            return true;
        } else {
            return false;
        }
    }

    public function save() {
        $args = array(':id' => $this->id, ':likes' => $this->likes, ':type' => $this->type, ':status_type' => $this->status_type, ':posted' => $this->posted, ':comments' => $this->comments, ':data' => $this->data, ':page' => $this->page, ':created' => $this->created, ':shares' => $this->shares, ':total_interactions' => $this->total_interactions, ':updated' => $this->updated, ':engagement' => $this->engagement);
        if (!$this->indb()) {
            $sql = 'INSERT INTO post (id, data, likes, posted, type, status_type, page, comments, created, shares, total_interactions, updated) VALUES (":id", ":data", "likes", :posted, ":type", ":status_type", ":page", ":comments", ":created", :shares, :total_interactions, :updated);';
        } else {
            $args[':created'] = time();
            $sql = 'UPDATE post SET data = ":data", likes = ":likes", type=":type", status_type = ":status_type", page = ":page", posted = ":posted", created = :created, comments = :comments, shares = :shares, total_interactions = :total_interactions, updated=:updated, engagement = :engagement  WHERE id = ":id"';
        }

        db()->dquery($sql)->arg($args)->execute();

    }

}
