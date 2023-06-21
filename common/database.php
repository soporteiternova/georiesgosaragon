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
 * Database controller
 * @author ITERNOVA (info@iternova.net)
 * @version 1.0.0 - 20230612
 * @package common
 * @copyright 2023 ITERNOVA SL
 * @license http://www.gnu.org/licenses/gpl-3.0.html
 */
 namespace georiesgosaragon\common;

 class database {
 	private $database = '';

 	/**
 	 * Constructor de la clase
 	 */
 	function __construct() {
	 	$this->database = mysqli_connect('localhost', 'zgzagua', 'zgzagua', 'zgzagua');
	 	mysqli_select_db($this->database, 'zgzagua');
 	}

 	/**
 	 * Funcion para realizar consulta sobre la base de datos
 	 * @param String $sql Consulta a realizar
 	 */
 	 function query( $sql) {
 	 	mysqli_query( $this->database, 'set names"utf8"');
 	 	return mysqli_query( $this->database,$sql);
 	 }
 }
