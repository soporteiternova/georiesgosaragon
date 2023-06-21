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
 * MongoDB database functions
 * @author ITERNOVA (info@iternova.net)
 * @version 1.0.0 - 20230612
 * @package common
 * @copyright 2023 ITERNOVA SL
 * @license http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace georiesgosaragon\common;

class databasemongo {
    /** @var \georiesgosaragon\common\databasemongo[] $_singleton database object */
    private static $_singleton = [];
    const DATABASE_ID = 'mongodb';

    /**
     * @var array|null|\georiesgosaragon\common\databasemongo[] $_databases database object array like:
     * <br/>
     *      - id: database id
     *      - host, port, user, password, database: database connection data
     */
    private $_databases = null;

    /** @var bool $database selected database object */
    public $database = false;

    private function __construct() {
        $this->load_config();
        $this->select_database();
    }

    /**
     * Configuration load. Password must be set into the config/mongodb.key file. Config folder must be at root path
     * @return bool Resultado de la operacion
     */
    private function load_config() {
        if ( $this->_databases === null ) {
            unset( $this->_databases );

            $this->_databases[ 'mongodb' ] = new databasemongoconnection(
                (string) 'mongodb', // datbase id
                [ 'localhost:27017' ], // server array
                'georiesgosaragon', // database user
                file_get_contents( __DIR__ . '/../config/mongodb.key' ), // database password
                'georiesgosaragon' //database to be selected
            );

            return true;
        }

        return false;
    }

    /**
     * Providing of database object
     * @return \georiesgosaragon\common\databasemongo Objeto controlador de base de datos
     */
    public static function getDatabase() {
        if ( !isset( self::$_singleton[ static::DATABASE_ID ] ) || null === self::$_singleton[ static::DATABASE_ID ] ) {
            self::$_singleton[ static::DATABASE_ID ] = new self();
        }

        return self::$_singleton[ static::DATABASE_ID ];
    }

    /**
     * Round-robin funtion at selected database.
     * @return void
     */
    private function select_database() {
        if ( !is_array( $this->_databases ) ) {
            $this->load_config();
        }

        $database_selected = reset( $this->_databases );
        $this->database = $database_selected->connect();
    }

    /**
     * Data setting for stored objects
     *
     * @param mixed $object object
     * @param array $array_data object attributes array
     * @param array $array_booleans boolean object attributes array
     * @param array $array_datetime datetime object attributes array
     * @param bool $to_utf8 must be true to store in database, false to load data and show in the interface
     *
     * @return mixed codified object
     */
    public static function set_object_data( &$object, $array_data = [], $array_booleans = [], $array_datetime = [], $to_utf8 = true ) {
        unset( $array_data[ '_database_collection' ] );

        if ( isset( $array_data[ '_id' ] ) && is_a( $array_data[ '_id' ], '\MongoDB\BSON\ObjectID' ) ) {
            $_id = $array_data[ '_id' ];
            unset( $array_data[ '_id' ] );
            $object->_id = $_id->__toString();
        }

        // Si hay datos en el array...
        if ( !empty( $array_data ) ) {
            foreach ( $array_data as $key => $value ) {
                if ( property_exists( $object, $key ) ) {
                    if ( is_array( $value ) ) {
                        $object->{$key} = array_replace_recursive( $object->{$key}, $value );
                    } else {
                        $object->{$key} = self::get_typed_var( $value );
                    }
                }
            }
        }

        if ( !empty( $array_booleans ) ) {
            foreach ( $array_booleans as $field_boolean ) {
                if ( isset( $object->{$field_boolean} ) && array_key_exists( $field_boolean, $array_data ) ) {
                    $object->{$field_boolean} = ( isset( $array_data[ $field_boolean ] ) ? (boolean) $array_data[ $field_boolean ] : false );
                }
            }
        }

        if ( !empty( $array_datetime ) ) {
            foreach ( $array_datetime as $field_datetime_ID ) {
                if ( isset( $array_data[ $field_datetime_ID . '_date' ], $array_data[ $field_datetime_ID . '_time' ] ) && in_array( $field_datetime_ID, $array_datetime, false ) ) {
                    $object->{$field_datetime_ID} = (string) date( 'Y-m-d H:i:s', strtotime( $array_data[ $field_datetime_ID . '_date' ] . ' ' . $array_data[ $field_datetime_ID . '_time' ] ) );
                }
            }
        }

        return $object;
    }

    /**
     * Gets casted value
     * @return mixed value to be cast
     * @var mixed $var casted value
     */
    public static function get_typed_var( $var ) {
        switch ( gettype( $var ) ) {
            case 'NULL':
                return null;
                break;
            case 'integer':
                return (int) $var;
                break;
            case 'double':
                return (float) $var;
                break;
            case 'object': // objects
                return $var;
                break;
            case 'boolean': // Boolean
                return (boolean) $var;
                break;
            case 'string':
            default:
                return (string) utils::detect_utf8( $var );
                break;
        }
    }

    /**
     * Generates criteria for documents selection in database
     *
     * @param $array_opts
     *
     * @return array|void
     */
    public static function get_array_criteria( $array_opts = [] ) {
        $array_return = [];

        if ( !empty( $array_opts ) ) {
            foreach ( $array_opts as $opt ) {
                $opt_size = count( $opt );
                if ( \in_array( $opt_size, [ 3, 4 ], false ) ) {
                    [ $key, $modifier, $value, $data_type ] = $opt;
                    if ( $data_type === '' ) {
                        $data_type = 'string';
                    }
                    if ( null !== $value && !\in_array( $modifier, [ 'and', 'or' ], true ) ) {
                        $value = utils::detect_utf8( $value );
                    }
                    switch ( $modifier ) {
                        case 'gt':
                        case 'gte':
                        case 'min':
                        case 'lt':
                        case 'lte':
                        case 'max':
                            if ( $modifier === 'min' ) {
                                $modifier = 'gte';
                            } elseif ( $modifier === 'max' ) {
                                $modifier = 'lte';
                            }
                            if ( $data_type === 'MongoDate' ) {
                                $value = self::datetime_mongodate( $value, true );
                            } else {
                                settype( $value, $data_type );
                            }
                            $array_return[ $key ][ '$' . $modifier ] = $value;
                            break;

                        case 'in':
                        case 'nin':
                            if ( !is_array( $value ) ) {
                                $value = explode( ',', $value );
                            }
                            if ( $data_type === 'integer' || $data_type === 'int' ) {
                                $value = array_map( '\intval', $value );
                            } elseif ( $data_type === 'float' ) {
                                $value = array_map( '\floatval', $value );
                            } elseif ( $data_type === 'MongoId' ) {
                                $value = array_map( [ databasemongo::class, 'mongoIdVal' ], $value );
                            } elseif ( $data_type === 'MongoDate' ) {
                                $value = self::datetime_mongodate( $value, true );
                            } else {
                                $value = array_map( '\strval', $value );
                            }
                            if ( !isset( $array_return[ $key ] ) || !is_scalar( $array_return[ $key ] ) ) {
                                $array_return[ $key ][ '$' . (string) $modifier ] = array_values( $value );
                            }
                            break;

                        case 'eq':
                            if ( $data_type === 'MongoId' ) {
                                $value = self::mongoIdVal( $value );
                            } elseif ( is_array( $value ) && empty( $value ) ) {
                                $array_return[ $key ] = $value;
                            } elseif ( $data_type === 'MongoDate' ) {
                                $value = self::datetime_mongodate( $value, true );
                            } elseif ( null !== $value ) {
                                settype( $value, $data_type );
                            }
                            $array_return[ $key ] = $value;
                            break;

                        case 'ne':
                            if ( $data_type === 'MongoId' ) {
                                $value = self::mongoIdVal( $value );
                            } elseif ( null !== $value ) {
                                settype( $value, $data_type );
                            }
                            $array_return[ $key ][ '$ne' ] = $value;
                            break;

                        case 'like':
                            $array_return[ $key ] = new \MongoDB\BSON\Regex( $value, 'i' );
                            break;

                        case 'blike':
                            $array_return[ $key ] = new \MongoDB\BSON\Regex( '^' . $value, 'i' );
                            break;

                        case 'and':
                            $dollar_modifier = '$and';
                            if ( isset( $array_return[ $dollar_modifier ] ) ) { // Si existe previamente... mergeamos, porque si no se sobreescribe el ultimo and/or
                                /** @noinspection SlowArrayOperationsInLoopInspection */
                                $array_return[ $dollar_modifier ] = \array_merge( $array_return[ $dollar_modifier ], $value );
                            } else { // No existe previamente... se crea
                                $array_return[ $dollar_modifier ] = $value;
                            }
                            break;
                        case 'or':
                            // Todos los OR independientes los metemos en un $and... porque si no se mergearan
                            // y un OR( W, X ) AND OR( Y, Z ) se convertiria en un OR (W, X, Y, Z), y seria erroneo
                            if ( !isset( $array_return[ '$and' ] ) ) { // No existe previamente... se crea
                                $array_return[ '$and' ] = [];
                            }
                            $array_return[ '$and' ][] = [ '$or' => $value ];
                            break;

                        case 'near': // geospatial - $value es array( (float) $lat, (float) $lng ).
                            if ( isset( $value[ 0 ], $value[ 1 ] ) ) {
                                $array_return[ $key ][ '$' . $modifier ] = [ (double) $value[ 0 ], (double) $value[ 1 ] ];
                                if ( isset( $value[ 2 ] ) ) { // $maxDistance es en grados en la consulta... pero pasaran metros! Convertimos...
                                    $array_return[ $key ][ '$maxDistance' ] = (double) $value[ 2 ] / 111000.0;
                                }
                            }
                            break;
                        case 'geoWithin': // geospatial - $value es un array compuesto, tal que: array( vertice 1, vertice 2), donde cada vertice es del tipo array( (float) $lat, (float) $lng ).
                            if ( isset( $value[ 0 ], $value[ 1 ] ) && count( $value[ 0 ] ) === 2 && count( $value[ 1 ] ) === 2 ) {
                                $array_return[ $key ][ '$' . $modifier ] = [ '$box' => [ [ (float) $value[ 0 ][ 0 ], (float) $value[ 0 ][ 1 ] ], [ (float) $value[ 1 ][ 0 ], (float) $value[ 1 ][ 1 ] ] ] ];
                            }
                            break;
                        case 'geoIntersects': // geospatial - $value es un array compuesto, tal que: array( lat, lng, type ).
                            if ( isset( $value[ 0 ], $value[ 1 ] ) ) {
                                if ( !isset( $value[ 2 ] ) || !\in_array( $value[ 2 ], [ 'Point', 'Polygon' ] ) ) {
                                    $value[ 2 ] = 'Point';
                                }
                                $array_return[ $key ][ '$' . $modifier ] = [ '$geometry' => [ 'type' => $value[ 2 ], 'coordinates' => [ (float) $value[ 1 ], (float) $value[ 0 ] ] ] ];
                            }
                            break;
                        case 'maxDistance': // geospatial - Distancia maxima...
                            // $maxDistance es en grados en la consulta... pero pasaran metros! Convertimos...
                            $array_return[ $key ][ '$maxDistance' ] = (double) $value / 111000.0;
                            break;

                        case 'mod' : // $mod: Hay que pasar para cada atributo un array [ x, y ] para efectuar operacion ( atributo % x == y )
                            if ( isset( $value[ 0 ], $value[ 1 ] ) ) {
                                settype( $value[ 0 ], $data_type );
                                settype( $value[ 1 ], $data_type );
                                $array_return[ $key ][ '$' . $modifier ] = [ $value[ 0 ], $value[ 1 ] ];
                            }
                            break;

                        case 'elemMatch': // Similar a 'in', solo que en este caso, si se pasa un array con varias combinaciones a buscar para el elemento dado, deben cumplirse todas
                            $array_return[ $key ][ '$' . $modifier ] = self::get_array_criteria( $value );
                            break;

                        case 'exists':
                            // Si no se pasa un valor, se supone que el comportamiento deseado es que filtre por los que existan
                            $value = ( $value === null ? true : $value );
                            $array_return[ $key ][ '$exists' ] = (integer) $value;
                            break;

                        case 'where':
                        case 'expr':
                            $array_return[ '$' . $modifier ] = $value;
                            break;

                        default: // Resto de casos
                            settype( $value, $data_type );
                            $array_return[ $key ][ '$' . $modifier ] = $value;
                            break;
                    }
                } else { // Error... la opcion no tiene los parametros requeridos
                    print_r( 'ERROR MongoDB (get_array_criteria): ' . json_encode( $opt ) );
                    die( 'CRITERIA' );
                }
            }
        }

        // Y devolvemos filtrado
        return $array_return;
    }

    /**
     * Convert string into MongoId object
     *
     * @param string $_id object _id
     *
     * @return \MongoDB\BSON\ObjectID
     */
    public static function mongoIdVal( $_id ) {
        if ( $_id === null || $_id === 'null' || ( is_numeric( $_id ) && $_id < 0 ) || $_id === '' ) {
            return null;
        }

        return new \MongoDB\BSON\ObjectID( $_id );
    }

    /**
     * Conversion of Y-m-d H:i:s (string) formatted data into a MongoDB datatime object
     *
     * @param mixed $datetime DateTime or string Y-m-d H:i:s, or MongoDB\BSON\UTCDateTime
     * @param bool $to_mongodate true if need to convert to MongoDate, false if not
     * @param bool $datetime_object if $to_mongodate is false, must return DateTime object if true
     * @param string $timezone_selected Timezone like "Europe/madrid"
     *
     * @return mixed generated value/object
     */
    public static function datetime_mongodate( $datetime, $to_mongodate = true, $datetime_object = false, $timezone_selected = '' ) {
        if ( empty( $timezone_selected ) ) {
            $timezone_selected = 'Europe/Madrid';
        }
        $timezone = new \DateTimeZone( $timezone_selected );
        if ( $to_mongodate ) {
            if ( !is_a( $datetime, '\MongoDB\BSON\UTCDateTime' ) ) {
                if ( !is_a( $datetime, \DateTime::class ) ) {
                    $datetime = date( 'Y-m-d H:i:s', strtotime( str_replace( 'T', ' ', $datetime ) ) );
                    $datetime = new \DateTime( $datetime, $timezone );
                }

                return new \MongoDB\BSON\UTCDateTime( $datetime->getTimestamp() * 1000 );
            }

            return $datetime;
        }

        if ( is_a( $datetime, '\MongoDB\BSON\UTCDateTime' ) ) {
            $object = $datetime->toDateTime();
        } else {
            $datetime = empty( $datetime ) ? date( 'Y-m-d H:i:s' ) : date( 'Y-m-d H:i:s', strtotime( str_replace( 'T', ' ', $datetime ) ) );
            $object = new \DateTime( $datetime, new \DateTimeZone( 'UTC' ) );
        }

        if ( method_exists( $object, 'setTimezone' ) ) {
            $object->setTimezone( $timezone );
        }

        return ( $datetime_object ? $object : $object->format( 'Y-m-d H:i:s' ) );
    }

    /**
     * Stores data in database
     *
     * @param string $collection database collection where object must be stored
     * @param mixed $object object to be stored
     * @param array $object_attributes_not_required object attributes that won't be stored
     * @param array $options MongoCollection::save options array http://www.php.net/manual/en/mongocollection.save.php i.e.: 'safe', 'fsync', 'timeout'
     *
     * @return string stored object _id
     */
    public function store_object( $collection, $object, $object_attributes_not_required = [], $options = [] ) {
        try {
            // Convertimos el objeto en array
            $object_attributes_not_required[ '_database_controller' ] = true;
            $object_attributes_not_required[ '_database_controller_class' ] = true;
            $object_attributes_not_required[ '_database_collection' ] = true;
            $object_array = self::get_object_data( $object, $object_attributes_not_required, false );

            if ( isset( $object_array[ '_id' ] ) && $object_array[ '_id' ] !== null ) {
                $options[ 'upsert' ] = true;
                $result = $this->database->{$collection}->replaceOne( [ '_id' => $object_array[ '_id' ] ], $object_array, $options );
                $_id = $object_array[ '_id' ];
            } else { // Create new object
                $result = $this->database->{$collection}->insertOne( $object_array, $options );
                $_id = $result->getInsertedId();
            }

            return (string) ( is_a( $_id, 'MongoDB\BSON\ObjectID' ) ? $_id->__toString() : $_id );
        } catch ( \MongoDB\Exception\Exception $e ) {
            print_r( 'ERROR MongoDB (store): ' . $e->getMessage() );
            die( 'STORE' );
        } catch ( \MongoDB\Driver\Exception\InvalidArgumentException $e ) {
            print_r( 'ERROR MongoDB - InvalidArgumentException (store): ' . $e->getMessage() );
            die( 'STORE' );
        }
    }

    /**
     * Converts a given object into an array
     *
     * @param mixed $object object to be converted
     * @param array $array_attibutes_not_required array with attributes that won't be stored
     *
     * @return array data array
     */
    public static function get_object_data( $object, $array_attibutes_not_required = [], $remove_id = true ) {
        // Convertimos objeto a array
        $array_attibutes_not_required[ '_database_controller' ] = true;
        $array_attibutes_not_required[ '_database_controller_class' ] = true;
        $array_attibutes_not_required[ '_database_collection' ] = true;
        $object_array = utils::cast_object_to_array( $object, [ 'MongoDB\BSON\ObjectID', 'MongoDB\BSON\UTCDateTime', '\DataBase_MongoDB_Controller' ] );

        if ( empty( $object_array[ '_id' ] ) ) {
            unset( $object_array[ '_id' ] );
        } else {
            if ( isset( $object_array[ '_id' ][ '$id' ] ) && is_object( $object_array[ '_id' ][ '$id' ] ) ) {
                $_id = $object_array[ '_id' ][ '$id' ];
                unset( $object_array[ '_id' ] );
                $object_array[ '_id' ] = $_id;
            }

            if ( $remove_id ) {
                unset( $object_array[ '_id' ] );
            } else {
                $object_array[ '_id' ] = self::mongoIdVal( $object_array[ '_id' ] );
            }
        }

        return utils::array_diff_key_recursive( $object_array, $array_attibutes_not_required );
    }

    /**
     * Object counting function bu search critera in a given collection
     * @link https://www.php.net/manual/en/mongocollection.count.php
     *
     * @param string $collection collection where find and count objects
     * @param array $criteria search criteria array
     * @param int $limit number of elementes to be returned
     * @param int $skip number of elementes not to be returned
     *
     * @return \MongoCursor database results cursor
     * @throws \Exception
     */
    public function count( $collection, $criteria = [], $limit = 0, $skip = 0 ) {
        try {
            $array_options = [];
            if ( $limit > 0 ) {
                $array_options[ 'limit' ] = $limit;
            }
            if ( $skip > 0 ) {
                $array_options[ 'skip' ] = $skip;
            }

            return $this->database->{$collection}->count( $criteria, $array_options );
        } catch ( \MongoDB\Exception\Exception $e ) {
            print_r( 'ERROR MongoDB (count): ' . $e->getMessage() );
            die( 'COUNT' );
        }
    }

    /**
     * Search index definition
     * @link http://www.php.net/manual/en/mongocollection.ensureindex.php
     *
     * @param string $collection database collection where indexes will be denifed
     * @param array $array_keys key=>index deinition array, like:
     *                             - array( 'title' => 1 ): asc title
     *                             - array( 'username' => 1, 'date' => -1 ):username asc, date desc
     *                             - array( 'point_latlng' => "2d" ); geospatial index {2d|2dsphere}
     * @param array $options options array ('safe', 'background', 'dropDups', 'unique') to create indexes
     *
     * @return mixed
     * @throws \Exception
     */
    public function ensureIndex( $collection, $array_keys, $options = [] ) {
        try {
            return $this->database->{$collection}->createIndex( $array_keys, $options );
        } catch ( \MongoDB\Exception\Exception $e ) {
            print_r( 'ERROR MongoDB (ensureIndex): ' . $e->getMessage() );
            die( 'ENSURE_INDEX' );
        }
    }

    /**
     * Database search function from slection criteria
     * @link http://www.php.net/manual/en/mongocollection.find.php
     *
     * @param string $collection database collection to do search in
     * @param array $criteria search criteria array
     * @param array $sort sorting array
     * @param int $limit number of elements to be returned
     * @param int $skip number of elements not to be returned
     * @param array $attributes object attributes to be returned
     *
     * @return MongoDB\Driver\Cursor Object with result rowset cursor
     * @throws \Exception
     */
    public function select_by_criteria( $collection, $criteria = [], $sort = [], $limit = 0, $skip = 0, $attributes = [] ) {
        try {
            $array_options = [];
            if ( !empty( $attributes ) ) {
                $array_options[ 'projection' ] = $attributes;
            }
            if ( $limit > 0 ) {
                $array_options[ 'limit' ] = (int) $limit;
            }
            if ( $skip > 0 ) {
                $array_options[ 'skip' ] = $skip;
            }
            if ( !empty( $sort ) ) {
                $array_options[ 'sort' ] = $sort;
            }

            return $this->database->{$collection}->find( $criteria, $array_options );
        } catch ( \MongoDB\Exception\Exception $e ) {
            print_r( 'ERROR MongoDB (select): ' . $e->getMessage() );
            die( 'SELECT' );
        }
    }

    /**
     * Realiza busqueda en la coleccion, en funcion de los parametros introducidos. Equivalente a MongoCollection::findOne() de PHP::MongoDB
     * @link http://www.php.net/manual/en/mongocollection.find.php
     *
     * @param string $collection Nombre de la coleccion de la base de datos en la que se eliminaran objetos
     * @param string $_id Identificador del objeto a buscar
     *
     * @return array Objeto (en formato array). Requiere hacer cast del array resultante para convertilo en objeto
     * @throws \Exception
     */
    public function select_by_id( $collection, $_id ) {
        try {
            // Buscamos y devolvemos el objeto
            return ( ( is_numeric( $_id ) && strlen( (string) $_id ) < 11 ) ? null : $this->database->{$collection}->findOne( [ '_id' => self::mongoIdVal( $_id ) ] ) );
        } catch ( MongoDB\Driver\Exception $e ) {
            print_r( 'ERROR MongoDB (select_by_id): ' . $e->getMessage() . $collection, [ '_id' => (string) $_id ] );
            die( print_r( 'SELECT_ID' ) );
        }
    }
}
