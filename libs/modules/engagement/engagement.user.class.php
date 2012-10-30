<?php

class User{
    
    public $id, $firstname, $lastname, $gender, $locale, $added = 0, $updated = 0;
    
    public function __construct($id) {
        $this->id = (int) $id;
        if ($this->indb()){
            $this->load();
        }else{
            $this->load_from_fb();
        }
        $this->save();    
    }
    
    public function parse_fb_object(){
        if (is_object($this->data)){
            $data = $this->data;
        }else{
            $data = json_decode($this->data);
        }
        if(isset($data->first_name)){
            $this->firstname = $data->first_name;
        }
        if(isset($data->last_name)){
            $this->lastname = $data->last_name;
        }
        if(isset($data->locale)){
            $this->locale = $data->locale;
        }
        if(isset($data->gender)){
            $this->gender = $data->gender;
        }
    }
    //we except db row here so we can load multiple objects in one query (sexy eh?)
    public function load($db_row = array()){
        if (empty($db_row)){
            $sql = 'SELECT * FROM user WHERE id = :id';
            $user = db()->dquery($sql)->arg(':id', $this->id)->execute()->fetch_single();
        }else{
            $user = $db_row;
        }
        $this->data = $user['data'];
        $this->updated = $user['updated'];
        $this->added = $user['added'];
        $this->parse_fb_object();
    }
    
    public function load_from_fb(){
        global $facebook;
        try{
        $res = $facebook->api($this->id);
        $this->data = json_encode($res);  
        $this->updated = time();
        $this->parse_fb_object();
        $this->save();
        }catch(Exception $e){
            
        }
    }
    
    public function save(){
        $args = array(
            ':id' => $this->id,
            ':updated' => $this->updated,
            ':firstname' => $this->firstname,
            ':lastname' => $this->lastname,
            ':gender' => $this->gender,
            ':locale' => $this->locale,
            ':data' => $this->data
        );
        
        if ($this->indb()){
            $sql = 'UPDATE user SET firstname = ":firstname", lastname = ":lastname", gender = ":gender", locale = ":locale", data = ":data", updated = :updated WHERE id = :id';
        }else{
            $args[':added'] = time();
            $sql = 'INSERT INTO user (id, firstname, lastname, gender, locale, data, added, updated) VALUES (:id, ":firstname",":lastname",":gender",":locale",":data", :added, :updated)';
        }
        
        db()->dquery($sql)->arg($args)->execute();
        
    }
    
    public function indb() {
        if (!is_int($this->id)) {
            return false;
        }
        $sql = 'SELECT id FROM user WHERE id = :id';
        $res = db()->dquery($sql)->arg(':id', $this->id)->execute()->fetch_single();
        if (!empty($res)) {
            return true;
        } else {
            return false;
        }
    }
}