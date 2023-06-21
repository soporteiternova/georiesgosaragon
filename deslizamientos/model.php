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
 * Vehicle data model
 * @author ITERNOVA (info@iternova.net)
 * @version 1.0.0 - 20230612
 * @package busstop
 * @copyright 2023 ITERNOVA SL
 * @license http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace georiesgosaragon\deslizamientos;

class model extends \georiesgosaragon\common\model {
    public $_database_collection = 'deslizamientos';
    public $objectid = -1;
    public $descripcion = '';
    public $peligrosidad = '';
    public $geometry = [];
    public $recordtime = '';

    /**
     * Updates a glide object from open data api, and creates it if doesn't exist
     *
     * @param $api_object
     *
     * @return bool
     */
    public function update_from_api( $api_object ) {
        $this->_id = null;
        $ret = false;

        if( isset( $api_object->properties->objectid)) {
            $array_criteria[] = [ 'objectid', 'eq', $api_object->properties->objectid, 'int' ];

            $array_obj = $this->get_all( $array_criteria, [], 0, 1 );
            if ( !empty( $array_obj ) ) {
                $saved_obj = reset( $array_obj );
                $this->_id = $saved_obj->_id;
            } else {
                $this->recordtime = date( 'Y-m-d H:i:s' );
            }

            $array_equivalence = [
                'objectid' => 'objectid',
                'descripcion' => 'descripcio',
                'peligrosidad' => 'peligrosid',
            ];

            foreach ( $array_equivalence as $attr => $tag ) {
                $this->{$attr} = $api_object->properties->$tag;
            }

            $this->geometry = json_encode($api_object->geometry);

            $ret = $this->store();
            var_dump($ret);
            die;
        }

        return $ret;
    }

    /**
     * Sets collection indexes
     * @return bool Resultado de la operacion
     * @throws \Exception
     */
    protected function ensureIndex() {
        $array_indexes = [
            [ 'objectid' => 1 ],
            [ 'recortdime' => 1 ]
        ];
        foreach ( $array_indexes as $index ) {
            $this->_database_controller->ensureIndex( $this->_database_collection, $index );
        }
        return true;
    }

    /**
     * Cofieds object to utf8/iso8859-1
     *
     * @param boolean $to_utf8 if true, converts to utf8, if false, converts to iso8859-1
     *
     * @return void
     */
    public function object_encode_data( $to_utf8 = false ) {
        $callback_function = \georiesgosaragon\common\utils::class . ( $to_utf8 ? '::detect_utf8' : '::detect_iso8859_1' );

        // Dates (format \MongoDate en UTC+0)
        $array_fields_datetime = [ 'recordtime', 'updated_at', 'created_at' ];
        foreach ( $array_fields_datetime as $key ) {
            $this->{$key} = \georiesgosaragon\common\databasemongo::datetime_mongodate( $this->{$key}, $to_utf8, false );
        }

        // Common attributes: integer
        $array_integer = [ 'objectid' ];
        foreach ( $array_integer as $key ) {
            $this->{$key} = (integer) $this->{$key};
        }

        // Common attributes: string
        $array_string = [ 'descripcion', 'peligrosidad' ];
        foreach ( $array_string as $key ) {
            $this->{$key} = (string) \call_user_func( $callback_function, $this->{$key} );
        }
        if ( !empty( $this->lat_lng ) ) {
            $this->lat_lng = [ (float) $this->lat_lng[ 0 ], (float) $this->lat_lng[ 1 ] ];
        }

        // Common attributes: booleans
        $array_boolean = [ 'active' ];
        foreach ( $array_boolean as $key ) {
            $this->{$key} = (boolean) $this->{$key};
        }
    }

    /**
     * Gets array of data to be showed as markers in maps
     *
     * @param array $array_criteria search criteria
     *
     * @return array
     * @throws \Exception
     */
    public function get_array_markers( $array_criteria = [] ) {
        $array_objs = $this->get_all( $array_criteria );
        $array_return = [];
        $server_url = \georiesgosaragon\common\utils::get_server_url();

        foreach ( $array_objs as $obj ) {
            $str_address = htmlspecialchars( $obj->address );
            $array_return[] = [
                'id' => $obj->_id,
                'lat' => $obj->lat_lng[ 0 ],
                'lng' => $obj->lat_lng[ 1 ],
                'title' => $str_address,
                'marker_color' => $obj->network === \georiesgosaragon\common\controller::ENDPOINT_BUS_STOP_ARAGON ? 'red' : 'green',
                'url' => $obj->network === \georiesgosaragon\common\controller::ENDPOINT_BUS_STOP_CTAZ ? $server_url . '/?&zone=bus_stop&action=get_remaining_time&bus_stop_id=' . $obj->code : '',
            ];
        }

        return $array_return;
    }

    /**
     * Gets vehicle object by code
     *
     * @param $bus_id
     *
     * @return void
     */
    public function select_by_code( $bus_id ) {
        $obj = $this;
        $array_criteria[] = [ 'code', 'eq', $bus_id, 'string' ];
        $array_obj = $this->get_all( $array_criteria, [], 0, 1 );

        if ( !empty( $array_obj ) ) {
            $obj = reset( $array_obj );
        }

        return $obj;
    }
}
