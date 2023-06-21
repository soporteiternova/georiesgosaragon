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
 * Logic model class to be extended in objects
 * @author ITERNOVA (info@iternova.net)
 * @version 1.0.0 - 20230612
 * @package common
 * @copyright 2023 ITERNOVA SL
 * @license http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace georiesgosaragon\common;

abstract class model {
    /** @var string $_database_collection Database collection */
    protected $_database_collection = null;
    protected $_database_controller = null;

    /** @var string $_id item identificato in database */
    public $_id = null;

    /** @var \MongoDate $created_at creation date */
    public $created_at = null;

    /** @var \MongoDate $updated_at update date */
    public $updated_at = null;

    /** @var bool $active indicates if item is active or has been deleted */
    public $active = true;

    /**
     * Constructor. Crea el objeto seteando los atributos pasados por parametro
     *
     * @param string $_id Identificador del contenido
     * @param array $array_data Array con valores de los atributos del objeto
     * @param array $array_config Array de configuracion adicional (se pasa a metodos set(), etc...)
     * @param string $_database_collection Nombre de coleccion en base de datos (por defecto, 'files'). Solo se usa si $_database_controller se pasa por parametro.
     *
     * @throws \Exception
     */
    final public function __construct( $_id = '-1', $array_data = [], $array_config = [], $_database_controller = null, $_database_collection = '' ) {
        if ( !isset( $this->_database_collection ) || empty( $this->_database_collection ) ) {
            die( 'Database not found' );
        }

        if ( $_database_collection !== '' ) { // Controlador de base de datos e identificador de coleccion pasado por parametro (p.e. para tests)
            $this->_database_collection = $_database_collection;
        }
        $this->_database_controller = databasemongo::getDatabase();

        if ( (string) $_id !== '-1' && !empty( $_id ) ) {
            $this->_id = $_id;
        }
        if ( \is_array( $array_data ) && !empty( $array_data ) ) {
            $this->set( $array_data, true, $array_config );
        } elseif ( $this->_id !== null ) {
            $this->get( $array_config );
        } elseif ( method_exists( $this, 'initialize' ) ) {
            $this->initialize( $array_config );
        }
    }

    /**
     * Asigna valores pasados por array al objeto, para usarse en formularios que reciben datos por POST, etc.
     * IMPORTANTE: Al pasar por POST claves con punto en su identificador (p.e. calzadas.num_seccion) se convierten automaticamente en guiones bajos (p.e. calzadas_num_seccion)
     *
     * @param array $array_data Array con tuplas atributo => valor para setear en los atributos del objeto
     * @param bool $from_database Indica si el array de datos $array_data proviene directamente de base de datos MongoDB (true por defecto, es decir, es un array multidimensional) o bien se han enviado por POST (tienen la forma array( 'plataforma_total' => 29 ); en lugar de array( 'plataforma' => array( 'total' => 29 ) ); que indica que proviene de MongoDB). Por defecto, true (proviene de MongoDB)
     * @param array $array_config Array de configuracion adicional (por si se quieren pasar parametros para cargar datos de una u otra manera, etc...)
     */
    public function set( $array_data = [], $from_database = true, $array_config = [] ) {
        if ( \is_array( $array_data ) && !empty( $array_data ) ) {

            databasemongo::set_object_data( $this, $array_data );

            $this->object_encode_data( !$from_database );
        }
    }

    /**
     * Codifica atributos del objeto como corresponda (ISO-8859-1 / UTF-8 en strings), para poder ser almacenados de forma correcta en la base de datos o ser utilizada en el sistema correctamente
     *
     * @param bool $to_utf8 Indica si se convertira a UTF-8 los strings (true, cuando se tenga que almacenar en la base de datos) o si se convierten a ISO-8859-1 (false, cuando los datos proceden de la base de datos)
     */
    abstract public function object_encode_data( $to_utf8 = false );

    /**
     * Genera arrays con los atributos del objeto para hacer las diferentes consultas a la base de datos MongoDB,
     * en funcion del array de opciones $array_criteria.
     *
     * @param array $array_criteria Array con opciones para generar filtros de busqueda. Cada elemento tiene la forma array( 'atributo', 'comparador', 'valor', 'data_type' ). Ver \Controller_DataBase_MongoDB::get_array_criteria()
     *
     * @return array Array con tuplas clave => valor listas para ser utilizadas como filtros en las llamadas a los metodos de MongoDB
     * @uses \Controller_DataBase_MongoDB::get_array_criteria()
     */
    protected function get_query_filter( $array_criteria = [] ) {
        $criteria = [];

        foreach ( $array_criteria as $opt ) {
            [ $key, $modifier, $value, $data_type ] = $opt;

            if ( !( $modifier === 'gte' && empty( $value ) ) ) {
                // Por defecto... lo dejamos como estaba... filtrando los valores nulos (PK min, fecha min, etc...)
                $criteria[] = $opt;
            }
        }

        return databasemongo::get_array_criteria( $criteria );
    }

    /**
     * Carga datos en el objeto procedentes de un registro de la base de datos
     *
     * @param array $array_config Array de configuracion adicional, se pasa a set()
     *
     * @return bool Resultado de la operacion
     * @throws \Exception
     */
    public function get( $array_config = [] ) {
        if ( $this->_id !== null ) {
            $object = $this->_database_controller->select_by_id( $this->_database_collection, $this->_id );
            if ( $object !== null ) {
                $this->set( $object, true, $array_config );

                return $this->_id;
            }
            $this->_id = null;
        }

        return false;
    }

    /**
     * Devuelve un array de objetos con datos de registros de la base de datos correspondientes
     *
     * @param array $array_criteria Array con opciones de busqueda (p.e. $array_criteria[] = array( 'carreteraID', 'in', array( 12, 14, 23, 45 ) ); )
     * @param array $sort Array con criterios de ordenacion ( ASC = 1, DESC = -1 ). Ejemplo: array( 'date_issue' => -1, 'title' => 1 );
     * @param int $skip Numero de elementos que no se devolveran (similar a LIMIT (int) $limit, (int) $skip )
     * @param int $limit Numero maximo de elementos a devolver (similar a LIMIT $limit )
     * @param string $index_id Identificador de atributo por el que se indexaran los resultados (default: _id)
     * @param array $array_config Array de configuracion adicional de filtrado
     *          - debug (bool) Mostrar criterios de filtrado para debug {true|false (default)}
     *          - attributes (array) Array de identificadores de atributos a devolver de base de datos en formato [ attr1 => true, attr2 => true ]. Array vacio para todos atributos
     *          - objects (bool) Devolver objetos {true (default)} o hacer cast a array de los registros devueltos {false}
     *
     * @return Model[] de objetos de este tipo o bien array() vacio en caso de no existir resultados
     * @throws \Exception
     */
    public function get_all( $array_criteria = [], $sort = [], $skip = 0, $limit = 0, $index_id = '_id', $array_config = [ 'attributes' => [], 'objects' => true, 'debug' => false ] ) {

        $return_array = [];
        $criteria = $this->get_query_filter( $array_criteria );
        if ( !isset( $array_config[ 'attributes' ] ) ) {
            $array_config[ 'attributes' ] = [];
        }
        if ( !isset( $array_config[ 'objects' ] ) ) {
            $array_config[ 'objects' ] = true;
        }
        if ( !isset( $array_config[ 'debug' ] ) ) {
            $array_config[ 'debug' ] = false;
        }
        if ( $array_config[ 'debug' ] ) {
            var_dump( json_encode( $criteria ) );
        }
        $cursor = $this->_database_controller->select_by_criteria( $this->_database_collection, $criteria, $sort, (int) $limit, (int) $skip, $array_config[ 'attributes' ] );
        if ( !empty( $cursor ) ) {
            if ( empty( $index_id ) ) {
                if ( $array_config[ 'objects' ] ) {
                    foreach ( $cursor as $doc ) {
                        $return_array[] = new static( null, $doc, $array_config, $this->_database_controller, $this->_database_collection );
                    }
                } else {
                    foreach ( $cursor as $doc ) {
                        $return_array[] = utils::cast_object_to_array( new static( null, $doc, $array_config, $this->_database_controller, $this->_database_collection ) );
                    }
                }
            } elseif ( isset( $array_config[ 'objects' ] ) && $array_config[ 'objects' ] ) {
                foreach ( $cursor as $doc ) {
                    $return_array[ (string) $doc[ (string) $index_id ] ] = new static( null, $doc, $array_config, $this->_database_controller, $this->_database_collection );
                }
            } else {
                foreach ( $cursor as $doc ) {
                    $return_array[ (string) $doc[ (string) $index_id ] ] = utils::cast_object_to_array( new static( null, $doc, $array_config, $this->_database_controller, $this->_database_collection ) );
                }
            }
        }

        return $return_array;
    }

    /**
     * Devuelve un entero con el numero total de elementos publicados
     *
     * @param array $array_criteria Array con opciones de busqueda (p.e. $array_criteria[] = array( 'carreteraID', 'in', array( 12, 14, 23, 45 ) ); )
     * @param array $array_config Configuracion adicional
     *      - debug (bool) Muestra el array de criterios de filtrado generados para debug
     *
     * @return int Numero total de resultados
     * @throws \Exception
     */
    public function count_all( $array_criteria = [], $array_config = [] ) {
        $criteria = $this->get_query_filter( $array_criteria );

        $database = databasemongo::getDatabase();
        return (int) $database->count( $this->_database_collection, $criteria );
    }

    /**
     * Actualizar un objeto de la base de datos (si existe y tiene el mismo identificador que tenemos en nuestro identificador),
     * o bien lo registra si no existe, a partir de los datos de el objeto actual
     *
     * @param array $array_config Array de configuracion adicional para el store
     *
     * @return string Identificador del objeto actualizado, false en caso contrario
     */
    public function store() {
        $this->updated_at = gmdate( 'Y-m-d H:i:s' );
        if ( is_null( $this->_id ) ) {
            $this->created_at = $this->updated_at;
        }

        $this->object_encode_data( true );
        $database = databasemongo::getDatabase();
        $this->_id = $database->store_object( $this->_database_collection, $this );
        $this->ensureIndex();
        $this->object_encode_data( false );

        return $this->_id;
    }

    /**
     * Elimina un objeto de la base de datos, identificado por su _id, junto con todos ficheros asociados en filesystem
     * @return bool Resultado de la operacion
     * @throws \Exception
     */
    public function delete() {

        // Eliminamos de la base de datos. Realmente no se elimina, se desactiva, asi como los nodos hijos
        $array_criteria = [
            [ '_id', 'eq', $this->_id, 'MongoId' ],
        ];
        $object_attributes_to_update = [ 'active' => false ];
        $criteria = $this->get_query_filter( $array_criteria );
        return $this->_database_controller->update_by_criteria( $this->_database_collection, $criteria, $object_attributes_to_update, [ 'multiple' => true ], false );
    }

    /**
     * Permite asegurar los indices para esta coleccion
     * @return bool Resultado de la operacion
     */
    abstract protected function ensureIndex();

}
