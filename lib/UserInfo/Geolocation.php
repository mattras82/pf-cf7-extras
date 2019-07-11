<?php

namespace PublicFunction\Cf7Extras\UserInfo;


use PublicFunction\Cf7Extras\Plugin;

class Geolocation
{
	const ENDPOINT = 'geolocation_endpoint';
	const ENABLED = 'enable_geolocation_endpoint';
	const DEFAULT_ENDPOINT = 'https://ip.goldencomm.com/lookupJSON.aspx';

	/**
	 * @since   1.0.0
	 * @access   private
	 * @var      string
	 */
	private $url;

	/**
	 * @since   1.0.0
	 * @access   private
	 * @var bool
	 */
	private $enabled;

	/**
	 * @since   1.0.0
	 * @access   private
	 * @var      bool
	 */
	private $valid_json;

	/**
	 * @since   1.0.0
	 * @access   private
	 * @var      string
	 */
	private $response;

	/**
	 * @since   1.0.0
	 * @access   private
	 * @var      array
	 */
	private $info;

	/**
	 * Format with anchor tag
	 * @since   1.0.0
	 * @var bool
	 */
	private $html;

	/**
	 * Loads configuration settings for the Geolocation
	 * @since   1.0.0
	 * @param   bool $html
	 */
	public function __construct($html = false) {
		$config = Plugin::getInstance()->get('config');
		$this->url = $config->get_config(self::ENDPOINT);
		$this->enabled = $config->get_config(self::ENABLED);
		$this->html = $html;
	}

	/**
	 * @since   1.0.0
	 * @return  string
	 */
	public function get_location() {
		if ($this->enabled) {
			$this->call();
			$this->validate();
			return $this->format();
		}
		return $_SERVER['REMOTE_ADDR'];
	}

	/**
	 * Format our Geolocation
	 * @since   1.0.0
	 * @return  string
	 */
	private function format() {
		return $this->valid_json
			? $this->get_ip2location_link()
			: $this->get_ip();
	}

	/**
	 * Calls Geolocation API
	 * @since   1.0.0
	 * @expects json
	 */
	private function call() {
		$curl = curl_init();

		$data = [
			'ip' => $this->resolve_ip()
		];

		if (empty($this->url)) {
			$this->url = self::DEFAULT_ENDPOINT;
		}

		$this->url = $this->url . '?' . http_build_query($data);

		curl_setopt_array($curl, array(
			CURLOPT_URL => $this->url,
			CURLOPT_RETURNTRANSFER => true
		));

		$this->response = curl_exec($curl);

		curl_close($curl);
	}

	/**
	 * Validates a json string
	 * @since   1.0.0
	 */
	private function validate() {
		$result = json_decode($this->response, true);

		if (json_last_error() === JSON_ERROR_NONE) {
			$this->valid_json = true;
			$this->info = $result;
		} else {
			$this->valid_json = false;
		}
	}

	/**
	 * @since   1.0.0
	 * @return  string
	 */
	private function get_ip2location_link() {
		return $this->html
			? "<a href='" . $this->url . "'>" . $this->get_ip() . $this->get_isp() . "</a>"
			: $this->get_ip() . $this->get_isp();
	}

	/**
	 * @since   1.0.0
	 * @return  string
	 */
	private function get_ip() {
		return isset($this->info['ipinfo']['ipaddress'])
			? $this->info['ipinfo']['ipaddress']
			: $_SERVER['REMOTE_ADDR'];
	}

	/**
	 * @since   1.0.0
	 * @return  string
	 */
	private function get_isp() {
		return isset($this->info['ipinfo']['ispname'])
			? '(' . $this->info['ipinfo']['ispname'] . ')'
			: '';
	}

	/**
	 * @since   1.0.0
	 * @return mixed
	 */
	private function resolve_ip() {
		if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
			$_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
			$_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
		}
		$client  = @$_SERVER['HTTP_CLIENT_IP'];
		$forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
		$remote  = $_SERVER['REMOTE_ADDR'];

		if (filter_var($client, FILTER_VALIDATE_IP)) {
			$ip = $client;
		} elseif (filter_var($forward, FILTER_VALIDATE_IP)) {
			$ip = $forward;
		} else {
			$ip = $remote;
		}

		return $ip;
	}
}