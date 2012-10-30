$(document).ready(function() {
	
	$('a.colorbox').colorbox({'opacity':1});
	
});

function get_url(path){

    if (SYSTEM.SITE_ROOT != '') {
        path = '/' + SYSTEM.SITE_ROOT + '/' + path;
    } else {
        path = '/' + path;
    }

    return '' . path.replace('//','/');
}