<?php

class ListbuilderList
{
    public $name;
    
    public function __construct($id = null) {
        
    }
    
    public function create(){
        
        $args = array(
            ':name' => $this->name
        );
        
        $sql = 'INSERT INTO lb_list (name) VALUES (":name")';
        
        db()->dquery($sql)->arg($args)->execute();
        return db()->last_id();
    }
}

    