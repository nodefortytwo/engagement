var total_users;
$(document).ready(function(){
    total_users = 0;
    writeToLog('Starting Post Collection Now:');
    recursiveRequest(function(){
        writeToLog('Finished!');
    });
});


function recursiveRequest(callback){
    var url = '/' + SYSTEM.SITE_ROOT + '/user/process/json/';
    writeToLog('Requesting: ' + url);
    $.ajax({
        url : url,
        dataType : 'json'
    }).done(function(data){
        console.log(data);
        if (data.count > 0){
            total_users += data.count;
            writeToLog('Total Users: ' + total_users);
            recursiveRequest(callback);
        }else{
            callback();
        }
        
    });
}


function writeToLog(string){
    $('#log').append(string + '\n');
}
