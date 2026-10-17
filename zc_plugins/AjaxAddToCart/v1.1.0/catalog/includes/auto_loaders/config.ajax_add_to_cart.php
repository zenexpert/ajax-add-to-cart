<?php
/**
 * Autoloader for Ajax Add To Cart
 */
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

$autoLoadConfig[90][] = [
    'autoType' => 'class',
    'loadFile' => 'observers/class.ajax_add_to_cart_observer.php',
];

$autoLoadConfig[90][] = [
    'autoType' => 'classInstantiate',
    'className' => 'ajax_add_to_cart_observer',
    'objectName' => 'ajax_add_to_cart_observer'
];
