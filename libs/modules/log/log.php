<?php

function log_init(){
    //log_clean_log();
} 

function log_routes(){
    $paths = array();
    $paths['log/view'] = array('callback' => 'log_view');
    $paths['log/view/json'] = array('callback' => 'log_view_json');
    return $paths;
}

function log_view(){
    $entries = db()->query('SELECT * FROM log ORDER BY created desc, id desc LIMIT 20')->fetch_all();
    $headers = array('id','source','level', 'text', 'created');
    $rows = array();
    if(!empty($entries)){
        $last_id = $entries[0]['id'];
    }else{
        $last_id = 0;
    }
    
    foreach($entries as $entry){
        $rows[$entry['id']] = array(
            $entry['id'],
            $entry['source'],
            $entry['level'],
            '<pre>' . $entry['text'] . '</pre>',
            template_date($entry['created'])
        );
        
    }
    $page = new Template();
    $page->add_js('js/log.js', 'log');
    $page->c('<script type="text/javascript" charset="utf-8">' . 'var log_last_id = ' . $last_id . '</script>');
    $page->c(template_table($headers, $rows));
    return $page->render();
}

function log_view_json($last_id = 0){
   $entries = db()->dquery('SELECT id, source, level, text, created FROM log WHERE id > :id ORDER BY created asc, id asc LIMIT 20')
                  ->arg(':id', $last_id)
                  ->execute()
                  ->fetch_all();
   foreach($entries as &$entry){
       $entry['created'] = template_date($entry['created']);
   }
   
   return json_encode($entries);
                 
}

function log_clean_log(){
    $exclude = array();
    $ids = db()->query('SELECT id FROM log ORDER BY id desc LIMIT 50')->fetch_all();
    foreach($ids as $id){
       $exclude[] = $id['id']; 
    }
    $exclude = implode(',', $exclude);
    db()->dquery('DELETE FROM log WHERE id NOT IN (:exclude)')->arg(':exclude', $exclude)->execute();
}
