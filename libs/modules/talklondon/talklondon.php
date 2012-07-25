<?php

function talklondon_register_sites() {
    return array('name' => 'Talk London', 'path' => 'talklondon', 'database' => 'talklondon_london_gov_uk_live');
}

function talklondon_routes() {
    $paths = array();

    $paths['talklondon'] = array('callback' => 'talklondon_landing');
    $paths['talklondon/runquery'] = array('callback' => 'talklondon_run_query');
    return $paths;
}

function talklondon_run_query() {
    $sample = json_decode('{"from":"09-07-2012","to":"16-07-2012","query":"user_by_borough"}');

    if ($_POST) {
        $obj = new stdClass;
        $obj->query = $_POST['query'];
        $obj->to = strtotime($_POST['to']);
        $obj->from = strtotime($_POST['from']);
    } else {
        $obj = $sample;
        $obj->to = strtotime($obj->to);
        $obj->from = strtotime($obj->from);
    }
    
    $obj->to = $obj->to + 86400;
    $queries = talklondon_queries();
    if (array_key_exists($obj->query, $queries)) {
        $results = talklondon_exec_query($obj, $queries[$obj->query]['query']);
        $desc = '';
        if (isset($queries[$obj->query]['description'])) {
            $desc = $queries[$obj->query]['description'];
        }
        if (count($results) == 0) {
            $response = array('status' => '200', 'desc' => $desc, 'fields' => array_keys(array()), 'results' => array());
        } else {
            $response = array('status' => '200', 'desc' => $desc, 'fields' => array_keys($results[0]), 'results' => $results);
        }
        return json_encode($response);
    } else {
        $response = array('status' => '404', 'msg' => 'query not found');
        return json_encode($response);
    }
    $response = array('status' => '500', 'msg' => 'fail');
    return json_encode($response);
}

function talklondon_exec_query($obj, $query) {
    $site = talklondon_register_sites();
    $args = array(':to' => $obj->to, ':from' => $obj->from);

    db()->select_db($site['database']);
    return db()->dquery($query)->arg($args)->execute()->fetch_all();
}

function talklondon_landing() {

    $page = new Template();
    $page->add_js('script.js', 'talklondon');
    $page->c('<h1 class="span11">' . 'Queries' . '</h1>');
    $page->c(talklondon_query_form());
    $page->c('<div class="span11" class="margin-left:0;" id="query_results">' . '' . '</div>');
    return $page->render();
}

function talklondon_queries() {

    $queries = array();

    $queries['total_users'] = array('name' => 'Total Users', 
                    'description' => 'this query always shows total users, it is not affected by from and to dates',    
                    'query' => '
                    SELECT count(users.uid) as "Total Users" FROM users 
                    LEFT JOIN users_roles ON users.uid = users_roles.uid 
                    WHERE status = 1 AND (users_roles.rid IS NULL OR users_roles.rid = 2);
                    ');

    $queries['user_by_borough'] = array('name' => 'User by Borough', 'query' => '
                    SELECT field_borough_value as "Borough", count(users.uid) AS count FROM users 
                    LEFT JOIN users_roles on users.uid = users_roles.uid 
                    LEFT JOIN field_data_field_borough ON field_data_field_borough.entity_id = users.uid 
                    WHERE status = 1
                    AND (users_roles.rid IS NULL OR users_roles.rid = 2)
                    AND (users.created > :from and users.created < :to) 
                    GROUP BY `field_borough_value`
                    ORDER BY field_borough_value desc;
                    ');

    $queries['new_users'] = array('name' => 'New Users', 'query' => '
                    SELECT count(users.uid) as "New Users" FROM users
                    LEFT JOIN users_roles ON users.uid = users_roles.uid  
                    WHERE status = 1 
                    AND (users_roles.rid IS NULL OR users_roles.rid = 2)
                    AND (created > :from AND created < :to);
                    ');
    
    $queries['active_users'] = array('name' => 'Active Users', 'description' => 'An active users is one who has logged in within the date range specified', 'query' => '
                    SELECT count(*) as Users
                    FROM `users`
                    LEFT JOIN users_roles ON users.uid = users_roles.uid
                    WHERE (users_roles.rid IS NULL OR users_roles.rid = 2)
                    AND ((login > :from and login < :to) OR (created > :from and created < :to) OR (((SELECT timestamp FROM fn_log WHERE uid = users.uid ORDER BY timestamp desc LIMIT 1) > :from AND (SELECT timestamp FROM fn_log WHERE uid = users.uid ORDER BY timestamp desc LIMIT 1) < :to)))
                    ');
    
    $queries['engaged_userss'] = array('name' => 'Engaged Users', 'description' => 'users who have created a discussion or posted a comment', 'query' => '
                    SELECT count(users.name) as Users
                    FROM users
                    LEFT JOIN users_roles ON users.uid = users_roles.uid
                    WHERE (users_roles.rid IS NULL OR users_roles.rid = 2)
                    AND (((SELECT created FROM node WHERE node.uid = users.uid ORDER BY created desc LIMIT 1) > :from AND (SELECT created FROM node WHERE node.uid = users.uid ORDER BY created desc LIMIT 1) < :to)
                    OR ((SELECT created FROM comment WHERE comment.uid = users.uid ORDER BY created desc LIMIT 1) > :from AND (SELECT created FROM comment WHERE comment.uid = users.uid ORDER BY created desc LIMIT 1) < :to)
                    OR ((SELECT timestamp FROM poll_vote WHERE poll_vote.uid = users.uid ORDER BY timestamp desc LIMIT 1) > :from AND (SELECT timestamp FROM poll_vote WHERE poll_vote.uid = users.uid ORDER BY timestamp desc LIMIT 1) < :to))
                    ');
                    
    $queries['new_discussions_CM'] = array('name' => 'New Discussions Community Managers', 'description' => 'Discussions created by Community managers', 'query' => '
                    SELECT count(nid) as "New Discussions" FROM node 
                    LEFT JOIN users ON node.uid = users.uid 
                    LEFT JOIN users_roles ON users.uid = users_roles.uid 
                    WHERE type = "discussion" 
                    AND (users_roles.rid = 3 OR users_roles.rid = 4)
                    AND ( node.created > :from AND node.created < :to);
                    ');

    $queries['new_discussions'] = array('name' => 'New Discussions', 'query' => '
                    SELECT count(nid) as Discussions FROM node 
                    LEFT JOIN users ON node.uid = users.uid 
                    LEFT JOIN users_roles ON users.uid = users_roles.uid 
                    WHERE type = "discussion" 
                    AND (users_roles.rid IS NULL OR users_roles.rid = 2)
                    AND (node.created > :from AND node.created < :to);
                    ');

    $queries['comments_on_discussions'] = array('name' => 'Comments on Discussions', 'query' => '
                    SELECT count(cid) as Comments FROM comment
                    LEFT JOIN node on comment.nid = node.nid
                    LEFT JOIN users ON users.uid = comment.uid
                    LEFT JOIN users_roles ON users_roles.uid = users.uid
                    WHERE node.type = "discussion" 
                    AND (users_roles.rid IS NULL OR users_roles.rid = 2)
                    AND (comment.created > :from AND comment.created < :to);
                    ');
    
    $queries['comments_on_discussions_by_user'] = array('name' => 'Comments on Discussions by User', 'query' => '
                    SELECT users.name, count(cid) as Comments FROM comment
                    LEFT JOIN node ON comment.nid = node.nid
                    LEFT JOIN users ON users.uid = comment.uid
                    LEFT JOIN users_roles ON users_roles.uid = users.uid
                    WHERE node.type = "discussion" 
                    AND (users_roles.rid IS NULL OR users_roles.rid = 2)
                    AND (comment.created > :from AND comment.created < :to)
                    GROUP BY comment.uid
                    ORDER BY Comments desc;
                    ');
    
    $queries['new_articles'] = array('name' => 'New Articles', 'query' => '
                    SELECT count(nid) as Articles FROM node WHERE type = "article" 
                    AND (created > :from AND created < :to);
                    ');

    $queries['comments_on_articles'] = array('name' => 'Comments on Articles', 'query' => '
                    SELECT count(cid) as Comments FROM comment
                    LEFT JOIN node on comment.nid = node.nid
                    LEFT JOIN users ON users.uid = comment.uid
                    LEFT JOIN users_roles ON users_roles.uid = users.uid
                    WHERE node.type = "article" 
                    AND (users_roles.rid IS NULL OR users_roles.rid = 2)
                    AND (comment.created > :from AND comment.created < :to);
                    ');
                    
    $queries['comments_on_articles_by_user'] = array('name' => 'Comments on Articles by User', 'query' => '
                    SELECT users.name, count(cid) as Comments FROM comment
                    LEFT JOIN node ON comment.nid = node.nid
                    LEFT JOIN users ON users.uid = comment.uid
                    LEFT JOIN users_roles ON users_roles.uid = users.uid
                    WHERE node.type = "article" 
                    AND (users_roles.rid IS NULL OR users_roles.rid = 2)
                    AND (comment.created > :from AND comment.created < :to)
                    GROUP BY comment.uid
                    ORDER BY Comments desc;
                    ');
                    
    $queries['poll_votes'] = array('name' => 'Poll Votes', 'query' => '   
                    SELECT count(*) as Votes FROM poll_vote WHERE timestamp > :from AND timestamp < :to;
                    ');

    return $queries;
}

function talklondon_query_form() {
    $html = '<form class="form-search span 11" id="query_form">';
    
    if (date('w') != 1){//not monday
        $to_ts = strtotime('last monday');
    }else{
        $to_ts = mktime(0,0,0);
    }
    $to = date('d-m-Y', $to_ts);
    
    $from = date('d-m-Y', strtotime('last monday', $to_ts));

    $html .= '<label for="from_date">From:</label>';
    $html .= '<input type="text" id="from_date" value="' . $from . '">';
    $html .= '<label for="to_date" style="margin-left:5px;">To:</label>';
    $html .= '<input type="text" id="to_date" style="margin-right:5px;" value="' . $to . '">';

    $queries = talklondon_queries();
    $html .= '<label for="query">Query:</label>';
    $html .= '<select id="query">';
    foreach ($queries as $key => $query) {
        $html .= '<option value="' . $key . '">' . $query['name'] . '</option>';
    }
    $html .= '</select>';
    $html .= '<input type="submit" style="margin-left:5px;" id="submit" class="btn btn-primary"/>';
    $html .= '</form>';
    return $html;
}
