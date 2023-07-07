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

    window['layer_glides'].setMap(null);
    window['layer_glides']=null;
    window['layer_glides'] = new google.maps.Data();

    var color = 'grey';
    if(type==='glides'){
        color = 'red';
    }

    if( zoom > 11 ){
        window['layer_glides'].loadGeoJson(url);
        window['layer_glides'].setStyle({fillColor: color});
        window['layer_glides'].setMap(window[map_id]);
    }
}
