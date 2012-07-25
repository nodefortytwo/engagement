$(document).ready(function() {
	log_update();
});

function log_update(){
	$.ajax({
		url : '/' + SYSTEM.SITE_ROOT + '/log/view/json/~/' + log_last_id + '/',
		success : function(data) {
			data = eval(data);
			log_update_table_rows(data);
		}
	});
	t=setTimeout("log_update()",3000);
}

function log_update_table_rows(data) {
	for(var i = 0; i < data.length; i++) {
		var row = data[i];
		//generate the row markup
		var markup = '<tr class="js">';
		markup += '<td>'+row.id+'</td>'; 
		markup += '<td>'+row.source+'</td>';
		markup += '<td>'+row.level+'</td>';
		markup += '<td><pre>'+row.text+'</pre></td>';
		markup += '<td>'+row.created+'</td>';
		markup += '</tr>';
		
		//set a new last_id
		log_last_id = row.id;
		if ($('table tbody').children().length > 20){
            $('table tbody').children().last().remove();
        }
		$('table tbody:last').prepend(markup);
		markup='';
	}
}