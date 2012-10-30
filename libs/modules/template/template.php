<?php

function template_init(){
    require('template.class.php');
}

function template_routes(){
    return array(PATH_TO_MODULES.'/template/theme/js/dynamic' => array('callback'=>'template_dynamic_js'));
}

function template_global_css(){
    $css = array();
    $css[] = 'http://fonts.googleapis.com/css?family=Raleway:100';
    $css[] = 'theme/css/ui-darkness/jquery-ui-1.8.21.custom.css';
    return $css;
}

function template_global_js(){
    $js = array();
    $js[] = 'theme/js/jquery-1.7.1.min.js';
    $js[] = 'theme/js/jquery-ui-1.8.18.custom.min.js';
    $js[] = 'theme/js/modernizr-2.5.2.min.js';
    $js[] = 'theme/js/bootstrap.min.js';
    $js[] = 'theme/js/bootstrap-alert.js';
    $js[] = 'theme/js/bootstrap-button.js';
    $js[] = 'theme/js/bootstrap-carousel.js';
    $js[] = 'theme/js/bootstrap-collapse.js';
    $js[] = 'theme/js/bootstrap-dropdown.js';
    $js[] = 'theme/js/bootstrap-modal.js';
    $js[] = 'theme/js/bootstrap-popover.js';
    $js[] = 'theme/js/bootstrap-scrollspy.js';
    $js[] = 'theme/js/bootstrap-tab.js';
    $js[] = 'theme/js/bootstrap-tooltip.js';
    $js[] = 'theme/js/bootstrap-transition.js';
    $js[] = 'theme/js/bootstrap-typeahead.js';
    $js[] = 'theme/js/less-1.3.0.min.js';
    $js[] = 'theme/js/jquery.colorbox-min.js';
    $js[] = 'theme/js/extndr.js';
    $js[] = 'theme/js/dynamic';
    return $js;
}


function template_global_less(){
    $less = array();
    $less[] = 'theme/less/bootstrap.less'; 
    $less[] = 'theme/less/responsive.less';
    $less[] = 'theme/less/extndr.less';
    $less[] = 'theme/less/colorbox.less';  
    return $less;
}

//this function is used to pass system variables to JS, makes it easier to format ajax call urls and other stuff
function template_dynamic_js(){
    $vars = array();
    $vars['HOST'] = HOST;
    $vars['SITE_ROOT'] = SITE_ROOT;
    $vars['PATH_TO_MODULES'] = PATH_TO_MODULES;
    
    $js_vars = json_encode($vars);
    
    $return = 'var SYSTEM' . "\n";
    $return .= 'SYSTEM = eval('. $js_vars . ')';
    return $return;
}

//Theme functions (to be called by other modules)
function template_tabs($tabs = array(), $active = 0){
    $content = '';
    $top = '';
    $i =0;
    foreach($tabs as $id=>$tab){
        if ($i == $active){$class = 'active';}
        $top .= "\t" . '<li><a class="'.$class.'" href="#'.$id.'">' . $tab['title'] . '</a></li>' . "\n";
        $content .= "\t" . '<li id="'.$id.'" class="'.$class.'">' . $tab['content'] . '</li>' . "\n";    
        $i++;
        $class = '';
    }
    $return = '<ul class="tabs">' . "\n" . $top . '</ul>' . "\n";
    $return .= '<ul class="tabs-content">' . "\n" . $content . '</ul>';
    return $return;
    
}

function l($text, $url, $class='', $root = false){   
    $return = '<a href="' . $url . '" class="'.$class.'">' . $text . '</a>';
    return $return;
}

function template_list($array, $class = ''){
    $return = '<ul class="' . $class . '">';
    foreach($array as $key=>$item){
        $class = '';
        if(is_array($item)){
            if(array_key_exists('class', $item)){
                $class = $item['class'];
            }
            if(array_key_exists('text', $item)){
                $item = $item['text'];
            }
        }
        $return .= '<li id="' . $key . '" class="' . $class . '">';
        $return .= $item;
        $return .= '</li>';
    }
    return $return;
}

function template_table($headers, $rows, $class = ''){
    $return = '';
    $return .= '<table class="table table-striped table-bordered '.$class.'">';
    $return .= '<thead>';
    $return .= '<tr>';
    foreach($headers as $header){
        $return .= '<th>' . $header . '</th>';
    }
    $return .= '</tr>';
    $return .= '</thead>';
    $return .= '<tbody>';
    foreach($rows as $row){
        $return .= '<tr>';
        foreach($row as $col){
            $return .= '<td>' . $col . '</td>';
        }
        $return .= '</tr>';
    }
    $return .= '</tbody>';
    $return .= '</table>';
    return $return;
}

function template_form_item($id, $name, $type, $default = '', $width = 'ten', $options = array(), $description = '')
{
    $class = '';
    $return = '';
    $return .= '<div class="control-group '.$type.' '.$width.' columns">'. "\n";
    
    $return .= "\t" .'<label class="control-label" for="' . $id .'">' . $name . '</label>'. "\n";
    $return .= '<div class="controls">';
    switch($type){
        case 'select':
            $return .= "\t" .'<select id="' . $id . '" name="' . $id . '">'. "\n";
            array_unshift($options, '-- select -- ');
            foreach($options as $key=>$opt){
                $selected = '';
                if ($default == $key || $default == $opt){
                    $selected = 'selected="selected"';
                }
                if(!is_numeric($key)){
                    $return .= "\t\t" . '<option value="'.$key.'" '.$selected.'>';
                }else{
                    $return .= "\t\t" . '<option '.$selected.'>';    
                }
                $return .= $opt . '</option>' . "\n";
            }
            $return .= "\t" . '</select>'. "\n";
            break;
        case 'password':
        case 'text':
        default:
            if ($type == 'submit'){
                $class .= 'btn btn-primary';
            }
            $return .= "\t" . '<input type="' . $type . '" id="' . $id . '" name="' . $id . '" value="' . $default . '" class="'.$class.'"/>'. "\n"; 
    
    }
    $return .= '</div>';
    $return .= '<div class="help-text">' . $description . '</div>';
    $return .= '</div>'. "\n";
    
    return $return;
    
}

function template_date($date = null){
    if (is_null($date)){$date = time();}
    if(!is_numeric($date)){
        $date = strtotime($date);
    }
    $now = time();
    if (($now - $date) > 86400){
        return date('dS M @ ga', $date);   
    }else{
        return template_time_ago($date) . ' ago';
    }
}

function template_time_ago($tm,$rcs = 0){
   $cur_tm = time(); $dif = $cur_tm-$tm;
   $pds = array('second','minute','hour','day','week','month','year','decade');
   $lngh = array(1,60,3600,86400,604800,2630880,31570560,315705600);
   for($v = sizeof($lngh)-1; ($v >= 0)&&(($no = $dif/$lngh[$v])<=1); $v--); if($v < 0) $v = 0; $_tm = $cur_tm-($dif%$lngh[$v]);

   $no = floor($no); if($no <> 1) $pds[$v] .='s'; $x=sprintf("%d %s ",$no,$pds[$v]);
   if(($rcs == 1)&&($v >= 1)&&(($cur_tm-$_tm) > 0)) $x .= time_ago($_tm);
   return $x;
}





