$(document).ready(function() {
	
	$('a.colorbox').colorbox({'opacity':1});
	
});

function getUrl(path){
    
    if (path.charAt(0) == '/'){
        path = path.substr(1);
    }
    
    if (SYSTEM.SITE_ROOT.length > 0) {
        path = '/' + SYSTEM.SITE_ROOT + '/' + path;
    } else {
        path = '/' + path;
    }

    return path.replace('//','/');
}