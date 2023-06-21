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
 * Auxiliary functions
 * @author ITERNOVA (info@iternova.net)
 * @version 1.0.0 - 20230612
 * @package common
 * @copyright 2023 ITERNOVA SL
 * @license http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace georiesgosaragon\common;

class utils {
    /**
     * Convert this GoogleMaps_Modules_Utils_GeoLatLng object from OSGB36 datum to WGS84 datum.
     */
    public static function OSGB36ToWGS84( $north, $east, $utmZone = 30 ) {
        // This is the lambda knot value in the reference
        $LngOrigin = Deg2Rad( $utmZone * 6 - 183 );

        // The following set of class constants define characteristics of the
        // ellipsoid, as defined my the WGS84 datum.  These values need to be
        // changed if a different dataum is used.

        $FalseNorth = 0;   // South or North?
        //if (lat < 0.) FalseNorth = 10000000.  // South or North?
        //else          FalseNorth = 0.

        $Ecc = 0.081819190842622;       // Eccentricity
        $EccSq = $Ecc * $Ecc;
        $Ecc2Sq = $EccSq / ( 1. - $EccSq );
        $Ecc2 = sqrt( $Ecc2Sq );      // Secondary eccentricity
        $E1 = ( 1 - sqrt( 1 - $EccSq ) ) / ( 1 + sqrt( 1 - $EccSq ) );
        $E12 = $E1 * $E1;
        $E13 = $E12 * $E1;
        $E14 = $E13 * $E1;

        $SemiMajor = 6378137.0;         // Ellipsoidal semi-major axis (Meters)
        $FalseEast = 500000.0;          // UTM East bias (Meters)
        $ScaleFactor = 0.9996;          // Scale at natural origin

        // Calculate the Cassini projection parameters

        $M1 = ( $north - $FalseNorth ) / $ScaleFactor;
        $Mu1 = $M1 / ( $SemiMajor * ( 1 - $EccSq / 4.0 - 3.0 * $EccSq * $EccSq / 64.0 - 5.0 * $EccSq * $EccSq * $EccSq / 256.0 ) );

        $Phi1 = $Mu1 + ( 3.0 * $E1 / 2.0 - 27.0 * $E13 / 32.0 ) * sin( 2.0 * $Mu1 );
        +( 21.0 * $E12 / 16.0 - 55.0 * $E14 / 32.0 ) * sin( 4.0 * $Mu1 );
        +( 151.0 * $E13 / 96.0 ) * sin( 6.0 * $Mu1 );
        +( 1097.0 * $E14 / 512.0 ) * sin( 8.0 * $Mu1 );

        $sin2phi1 = sin( $Phi1 ) * sin( $Phi1 );
        $Rho1 = ( $SemiMajor * ( 1.0 - $EccSq ) ) / pow( 1.0 - $EccSq * $sin2phi1, 1.5 );
        $Nu1 = $SemiMajor / sqrt( 1.0 - $EccSq * $sin2phi1 );

        // Compute parameters as defined in the POSC specification.  T, C and D

        $T1 = tan( $Phi1 ) * tan( $Phi1 );
        $T12 = $T1 * $T1;
        $C1 = $Ecc2Sq * cos( $Phi1 ) * cos( $Phi1 );
        $C12 = $C1 * $C1;
        $D = ( $east - $FalseEast ) / ( $ScaleFactor * $Nu1 );
        $D2 = $D * $D;
        $D3 = $D2 * $D;
        $D4 = $D3 * $D;
        $D5 = $D4 * $D;
        $D6 = $D5 * $D;

        // Compute the Latitude and Longitude and convert to degrees
        $lat = $Phi1 - $Nu1 * tan( $Phi1 ) / $Rho1 * ( $D2 / 2.0 - ( 5.0 + 3.0 * $T1 + 10.0 * $C1 - 4.0 * $C12 - 9.0 * $Ecc2Sq ) * $D4 / 24.0 + ( 61.0 + 90.0 * $T1 + 298.0 * $C1 + 45.0 * $T12 - 252.0 * $Ecc2Sq - 3.0 * $C12 ) * $D6 / 720.0 );

        $lat = Rad2Deg( $lat );

        $lon = $LngOrigin + ( $D - ( 1.0 + 2.0 * $T1 + $C1 ) * $D3 / 6.0 + ( 5.0 - 2.0 * $C1 + 28.0 * $T1 - 3.0 * $C12 + 8.0 * $Ecc2Sq + 24.0 * $T12 ) * $D5 / 120.0 ) / cos( $Phi1 );

        $lon = Rad2Deg( $lon );

        // Returns a PC_LatLon object
        return [ $lat, $lon ];
    }

    /**
     * Returns given value in UTF8 codification
     *
     * @param string|array $value
     *
     * @return string|array
     */
    public static function detect_utf8( $value ) {
        if ( is_array( $value ) ) {
            foreach ( $value as $key => $data ) {
                $value[ $key ] = self::detect_utf8( $data );
            }
        } else {
            $value = $value === null ? null : ( (string) ( mb_detect_encoding( $value, 'UTF-8', true ) ? $value : utf8_encode( $value ) ) );
        }
        return $value;
    }

    /**
     * Returns given value codified in ISO-8859
     *
     * @param string|array|object $value
     *
     * @return string|array
     */
    public static function detect_iso8859_1( $value ) {
        if ( is_array( $value ) ) {
            foreach ( $value as $key => $data ) {
                $value[ $key ] = self::detect_iso8859_1( $data );
            }
        } else {
            if ( is_object( $value ) ) {
                return $value;
            }
            $value = (string) ( mb_detect_encoding( (string) $value, 'UTF-8', true ) ? utf8_decode( (string) $value ) : $value );
        }
        return $value;
    }

    /**
     * Convierte un objeto (con objetos recursivos...) en un array
     *
     * @param mixed $object Objeto a convertir
     * @param array $array_skipped_classes Array de nombres de clases que no se van a formatear en array. Por defecto, ninguna (p.e. MongoDate para almacenar fechas en MongoDB)
     *
     * @return array Array representativo del objeto
     */
    public static function cast_object_to_array( $object, $array_skipped_classes = [] ) {
        $return = [];
        if ( !empty( $object ) ) {
            if ( empty( $array_skipped_classes ) ) { // MongoDB\BSON\UTCDateTime da problemas (atributos updated_at)
                $array_skipped_classes[] = 'MongoDB\BSON\ObjectID';
                $array_skipped_classes[] = 'MongoDB\BSON\UTCDateTime';
                $array_skipped_classes[] = '\DataBase_MongoDB_Controller';
            }

            foreach ( $object as $key => $value ) {
                $is_object = \is_object( $value );
                $is_array = \is_array( $value );
                if ( $is_object && !empty( $array_skipped_classes ) ) {
                    $value_class = \get_class( $value );
                    if ( $value_class && \in_array( $value_class, $array_skipped_classes, true ) ) {
                        // Se ignora el cast, es objeto de clase a skippear
                        $return[ $key ] = $value;
                        continue;
                    }
                }

                if ( $is_object || $is_array ) { // Si es objeto o array... recursivamente lo convertiremos a array
                    $return[ $key ] = self::cast_object_to_array( $value, $array_skipped_classes );
                } else { // No se castea... es valor u objeto a no castear
                    $return[ $key ] = $value;
                }
            }
        }

        return $return;
    }

    /**
     * Elimina claves de un array (incluidas claves recursivas del array)
     *
     * @param array $haystackarray Array original, con claves. Por ejemplo, array( 'carreteraID' => 17, 'carretera' => 'A-23', 'datos' => array( 'longitud' => 28.4, 'fecha' => '2012-02-12' ) );
     * @param array $array_needle Array cuyas claves indican que claves del array $haystackarray seran eliminadas.  Por ejemplo, array( 'carretera' => true, 'datos' => array( 'fecha' => true ) );
     *
     * @return array Array $haystackarray sin las claves pasadas en $array_needle. Por ejemplo: array( 'carreteraID' => 17, 'datos' => array( 'longitud' => 28.4 ) );
     */
    public static function array_diff_key_recursive( $haystackarray, $array_needle ) {
        if ( !empty( $array_needle ) && !empty( $haystackarray ) ) {
            foreach ( $array_needle as $key => $value ) {
                $isset_key = isset( $haystackarray[ $key ] );
                if ( \is_array( $value ) ) {
                    if ( $isset_key ) {
                        $haystackarray[ $key ] = self::array_diff_key_recursive( $haystackarray[ $key ], $value );
                    }
                } elseif ( $isset_key ) {
                    unset( $haystackarray[ $key ] );
                }
            }
        }

        return $haystackarray;
    }

    /**
     * Returns current server url to compose links
     * @return string
     */
    public static function get_server_url() {
        $str_return = ( $_SERVER[ 'HTTPS' ] === 'on' ) ? 'https://' : 'http://';
        $str_return .= $_SERVER[ 'HTTP_HOST' ];
        return $str_return;
    }

    /**
     * Initializa array opts from default opts
     * @param $array_config
     * @param $array_config_default_values
     *
     * @return array
     */
    public static function initialize_array_config( $array_config, $array_config_default_values = [] ) {
        if ( !is_array( $array_config ) ) {
            $array_config = [];
        }
        if ( empty( $array_config_default_values ) ) {
            return $array_config;
        }
        foreach ( $array_config_default_values as $key => $default_value ) {
            if ( !isset( $array_config[ $key ] ) ) {
                $array_config[ $key ] = $default_value;
            }
        }

        return $array_config;
    }
}
