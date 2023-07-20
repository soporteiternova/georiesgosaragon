$(document).ready(function () {
    $('#roads_checkbox').change(function(){check_layers()});
    $('#railways_checkbox').change(function(){check_layers()});
    $('#dateMIN').datepicker();
    $('#dateMAX').datepicker();
});

function check_layers(){
    window[window['map_id']].overlayMapTypes.clear();
    enable_disable_layer('roadslayer', $('#roads_checkbox').prop('checked'));
    enable_disable_layer('railwayslayer', $('#railways_checkbox').prop('checked'));
}
function enable_disable_layer(layer_id, show) {
    if (window[layer_id ] !== undefined && show) {
        window[window['map_id']].overlayMapTypes.push(window[layer_id]);
    }
}

function show_json_layer(url,type){
    var map_id = window['map_id'];
    var bounds = window[map_id].getBounds();
    var southWest = bounds.getSouthWest();
    var northEast = bounds.getNorthEast();

    var zoom = parseInt(window[map_id].getZoom());
    url+='&sw_lat='+southWest.lat()+'&sw_lng='+southWest.lng()+'&ne_lat='+northEast.lat()+'&ne_lng='+northEast.lng()+'&zoom='+ zoom;

    window['layer_'+ type].setMap(null);
    window['layer_' + type]=null;
    window['layer_' + type] = new google.maps.Data();

    var color = 'grey';
    if( type === 'glides' ){
        color = 'red';
    } else if ( type === 'floods' ){
        color = 'blue';
    }

    if( zoom > 11 ){
        window['layer_' + type].loadGeoJson(url);
        window['layer_' + type].setStyle({fillColor: color});
        window['layer_' + type].setMap(window[map_id]);
    }
}

function disable_json_layers(){
    var types = ['glides', 'floods', 'collapses'];
    types.forEach(function(v){
        if(window['layer_' + v]!==undefined) {
            window['layer_' + v].setMap(null);
            window['layer_' + v] = null;
            window['layer_' + v] = new google.maps.Data();
        }
    });
}
