$(document).ready(function() {
    $('#from_date, #to_date').datepicker({
        dateFormat : "dd-mm-yy"
    });

    $('form #submit').click(function() {
        $('#query_results').html('query running');

        var sub = new Object();
        sub.from = $('form #from_date').val();
        sub.to = $('form #to_date').val();
        sub.query = $('form #query').val();
        submitQuery(sub);
        return false;
    })
})
function submitQuery(sub) {
    $.ajax('/talklondon/runquery/', {
        type : "POST",
        dataType : "json",
        data : sub
    }).done(renderResults)
}

function renderResults(data) {
    console.log(data);
    $('#query_results').html('');
    var html = $('<div id="description"></div>').html(data.desc);
    $('#query_results').prepend(html);
    var html = '<table class="table">';
    html += '<tr>';
    for(var i = 0; i < data.fields.length; i++) {
        html += '<th>' + data.fields[i] + '</th>';
    }
    html += '</tr>';
    for(var r = 0; r < data.results.length; r++) {
        html += '<tr>';
        for(var i = 0; i < data.fields.length; i++) {
            console.log( data.results[r]);
            html += '<th>' + data.results[r][data.fields[i]] + '</th>';
        }
        html += '</tr>';
    }
    html += '</table>';
    $('#query_results').append(html);
    html = '<pre>' + data.query + '</pre>';
    $('#query_results').append(html);
}