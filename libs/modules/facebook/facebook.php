<?php

function facebook_init(){
    require('src/facebook.php');
    global $facebook;
    $facebook = new Facebook(array(
      'appId'  => '490421517637633',
      'secret' => 'd61f208bc94405e5adf0f025d10a2df9',
    ));
}


