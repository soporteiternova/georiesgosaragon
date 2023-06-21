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
 * MongoDB database connection functions
 * @author ITERNOVA (info@iternova.net)
 * @version 1.0.0 - 20230612
 * @package common
 * @copyright 2023 ITERNOVA SL
 * @license http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace georiesgosaragon\common;

class databasemongoconnection {

    public $id; // database id
    public $servers = []; // server array
    public $user; // database user
    public $password; // database password
    public $database; // database name
    public $replicaset = ''; // replicaset id
    public $additional = []; // additional connection configuration
    private $_link = false; // MongoDB connection object
    private $_database_selected = false; //selected database object

    /**
     * Connection configuration
     *
     * @param string $id database id
     * @param array $servers server array {host:port}
     * @param string $user database user
     * @param string $password database password
     * @param string $database databasde name
     * @param string $replicaset replicaset id
     * @param array $additional connecton additional data
     *
     * @return void
     */

    public function __construct( $id, $servers, $user, $password, $database, $replicaset = '', $additional = [] ) {
        $this->id = $id;
        $this->servers = $servers;
        $this->user = $user;
        $this->password = $password;
        $this->database = $database;
        $this->replicaset = $replicaset;
        $this->additional = $additional;
    }

    /**
     * Database connect function
     * @return \MongoDB\Database
     */
    public function connect() {
        try {
            $this->password = trim( $this->password );
            if ( $this->_link === false ) {
                $str_connection = 'mongodb://' . $this->user . ':' . $this->password . '@' . implode( ',', $this->servers ) . '/' . $this->database . '?ssl=false';
                $this->_link = new \MongoDB\Client( $str_connection, [ 'connectTimeoutMS' => 30000 ], [ 'typeMap' => [ 'root' => 'array', 'document' => 'array', 'array' => 'array' ] ] );
            }

            if ( $this->_database_selected == false ) {
                $this->_database_selected = $this->_link->selectDatabase( $this->database ); // Conexion a la base de datos
            }
        } catch ( \MongoDB\Exception\Exception $e ) {
            print_r( 'ERROR MongoDB: Error connecting to MongoDB server: ' . $e->getMessage() );
            die( 'CONNECTION_SERVER_OPEN' );
        } catch ( \Exception $e ) {
            print_r( 'ERROR MongoDB (connect - Unknown exception): ' . $e->getMessage() );
            die( 'CONNECTION_OPEN_EXCEPTION' );
        }

        return $this->_database_selected;
    }

    /**
     * Database close connection function
     * @return mixed Resultado de la operacion
     */
    public function close_connection() {
        if ( $this->_database_selected !== false ) {
            try {
                return true;
            } catch ( \MongoDB\Exception\Exception $e ) {
                print_r( 'ERROR MongoDB: Error closing connecting to MongoDB server (close_connection): ' . $e->getMessage() );
                die( 'CONNECTION_SERVER_CLOSE' );
            }
        }

        return true;
    }

}
