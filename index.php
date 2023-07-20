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
 * Acces file to the app
 * @author ITERNOVA (info@iternova.net)
 * @version 1.0.0 - 20230612
 * @copyright 2023 ITERNOVA SL
 * @license http://www.gnu.org/licenses/gpl-3.0.html
 */

declare( strict_types=0 );

// Includes
require __DIR__ . '/libs/composer/vendor/autoload.php';

session_start();

// Index
$zone = \georiesgosaragon\common\controller::get('zone');
$js = \georiesgosaragon\common\controller::get('js');
if( $zone === 'map' ) {
    $controller = new \georiesgosaragon\common\map();
    echo $controller->actions();
} elseif( $js === 'true' ){
    if( $zone === 'deslizamientos' ){
        $controller = new \georiesgosaragon\deslizamientos\controller();
        echo $controller->actions();
    } elseif( $zone === 'inundaciones' ){
        $controller = new \georiesgosaragon\inundaciones\controller();
        echo $controller->actions();
    }
} else {
    \georiesgosaragon\common\controller::show_html_header();
    \georiesgosaragon\common\controller::show_html_body();
    \georiesgosaragon\common\controller::show_html_footer();
}
