<?php

/*
 *
 * @version 4.0
 * @requires_php 7.0
 */

namespace KamaLimitLogin;

if( ! need_check() ){
	return;
}

require_once __DIR__ . '/Options.php';
require_once __DIR__ . '/Checker.php';

$options = new Options( [
	'cache_dir'      => __DIR__ . '/cache/',
	'lock_sec'       => 3600,
	'lock_num'       => 3,
	'massage'        => 'Вы исчерпали %d попытки. Приходите через час.',
	'expire_seconds' => 3600 * 24 * 4,
	'soft_get_ip'    => false,
	'add_uri'        => false,
] );

try{
	$worker = new Checker( $options );
	$worker->init();
}
catch( \Exception $ex ){
	die( $ex->getMessage() );
}

function need_check(): bool {

	if( 'POST' !== $_SERVER['REQUEST_METHOD'] ){
		return false;
	}

	// For '/wp-login.php'
	if( false !== strpos( $_SERVER['REQUEST_URI'], '/wp-login.php' ) ){
		return true;
	}

//	// Custom profile
//	if(
//		( ! empty( $_POST['userdata'] ) && empty( $_POST['pwd'] ) )
//	    &&
//		false !== strpos( $_SERVER['REQUEST_URI'], '/profile' )
//	){
//		return true;
//	}

	return false;
}
