<?php

//defined a global "modules" variable which contains an array of all detected modules
function registerModules(){
    //init the list of modules and include the {modulename}.php files
    $modules = array();
    foreach (glob("libs/modules/*") as $filename)
    {
        $module = str_replace('libs/modules/', '', $filename);
        require('libs/modules/' . $module . '/' . $module . '.php');
        $modules[] = $module;   
    }
   
    //set the global so the exec hook runs in the right order
    $GLOBALS['modules'] = $modules;
}




function exec_hook($hook, $args = array(), $module = null){
    
    global $modules;
    
    $results = array();
    if(!is_null($module)){
        if(function_exists($module.'_'.$hook)){
            return call_user_func_array($module.'_'.$hook, $args);
        }else{
            return null;
        }
    }else{
        foreach($modules as $module){
            if(function_exists($module.'_'.$hook)){
                $results[$module] = call_user_func_array($module.'_'.$hook, $args);
            }
        }
        return $results;
    }
    
}

function elog($text, $level = 'notice', $source = 'core'){
    if (!is_string($text)){$text = print_r($text, true);}
    $warning_levels = array('debug', 'notice', 'warning', 'error');
    $leveln = array_search($level, $warning_levels);
    if (is_null($leveln)){$leveln = 1;}
}

function message($text, $level = 'info'){
    global $messages;
    
    $messages[] = array(
        'text' => $text,
        'level' => $level
    );
}


function number_to_word($number){
    $numbers = array('zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen');
    return $numbers[$number];
}

