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
 * Basic actions controller for the app
 * @author ITERNOVA (info@iternova.net)
 * @version 1.0.0 - 20230612
 * @package common
 * @copyright 2023 ITERNOVA SL
 * @license http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace georiesgosaragon\common;

class controller {

    const ENDPOINT_DESLIZAMIENTOS = 1;
    const ENDPOINT_INUNDACIONES = 2;
    const ENDPOINT_COLAPSOS = 3;

    /**
     * Funcion para mostrar la cabecera html
     *
     * @param boolean $echo Lo muestra por pantalla si true
     * @param boolean $script Incluye scripts
     */
    public static function show_html_header( $echo = true, $script = true ) {
        /*
         *
            <script language="javascript" type="text/javascript">

                window.onload = function() {
                    var s1 = document.createElement("script");
                    s1.type = "text/javascript";
                    s1.src = "libs/js/georiesgos.js";
                    document.getElementByTagName("head")[0].appendChild(s1);
                }

            </script>
         */
        $str = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
		<!--
			Twenty by HTML5 UP
			html5up.net | @ajlkn
			Free for personal and commercial use under the CCA 3.0 license (html5up.net/license)
		-->
		<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="es">
		<head>
		    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
			<title>GeoRiesgos Arag&oacute;n</title>
			<meta charset="utf-8" />
			<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
			<link rel="stylesheet" href="css/main.css" />
			<noscript><link rel="stylesheet" href="css/noscript.css" /></noscript>			
			<link rel="shortcut icon" href="img/favicon.ico">
        
            <!-- Scripts -->
            <script src="libs/js/jquery.min.js"></script>
            <script src="libs/js/jquery-ui/jquery-ui.js"></script>
            <!-- DATATABLES -->
            <link href="//cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css" rel="stylesheet">
            <script src="libs/js/jquery.dataTables.min.js"></script>
            <script src="/libs/js/georiesgos.js"></script>
		</head>';

        if ( $echo ) {
            echo $str;
        }

        return $str;
    }

    /**
     * Funcion para mostrar el pie html
     */
    public static function show_html_footer( $echo = true ) {
        $str = '<!-- Footer -->
                        <footer id="footer">
                            <ul class="icons">
                                <li><a href="https://twitter.com/tecnocarreteras" target="_blank" class="icon brands circle fa-twitter"><span class="label">Twitter</span></a></li>
                                <li><a href="https://facebook.com/tecnocarreteras" target="_blank" class="icon brands circle fa-facebook-f"><span class="label">Facebook</span></a></li>
                                <li><a href="https://github.com/soporteiternova/georiesgosaragon" target="_blank" class="icon brands circle fa-github"><span class="label">Github</span></a></li>
                                <li><a href="https://aragon.es/" target="_blank"><img src="img/logo_gobierno_aragon.png" alt="Gobierno de Arag&oacute;n" style="width:10%; margin-top:40px;"/></a></li>
                            </ul>
                            <ul class="copyright">
                                <li>Aplicaci&oacute;n subvencionada por el Gobierno de Arag&oacute;n - &copy; ' . date( 'Y' ) . ' <a href="https://www.iternova.net/" target="_blank">ITERNOVA</a></li>
                            </ul>
                        </footer>
                </div>
            <script src="libs/js/jquery.dropotron.min.js"></script>
            <script src="libs/js/jquery.scrolly.min.js"></script>
            <script src="libs/js/jquery.scrollgress.min.js"></script>
            <script src="libs/js/jquery.scrollex.min.js"></script>
            <script src="libs/js/browser.min.js"></script>
            <script src="libs/js/breakpoints.min.js"></script>
            <script src="libs/js/util.js"></script>
            <script src="libs/js/main.js"></script>
        
            </body>
        </html>';

        if ( $echo ) {
            echo $str;
        }
        return $str;
    }

    /**
     * Funcion para mostrar el cuerpo de la pagina
     */
    public static function show_html_body() {
        $zone = self::get( 'zone' );
        $class_start = '';
        $class_about = '';
        $class_routes = '';

        switch ( $zone ) {
            case 'about':
                $class_about = 'current';
                break;
            default:
                $class_start = 'current';
                break;
        }
        $str = '<body class="no-sidebar is-preload">
            <div id="page-wrapper">
    
                <!-- Header -->
                <header id="header">
                    <h1 id="logo"><a href="index.php">GeoRiesgos <span>Arag&oacute;n</span></a></h1>
                    <nav id="nav">
                        <ul>
                            <li class="' . $class_start . '"><a href="index.php">Inicio</a></li>
                            <li class="' . $class_about . '"><a href="?&amp;zone=about&amp;action=about">Sobre GeoRiesgos Arag&oacute;n</a></li>
                        </ul>
                    </nav>
                </header>
                
                <!-- Main -->
                <article id="main">

                    <header class="special container">
                        <span class="icon solid fa-car-crash"></span>
                        <h2>GeoRiesgos <b>Arag&oacute;n </b ></h2 >
                        <h2>ATENCI&Oacute;N: APLICACI&Oacute;N EN DESARROLLO</h2>
                        <p>Toda la informaci&oacute;n en tiempo real sobre riesgos geogr&aacute;ficos en Arag&oacute;n, que puedan afectar a las infrastructuras principales de carreteras y ferrocarriles.</p>
                    </header >

                    <!--One -->
                    <section class="wrapper style4 container">

                        <!--Content -->
                            <div class="content">';

        $controller = new self();
        switch ( controller::get( 'zone' ) ) {
            case 'about':
                $str .= $controller->about();
                break;
            case 'crondaemon':
                $controller->crondaemon(true);
                break;
            default:
                $str .= $controller->main();
        }
        $str .= '        </div >

                    </section >
                </article>';

        echo $str;
    }

    /**
     * Funcion para obtener datos de $_GET
     *
     * @param String $key Clave que queremos obtener
     */
    public static function get( $key ) {
        $return = '';
        if ( isset( $_GET[ $key ] ) ) {
            $return = trim( $_GET[ $key ] );
        }
        return $return;
    }

    /**
     * Funcion para obtener datos de $_POST
     */
    public static function post( $key ) {
        $return = '';
        if ( isset( $_POST[ $key ] ) ) {
            $return = trim( $_POST[ $key ] );
        }
        return $return;
    }

    /**
     * Proporciona la api key de google asociada al dominio
     */
    public static function google_key() {
        return file_get_contents( __DIR__ . '/../config/googlemaps.key' ); // Local
    }

    public static function get_endpoint_url( $endpoint ) {
        $url = '';
        switch ( $endpoint ) {
            case self::ENDPOINT_DESLIZAMIENTOS:
                $url = 'https://opendata.aragon.es/GA_OD_Core/download?view_id=208&formato=json&_pageSize=10000&_page=1';
                break;
            case self::ENDPOINT_INUNDACIONES:
                $url = 'https://opendata.aragon.es/GA_OD_Core/download?resource_id=211&formato=json';
                break;
            case self::ENDPOINT_COLAPSOS:
                $url = 'https://opendata.aragon.es/GA_OD_Core/download?resource_id=212&formato=json';
                break;
        }
        return $url;
    }

    /**
     * Executes cron functions to load data from OpenData API
     * @return bool
     */
    public function crondaemon($debug = false) {
        $controller = new \georiesgosaragon\deslizamientos\controller();
        $ret = $controller->actions( 'crondaemon', $debug );

        //$controller = new \georiesgosaragon\inundaciones\controller();
        //$ret &= $controller->actions( 'crondaemon' );

        return $ret;
    }

    public function about(){
        $str = '<h2>Sobre GeoRiesgos Arag&oacute;n</h2>';
        $str.= '<b>GeoRiesgos</b> Arag&oacute;n es una aplicaci&oacute;n multiplataforma, que mediante el uso de datos abiertos, procedentes de los repositorios OpenData del Gobierno de Arag&oacute;n y del Instituo Geogr&aacute;fico Nacional, muestra al ciudadano riesgos geogr&aacute;ficos en tiempo real, que puedan tener especial incidencia en carreteras y ferrocarriles.';

        $str .= '<br/><br/><p>Este es un programa inform&aacute;tico de software libre denominado "GeoRiesgos Arag&oacute;n" que forma parte de la subvenci&oacute;n de software libre, seg&uacute;n ORDEN CUS/166/2023, de 15 de febrero, por la que se convocan subvenciones de apoyo al software libre dirigidas a microempresas y a trabajadores aut&oacute;nomos.</p>';
        return $str;
    }

    private function main() {
        $url_glides = utils::get_server_url() . '?zone=deslizamientos&action=show_in_map&js=true';
        $str = '<script src="/libs/js/georiesgos.js"></script><div class="row">
                    <div class="col-5" style="border:1px solid #000000;padding:4px;margin:1.75em;"><b>Selecci&oacute;n de infraestructuras:</b><br/>
                        <label><input type="checkbox" id="roads_checkbox" name="roads" checked="checked"/>Carreteras</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        <label><input type="checkbox" id="railways_checkbox" name="railways" checked="checked"/>Ferrocarril</label>
                    </div>
                    <div class="col-5" style="border:1px solid #000000;padding:4px;margin:1.75em;"><b>Selecci&oacute;n de riesgos a visualizar:</b><br/>
                        <div class="row"><div class="col-5">
                            <label><button onclick="show_json_layer(\'' . $url_glides . '\',\'glides\');">Ver deslizamientos</button></label><br/>
                            <label><button>Ver inundaciones</button></label><br/>
                            <label><button>Ver colapsos</button></label><br/>
                            <label><button>Limpiar</button></label><br/>
                            </div><div class="col-5">
                            <label>Fecha inicial<input class="col-5" type="text" id="dateMIN" name="dateMIN" value="' . date('d/m/Y') . '"/></label>
                            <label>Fecha final<input type="text" id="dateMAX" name="dateMAX" value="' . date('d/m/Y') . '"/></label>
                        </div></div>
                    </div>
                </div>';
        $str.= map::create_map([], 600, 400, false);
        return $str;
    }
}
