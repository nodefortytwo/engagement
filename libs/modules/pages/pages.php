<?php

function pages_routes(){
    $paths = array();
    
    $paths['home'] = array(
        'callback' => 'engagement_home'
    );
    $paths['404'] = array(
        'callback' => 'pages_404'
    );
    $paths['403'] = array(
        'callback' => 'pages_403'
    );
    $paths['500'] = array(
        'callback' => 'pages_500'
    );
    return $paths;
}

function pages_homepage(){
    $page = new Template();
    $page->c('<h1 class="ten columns">' . 'Welcome to FreshNetworks Reporting' . '</h1>');
    $page->c('<p>' . 'Click on the client name below to run queries on the correct database.');
    $sites = exec_hook('register_sites');
    foreach($sites as &$site){
        $site = l($site['name'], '/'.$site['path']);
    }
    $page->c(template_list($sites));
    return $page->render();   
}

function pages_404(){
    header("HTTP/1.0 404 Not Found");
    $page = new Template();
    $img = '/' . SITE_ROOT . '/' . PATH_TO_MODULES . '/pages/img/404.jpg';
    $page->c('<div class="span12">' . '<h1>404 - Page Not Found</h1>');
    $page->c('<h2>Sorry Dude</h2>');
    $page->c('</div>');
    return $page->render();  
}

function pages_403(){
    header("HTTP/1.0 403 Access Denied");
    $page = new Template();
    $img = '/' . SITE_ROOT . '/' . PATH_TO_MODULES . '/pages/img/404.jpg';
    $page->c('<div class="span12">' . '<h1>403 - Access Denied</h1>');
    $page->c('<h2>Move along, Nothing to see here</h2>');
    $page->c('</div>');
    return $page->render();  
}

function pages_500(){
    header("HTTP/1.0 500 Server Error");
    $page = new Template();
    $page->c('<div class="span12">' . '<h1>500 - I appear to have broken the interwebs</h1>');
    $page->c('</div>');
    return $page->render();  
}