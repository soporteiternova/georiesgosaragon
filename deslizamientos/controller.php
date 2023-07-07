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
 * Vehicles controller
 * @author ITERNOVA (info@iternova.net)
 * @version 1.0.0 - 20230612
 * @package busstop
 * @copyright 2023 ITERNOVA SL
 * @license http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace georiesgosaragon\deslizamientos;

class controller {
    /**
     * Action controller for busstop class
     * @return bool
     */
    public function actions( $action = '', $debug = false ) {
        if ( $action === '' ) {
            $action = \georiesgosaragon\common\controller::get( 'action' );
        }
        switch ( $action ) {
            case 'crondaemon':
                return $this->crondaemon( $debug );
                break;
            case 'get_historic':
                return $this->get_historic();
                break;
            case 'show_in_map':
                return $this->get_map_json();
                break;
            case 'listing':
            default:
                return $this->listing();
                break;
        }

        return true;
    }

    /**
     * Gets glide list from opendata repository.
     * @return bool
     */
    protected function crondaemon($debug = false) {
        // Loading of vehicles listing
        $datetime = date( 'Hi' );
        if ( $debug || $datetime === '0005' ) {
            $count = 0;
            $api_url = \georiesgosaragon\common\controller::get_endpoint_url( \georiesgosaragon\common\controller::ENDPOINT_DESLIZAMIENTOS );
            $array_objs = json_decode( file_get_contents( $api_url ) );

            if ( isset( $array_objs->features  )) {
                foreach ( $array_objs->features as $obj ) {
                    $obj_deslizamiento = new model();
                    $obj_deslizamiento->update_from_api( $obj );
                    $count++;
                }
            }
            echo '<br/><br/><br/><br/><br/>Updated ' . $count . ' glides';
        }

        return true;
    }

    /**
     * Returns geojson data to load map layer
     * @return void
     */
    private function get_map_json() {
        $sw_lat = \georiesgosaragon\common\controller::get( 'sw_lat' );
        $sw_lng = \georiesgosaragon\common\controller::get( 'sw_lng' );
        $ne_lat = \georiesgosaragon\common\controller::get( 'ne_lat' );
        $ne_lng = \georiesgosaragon\common\controller::get( 'ne_lng' );
        $zoom = (int)\georiesgosaragon\common\controller::get( 'zoom' );

        $return = [];

        if( $zoom > 11 ) {
            $obj_model = new model();

            $array_opts[] = [ 'vertex', 'geoIntersects', [ [ $sw_lng, $sw_lat ], [ $ne_lng, $ne_lat ], 'Polygon' ], 'string' ];

            $array_obj = $obj_model->get_all( $array_opts, [], 0, 0, '_id', [ 'debug' => false ] );

            $return = \georiesgosaragon\common\utils::array_obj_to_geojson($array_obj);
        }

        echo json_encode( $return );
    }

}
