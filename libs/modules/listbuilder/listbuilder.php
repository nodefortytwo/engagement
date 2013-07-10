<?php

function listbuilder_init(){
    require 'listbuilder.list.class.php';
}

function listbuilder_routes(){
    $paths = array();
    $paths['listbuilder'] = array('callback'=>'listbuilder_home');
    $paths['listbuilder/list/create'] = array('callback'=>'listbuilder_list_create');
    return $paths;
}

function listbuilder_home(){
    $page = new Template();
    $page->c('<div class="row-fluid"><div class="span12"><div class="well"><form method="post" action="' . get_url('/listbuilder/list/create/') . '">
    <h2>Create List</h2>
    <input type="text" name="list" id="list">
    <input type="submit"/>
    </form></div></div></div>');
    
    return $page->render();
}


function listbuilder_list_create(){
    $list = new ListbuilderList();
    $list->name = $_POST['list'];
    return $list->create();
    
    die('test');
    
}
