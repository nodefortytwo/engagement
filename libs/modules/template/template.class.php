<?php

class Template{
    public $htmlHead, $close, $content, $title, $messages;
    
    public function __construct() {
        $this->load_css();
        $this->load_js();
        $this->load_less();
    }
    
    public function load_default_wrappers(){
        
        $this->htmlHead = $this->get_template('htmlHead');
        $this->close = $this->get_template('close');      
    }
    
    private function get_template($template){
        ob_start();
        include('theme/'.$template.'.tpl.php');
        $ret = ob_get_contents();
        ob_end_clean();
        return $ret;
    }
    
    public function render(){
        //parse the css and js files to generate the mark-up
        $this->compile_css();
        $this->compile_js();
        $this->compile_less();
        $this->get_messages();
        $this->compile_messages();
        //Load in the wrappers
        $this->load_default_wrappers();
        //render the body
        $html = '';
        $html .= $this->htmlHead;
        $html .= $this->content;
        $html .= $this->close;
        return $html;
    }
    
    private function load_css(){
        $this->css = array();
        $files = exec_hook('global_css');
        foreach($files as $module_name=>$module){
            foreach($module as $file){
                if(strpos($file, '//') !== false){
                    $this->css[] = $file;
                }else{
                    $this->css[] = get_url('/' . PATH_TO_MODULES . '/' . $module_name . '/' . $file);
                }
            }
        }
    }
    
    public function add_css($file, $module_name){
        
        $this->css[] = get_url('/' . PATH_TO_MODULES . '/' . $module_name . '/' . $file);
        
    }
    
    private function compile_css(){
        $this->css_compiled = '';
        foreach($this->css as $file){
            $this->css_compiled .= "\t" . '<link rel="stylesheet" href="'.$file.'">' . "\n";
        }
    }
    
    private function load_js(){
        $this->js = array();
        $files = exec_hook('global_js');
        foreach($files as $module_name=>$module){
            foreach($module as $file){
                $this->js[] = get_url('/' . PATH_TO_MODULES . '/' . $module_name . '/' . $file);
            }
        }
    }
    
    public function add_js($file, $module_name){
        
        if (beginsWith($file, 'http://') || beginsWith($file, 'https://') || beginsWith($file, '//')){
            $this->js[] = $file;
        }else{
            $this->js[] = get_url('/' . PATH_TO_MODULES . '/' . $module_name . '/' . $file);
        }
        
    }
    
    
    private function compile_js(){
        $this->js_complied = '';
        foreach($this->js as $file){
            $this->js_complied .= "\t" . '<script src="'.$file.'"></script>' . "\n";
        }
    }
    
    //load less
    private function load_less(){
        $this->less = array();
        $files = exec_hook('global_less');
        foreach($files as $module_name=>$module){
            foreach($module as $file){
                $this->less[] = get_url('/' . PATH_TO_MODULES . '/' . $module_name . '/' . $file);
            }
        }
    }
    
    public function add_less($file, $module_name){
        
        $this->less[] = get_url('/' . PATH_TO_MODULES . '/' . $module_name . '/' . $file);
        
    }
    
    public function compile_less(){
        
        $this->less_complied = '';
        foreach($this->less as $file){
            $this->less_complied .= "\t" . '<link rel="stylesheet/less" type="text/css" href="'.$file.'">' . "\n";
        }
        
    }
    
    
    public function c($content, $clear = false){
        if ($clear){
            $this->content = $content;
        }else{
            $this->content .= $content;
        }
    }
    
    private function get_messages(){
        global $messages;
        $this->messages = $messages;
    }
    
    private function compile_messages(){
        $this->compiled_messages = '';
        if(is_array($this->messages)){
            foreach($this->messages as $message){
               $this->compiled_messages .= '<div class="alert alert-'. $message['level'] .'">' . $message['text'] . '</div>';
            }
        }
        $this->compiled_messages = '<div class="alerts span11">' .  $this->compiled_messages . '</div>';
    }
}