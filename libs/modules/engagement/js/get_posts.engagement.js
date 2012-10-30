var total_posts;
$(document).ready(function(){
    var id = $('#page-data').attr('data-page-id');
    var recent_post =  $('#page-data').attr('data-recent-post');
    var first_request = 'http://graph.facebook.com/' + id + '/posts';

    total_posts = 0;
    writeToLog('Starting Post Collection Now:');
    recursiveRequest(id, recent_post, 0, function(){
        writeToLog('Finished!');
    });
});


function recursiveRequest(id, since, until, callback){
    var url = '/page/get_posts/json/~/' + id + '/' + since + '/' + until + '/';
    url = getUrl(url);
    writeToLog('Requesting: ' + url);
    $.ajax({
        url : url,
        dataType : 'json'
    }).done(function(data){
        console.log(data);
        if (data.post_count > 0){
            total_posts += data.post_count;
            writeToLog('Total Posts: ' + total_posts);
            recursiveRequest(id, since, data.next.until, callback);
        }else{
            callback();
        }
        
    });
}


function writeToLog(string){
    $('#log').append(string + '\n');
}
