<?php

function facebook_init() {
    require ('src/facebook.php');
    global $facebook;
    $facebook = new Facebook( array('appId' => FB_KEY, 'secret' => FB_SECRET, ));
    if (!empty($_SESSION) && !empty($_SESSION['fb_code'])){
        $url = "https://graph.facebook.com/oauth/access_token?";
        $params = array();
        $params[] = 'client_id=' . $facebook->getAppId();
        $params[] = 'redirect_uri=' . 'http://' . HOST . get_url('/facebook/auth/');
        $params[] = 'client_secret=' . $facebook->getApiSecret();
        $params[] = 'code=' . $_SESSION['fb_code'];
        $url .= implode('&', $params);
        $data = explode('&', get_data($url));
        foreach($data as &$d){
            $d = explode('=', $d);
            if ($d[0] == 'access_token'){
                $_SESSION['fb_access_token'] = $d[1];
            }elseif ($d[0] == 'expires'){
                $_SESSION['fb_at_expires'] = time() + $d[1];
            }
        }
    }
    
    if (array_key_exists('fb_access_token', $_SESSION)){
        if ($_SESSION['fb_at_expires'] > time()){
            $facebook->setAccessToken($_SESSION['fb_access_token']);
            unset($_SESSION['fb_code']);
        }
    }
    
}

function facebook_routes() {
    $routes = array();
    $routes['facebook/auth'] = array('callback' => 'facebook_auth');
    return $routes;
}

function facebook_auth() {
    global $facebook;

    if(!empty($_GET) && !empty($_GET['state'])){
        $_SESSION['fb_state'] = $_GET['state'];
        $_SESSION['fb_code'] = $_GET['code'];
        redirect(get_url('/'), 301, true);
    }

    $params = array('scope' => '', 'redirect_uri' => 'http://' . HOST . get_url('/facebook/auth/'));
    
    $loginUrl = $facebook->getLoginUrl($params);
    redirect($loginUrl, 301, false);
}
