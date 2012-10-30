<?php
function debug($var) {
    print '<pre>' . print_r($var, true) . '</pre>';

}

function beginsWith($str, $sub) {
    return (strncmp($str, $sub, strlen($sub)) == 0);
}

function redirect($url, $code = '301', $root = true) {

    if ($root) {
        $url = '/' . SITE_ROOT . $url;
        $url = str_replace('//', '/', $url);
    }
    switch ($code) {
        default :
            header("HTTP/1.1 301 Moved Permanently");
            break;
    }

    $header = 'Location: ' . $url;
    header($header);
    die();
}

function module_get_path($module_name) {
    $basepath = dirname($_SERVER['PHP_SELF']);
    $path = $basepath . '/libs/modules/' . $module_name;
    return $path;
}

function get_data($url) {
    global $last_request;
    //horrible rate limit hack
    if (time() - $last_request < 5) {
        sleep(5);
    }
    $last_request = time();
    $ch = curl_init();
    $timeout = 5;
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1");
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_ENCODING, "");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_AUTOREFERER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    # required for https urls
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
    $data = curl_exec($ch);
    curl_close($ch);
    if (strpos($data, 'Generic System Error') === false) {
        return $data;
    } else {
        return '';
    }
}

function var_get($name, $default = null) {
    $db = db();
    $db->query('DELETE FROM variable WHERE expires < ' . time() . ' AND expires <> 0');
    $db->dquery('SELECT value FROM variable WHERE name = ":name"');
    $db->arg(':name', $name);
    $db->execute();
    $res = $db->fetch_single();
    if (!empty($res)) {
        $value = json_decode($res['value']);
    } else {
        $value = $default;
    }

    return $value;
}

function var_set($name, $value, $exp = 0) {
    $db = db();
    $value = json_encode($value);

    $db->dquery('SELECT id FROM variable WHERE name = ":name"');
    $db->arg(':name', $name);
    $db->execute();
    $res = $db->fetch_single();
    if ($exp) {
        $exp = time() + ($exp * 3600);
    }
    if (empty($res)) {
        $db->dquery('INSERT INTO variable (name, value, expires) VALUES (":name", ":value", :exp)');
        $db->arg(':name', $name);
        $db->arg(':value', $value);
        $db->arg(':exp', $exp);
        $db->execute();
    } else {
        $db->dquery('UPDATE variable SET name = ":name", value = ":value", expires = :exp WHERE id = :id;');
        $db->arg(':name', $name);
        $db->arg(':value', $value);
        $db->arg(':exp', $exp);
        $db->arg(':id', $res['id']);
        $db->execute();
    }

}

function between($haystack, $string1, $string2) {
    //echo ($haystack . "\n");
    $pos1 = strpos($haystack, $string1);
    if ($pos1 === false) {
        return '';
    }
    $pos1 = $pos1 + strlen($string1);
    $pos2 = strpos($haystack, $string2, $pos1);
    $val = substr($haystack, $pos1, $pos2 - $pos1);
    return $val;
}

function get_url($path) { 
    if (SITE_ROOT != '') {
        $path = '/' . SITE_ROOT . '/' . $path;
    } else {
        $path = '/' . $path;
    }

    return '' . str_replace('//', '/', $path);
}

function current_module($set = null) {
    global $current_module;
    if (!is_null($set)) {
        $current_module = $set;
        return $current_module;
    } else {
        return $current_module;
    }
}

function get_url_args($url = '') {
    $url = parse_url($url);
    $url = explode('&', $url['query']);
    $n = array();
    foreach ($url as &$arg) {
        $t = explode('=', $arg);
        $n[$t[0]] = $t[1];
    }
    return $n;
}
?>