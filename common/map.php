<?php
/**
 * GeoRiesgos Aragón - ITERNOVA <info@iternova.net>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Map generation function
 * @author ITERNOVA (info@iternova.net)
 * @version 1.0.0 - 20230612
 * @package common
 * @copyright 2023 ITERNOVA SL
 * @license http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace georiesgosaragon\common;

class map {

    /**
     * Returns google api key stored in config file
     * @return string
     */
    private static function google_key() {
        $file = __DIR__ . '/../config/googlemaps.key';
        return file_exists( __DIR__ . '/../config/googlemaps.key') ? trim( file_get_contents( $file ) ) : '';
    }

    /**
     * Generates a map with given markers
     *
     * @param array $array_markers marker array to be represented in map
     * @param int $sizex Ancho del mapa
     * @param int $sizey Alto del mapa
     *
     * @return string
     */
    public static function create_map( $array_markers, $sizex = 600, $sizey = 400, $set_center_user = false, $zoom = 8 ) {
        $rand = rand();

        // JS googlemaps
        $str = '<script type="text/javascript" src="https://maps.google.com/maps/api/js?key=' . self::google_key() . '&callback=initialize' . $rand . '" async defer></script>';

        // Capas
        $arrayopts[ 'wms_layers' ][] = [
            'title' => 'Carreteras',
            'id' => 'roadslayer',
            'src' => 'https://servicios.idee.es/wms-inspire/transportes',
            'layers' => 'TN.RoadTransportNetwork.RoadLink',
            'index' => 1,
            'active' => true
        ];
        $arrayopts[ 'wms_layers' ][] = [
            'title' => 'Ferrocarril',
            'id' => 'railwayslayer',
            'src' => 'https://servicios.idee.es/wms-inspire/transportes',
            'layers' => 'TN.RailTransportNetwork.RailwayLink',
            'index' => 2,
            'active' => true
        ];
        [ $str_layers, $map_layers ] = self::get_layers_config_wms( $rand, $arrayopts );

        // Generamos el mapa
        $str .= "<script type=\"text/javascript\">
                window['map{$rand}']=null;
 				function initialize{$rand}(){
                    const centerPoint = {lat: 41.65, lng: -0.87};
                    const ARAGON_BOUNDS= {
                          north: 42.93,
                          south: 39.85,
                          west: -2.17,
                          east: 0.77,
                    };
                    
                   const styledMap = new google.maps.StyledMapType([
                          {
                                'featureType': 'poi',
                                'stylers': [
                                    {'visibility': 'off'}
                                ]
                          }, {
                                'featureType': 'transit',
                                'stylers': [
                                    {'visibility': 'off'}
                                ]
                          },{
                            'featureType': 'road',
                            'stylers': [ {
                                'visibility': 'off'
                                } ]
                        }, {
                        'featureType': 'landscape',
                                'elementType': 'labels',
                                'stylers': [
                                  {
                                      'visibility': 'on' }
                            ]
                        }],
                        { name: 'Mapa' });
                        
 					 window['map{$rand}'] = new google.maps.Map(document.getElementById('incidents_map$rand'),{
                                                        zoom:12,
                                                        center: centerPoint,
                                                        backgroundColor: 'hsla(0, 0%, 0%, 0)',
                                                        restriction: {
                                                            latLngBounds: ARAGON_BOUNDS,
                                                            strictBounds: false
                                                        },                                           
                                                    });
                                                    
                    //window['map{$rand}'].mapTypes.set('NSW', 'styled_map');
                    
                    //var mapTypeControlOptions = {
                    //    mapTypeControlOptions: {
                    //        mapTypeIds: [google.maps.MapTypeId.TERRAIN, 'NSW']
                    //    }
                    //};
                    
                    //window['map{$rand}'].setOptions(mapTypeControlOptions);
                    
                    //window['map{$rand}'].setMapTypeId('styled_map');
                    window['map_id'] = 'map{$rand}';
                    window['layer_glides'] = new google.maps.Data();
                    window['layer_floods'] = new google.maps.Data();
                    window['layer_collapses'] = new google.maps.Data();
                    window['map{$rand}'].setZoom({$zoom});
                    ";
        if ( $set_center_user ) {
            $str .= "if (navigator.geolocation) {
                     navigator.geolocation.getCurrentPosition(function (position) {
                         initialLocation = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
                         window['map{$rand}'].setCenter(initialLocation);
                     });
                 }";
        }


        $str .= $str_layers;

        $str .= 'load_comunidad_' . $rand . '();';
        $str .= '}';

        $str .= 'function load_comunidad_' . $rand .'(){map' . $rand . '.data.loadGeoJson(
                    "' . utils::get_server_url() . '/common/files/comunidades.geojson"
              );}';


        $str .= '</script>';

        $str .= '<div class="incidents_map" id="incidents_map' . $rand . '" style="height:' . $sizey . 'px;width:100%;"></div>';


        return $str;
    }

    /**
     * Genera configuracion de capas de tipo WMS
     *
     * @param string $rand Id. de div de mapa
     * @param array $arrayopts Array de configuracion de mapas
     *
     * @return array [$arrayopts, $str_layers, $map_layers] actualizados
     */
    private static function get_layers_config_wms( $rand, $arrayopts ) {
        $str_layers = '';
        $map_layers = [];
        if ( isset( $arrayopts[ 'wms_layers' ] ) && is_array( $arrayopts[ 'wms_layers' ] ) && !empty( $arrayopts[ 'wms_layers' ] ) ) {
            $index = 0;
            foreach ( $arrayopts[ 'wms_layers' ] as $layer ) {
                if ( isset( $layer[ 'title' ], $layer[ 'id' ], $layer[ 'src' ] ) && ( isset( $layer[ 'layer' ] ) || isset( $layer[ 'layers' ] ) ) ) {
                    $layer_id = $layer[ 'id' ] . '_' . $rand . '_layer';

                    $array_config_default_values = [
                        'version' => '1.3.0',
                        'request' => 'GetMap',
                        'service' => 'WMS',
                        'format' => 'image/png',
                        'projection' => 'EPSG:4326',
                        'layer' => '',
                        'layers' => '',
                        'width' => '256',
                        'height' => '256',
                        'style' => '',
                        'geojson_mode' => true,
                        'opacity' => 1.0,
                        'active' => true,
                    ];
                    $layer = utils::initialize_array_config( $layer, $array_config_default_values );
                    $str_layers .= 'var ' . $layer_id . '_wmsOptions = ' . self::get_wms_config_str( $layer, $rand ) . ';';

                    $str_layers .= 'window["' . $layer['id'] . '"] = new google.maps.ImageMapType(' . $layer_id . '_wmsOptions);';
                    $str_layers .= 'window["' . $layer[ 'id' ] . '"].setOpacity(' . $layer[ 'opacity' ] . ');';
                    if ( $layer[ 'active' ] ) {
                        $str_layers .= 'map' . $rand . '.overlayMapTypes.setAt(' . $layer['index'].',window["' . $layer[ 'id' ] . '"]);';
                    }

                    $index += 2; // Reservamos el siguiente indice para capas temporales
                    $map_layers[ $layer_id ] = $layer;

                }
            }

        }

        return [ $str_layers, $map_layers ];
    }

    /**
     * @param array $layer Array configuracion de capa WMS
     * @param string $div_id Id. de div de mapa
     * @param string $str_time_param Parametro para datos variantes en el tiempo
     *
     * @return string
     */
    public static function get_wms_config_str( $layer, $div_id, $str_time_param = '' ) {
        $array_config_default_values = [
            'version' => '1.3.0',
            'request' => 'GetMap',
            'service' => 'WMS',
            'format' => 'image/png',
            'projection' => 'EPSG:4326',
            'width' => '256',
            'height' => '256',
            'style' => '',
            'geojson_mode' => true,
            'opacity' => 1.0,
            'layers' => '',
            'layer' => '',
        ];
        $layer = utils::initialize_array_config( $layer, $array_config_default_values );
        $layer_wms_with_get_attributes = strpos( $layer[ 'src' ], '?' );
        return '{
                getTileUrl: function(coord, zoom) {
                  var projection = map' . $div_id . '.getProjection();
                  var zpow = Math.pow(2, zoom);
                  var ul = new google.maps.Point(coord.x * 256.0 / zpow, (coord.y + 1) * 256.0 / zpow);
                  var lr = new google.maps.Point((coord.x + 1) * 256.0 / zpow, (coord.y) * 256.0 / zpow);
                  var ulw = projection.fromPointToLatLng(ul);
                  var lrw = projection.fromPointToLatLng(lr);
                  var bbox = ' . ( !$layer[ 'geojson_mode' ] ? 'ulw.lng() + "," + ulw.lat() + "," + lrw.lng() + "," + lrw.lat()' : 'ulw.lat() + "," + ulw.lng() + "," + lrw.lat() + "," + lrw.lng()' ) . ';
                  var url = "' . $layer[ 'src' ] . ( $layer_wms_with_get_attributes ? '&' : '?' ) .
               'SERVICE=' . $layer[ 'service' ] .
               '&VERSION=' . $layer[ 'version' ] .
               '&REQUEST=' . $layer[ 'request' ] .
               '&BBOX=" + bbox + "&WIDTH=' . $layer[ 'width' ] .
               ( $layer[ 'layers' ] !== '' ? '&LAYERS=' . $layer[ 'layers' ] : '&LAYER=' . $layer[ 'layer' ] ) .
               '&STYLES=' . $layer[ 'style' ] .
               ( $layer[ 'geojson_mode' ] ? '&CRS=' : '&SRS=' ) . $layer[ 'projection' ] .
               '&HEIGHT=' . $layer[ 'height' ] .
               '&FORMAT=' . $layer[ 'format' ] .
               '&TRANSPARENT=true' . $str_time_param . '";
                  return url;
              },
              tileSize: new google.maps.Size(256, 256)
              }';
    }

    /**
     * Controlador de acciones
     * @return void
     */
    public function actions() {
        $action = controller::get( 'action' );
        $ret = '';
        switch ($action){
            case 'deslizamientos_json':
                $ret = $this->deslizamientos_json();
                break;
            default:
        }

        return $ret;
    }

    /**
     * Devuelve json para deslizamientos
     * @return string
     */
    private function deslizamientos_json() {
        $date_min = controller::get('dateMIN');
        $date_max = controller::get('dateMAX');

        if( empty( $date_min ) ) {
            $date_min = date('Y-m-d 00:00:00', strtotime( '-1 year') );
        } else {
            $date_min = date('Y-m-d 00:00:00', strtotime( $date_min ) );
        }

        if( empty( $date_max) ) {
            $date_max = date( 'Y-m-d 23:59:59' );
        } else {
            $date_max = date( 'Y-m-d 00:00:00', strtotime( $date_max ) );
        }

        $hash = sha1( 'glides_' . $date_min . '_' . $date_max );
        $file_path = utils::get_path() . '/cache/' . $hash;

        if( file_exists( $file_path ) ) {
            return file_get_contents( $file_path );
        }

        $obj_glide = new \georiesgosaragon\deslizamientos\model();

        $geojson = $obj_glide->get_json( $date_min, $date_max );
        return $geojson;

    }

}
