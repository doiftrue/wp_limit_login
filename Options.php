<?php

namespace KamaLimitLogin;

class Options {

	/**
	 * @var string Cache folder path where data will be stored.
	 */
	public $cache_dir;

	/**
	 * @var int Time to release the lock. Eg: 3600.
	 */
	public $lock_sec;

	/**
	 * @var int Number of attempts to authorise. Eg: 3.
	 */
	public $lock_num;

	/**
	 * @var int Time to completely clear the file. Eg: 3600 * 24 * 4.
	 */
	public $expire_sec;

	/**
	 * @var string Lock message. Eg: 'Вы исчерпали %d попытки. Приходите через час.'.
	 */
	public $massage;

	/**
	 * @var bool Should we use a soft (insecure) method of obtaining IP?
	 */
	public $soft_get_ip;

	/**
	 * @var bool Add REQUEST_URI query to file names - may be useful for debugging.
	 */
	public $add_uri;

	public function __construct( $opt ) {
		$this->cache_dir   = $opt['cache_dir'];
		$this->lock_sec    = (int) $opt['lock_sec'];
		$this->lock_num    = (int) $opt['lock_num'];
		$this->expire_sec  = (int) $opt['expire_seconds'];
		$this->massage     = $opt['massage'];
		$this->soft_get_ip = (bool) $opt['soft_get_ip'];
		$this->add_uri     = (bool) $opt['add_uri'];
	}
}
