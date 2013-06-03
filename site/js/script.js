$(document).ready(function() {

    var apiBaseUrl = 'http://trackmyroute.fwd.wf/TrackMyRoute/api/router/';
    var apiDataType = 'json';
    //var apiExtraHeaders = { 'X-Api-Key' : '' };

    var makeCall = function(apiUrl, requestMethod, extraData) {
        
        // show spinnner
        $('#loading').show();
        
        // make the call
        $.ajax({
            url : apiUrl,
            type: requestMethod,
            dataType : apiDataType,
            data : extraData
        }).success(function(data, textStatus, jqXHR) {
            console.log('succeed')
        }).error(function(jqXHR, textStatus, errorThrown) {
            console.log('error');
        });
    }
 
    // hook form submit
    $('#routeform').on('submit', function(e, dontpush){
        e.preventDefault();
        var data = {route_name : $("#name").val(), city : $("#city").val()};
        console.log(data);
        makeCall(apiBaseUrl + 'routes', 'POST', data);
    });

    // hook form submit
    $('#checkpointform').on('submit', function(e, dontpush){
        e.preventDefault();
        var data = {route_id : parseInt($("#id").val()), description : $("#description").val(), longitude : parseFloat($("#longitude").val()), checkpoint : $("#checkpoint").val(), latitude : parseFloat($("#latitude").val())};
        console.log(data);
        makeCall(apiBaseUrl + 'checkpoints', 'POST', data);
    });
});