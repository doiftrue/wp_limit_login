<?php

namespace KamaLimitLogin;

class Checker {

	/** @var Options */
	public $opt;

	/** @var string */
	private $ip;

	/** @var string */
	private $data_file;

	/** @var array */
	private $data;

	public function __construct( Options $options ) {
		$this->opt = $options;
		$this->set_client_ip();
	}

	/**
	 * @throws \RuntimeException
	 */
	public function init() {

		if( ! $this->ip ){
			throw new \RuntimeException( 'No IP.' );
		}

		$this->set_data_file();
		$this->data = @ parse_ini_file( $this->data_file ) ?: [];

		$this->check_create_cache_dir();
		$this->check();
		$this->clear_file_on_expire();
	}

	private function set_data_file() {

		$action = $_GET['action'] ?? '';
		$allowed_actions = [
			'postpass',
			'logout',
			'lostpassword',
			'retrievepassword',
			'resetpass',
			'rp',
			'register',
			'login',
		];

		$action = in_array( $action, $allowed_actions, true ) ? $action : 'login';

		$host = str_replace( 'www.', '', $_SERVER['HTTP_HOST'] );
		$host = preg_replace( '/[^a-zA-Z0-9._-]/', '-', $host );

		$uri = '';
		if( $this->opt->add_uri ){
			$uri =  preg_replace( '/[^a-zA-Z0-9:=?_-]/', '-', $_SERVER['REQUEST_URI'] );
		}

		$this->data_file = "{$this->opt->cache_dir}/{$host}___{$action}{$uri}.ini";
	}

	private function check_create_cache_dir() {
		file_exists( $this->opt->cache_dir ) || mkdir( $this->opt->cache_dir, 0777 ) || is_dir( $this->opt->cache_dir );
	}

	/**
	 * @throws \RuntimeException
	 */
	private function check() {
		$ip = $this->ip;

		// Попыток нет или они не исчерпаны. Добавляем еще одну запись в лог.
		if(
			empty( $this->data[ $ip ] )
			||
			count( $this->data[ $ip ] ) < $this->opt->lock_num
		){
			$append_line = sprintf( "{$ip}[] = %d\n", time() );
			file_put_contents( $this->data_file, $append_line, FILE_APPEND );
		}
		// Попытки есть. Проверим что время не просрочено.
		else{
			// блокируем
			$last_access_time = max( $this->data[ $ip ] );
			if( ( $last_access_time + $this->opt->lock_sec ) > time() ){
				$msg = sprintf( $this->opt->massage, $this->opt->lock_num );
				throw new \RuntimeException( $msg );
			}

			// снимаем блокировку - удаляем строки
			$content = file_get_contents( $this->data_file );
			$new_content = preg_replace( "~$ip\[\] = [0-9]+\n~", '', $content );
			file_put_contents( $this->data_file, $new_content );
		}

	}

	private function clear_file_on_expire() {
		$expire_content = 'expire = ' . time() . "\n";

		if( empty( $this->data['expire'] ) ){
			file_put_contents( $this->data_file, $expire_content, FILE_APPEND );
		}
		elseif( $this->data['expire'] + $this->opt->expire_sec < time() ){
			file_put_contents( $this->data_file, $expire_content );
		}
	}

	public function set_client_ip() {
		$this->ip = $this->opt->soft_get_ip
			? $this->get_unsafe_ip()
			: ( $_SERVER['REMOTE_ADDR'] ?? '' );
	}

	private function get_unsafe_ip() {
		$client_ip = false;

		// In order of preference, with the best ones for this purpose first.
		$address_headers = [
			'HTTP_CF_CONNECTING_IP', // cloudflare
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		];

		foreach( $address_headers as $header ){
			if( array_key_exists( $header, $_SERVER ) ){
				/*
				 * HTTP_X_FORWARDED_FOR can contain a chain of comma-separated
				 * addresses. The first one is the original client. It can't be
				 * trusted for authenticity, but we don't need to for this purpose.
				 */
				$address_chain = explode( ',', $_SERVER[ $header ] );
				$client_ip = trim( $address_chain[0] );

				break;
			}
		}

		if(
			! $client_ip || '0.0.0.0' === $client_ip || '::' === $client_ip
			||
			false === filter_var( $client_ip, FILTER_VALIDATE_IP )
		){
			return false;
		}

		return $client_ip;
	}

}
