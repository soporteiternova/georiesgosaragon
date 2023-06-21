$(document).ready(function () {
    $('#roads_checkbox').change(function(){check_layers()});
    $('#railways_checkbox').change(function(){check_layers()});
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
