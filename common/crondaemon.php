<?php

require __DIR__ . '/..//libs/composer/vendor/autoload.php';

$crondaemon = new \georiesgosaragon\common\controller();
print_r( $crondaemon->crondaemon() );
