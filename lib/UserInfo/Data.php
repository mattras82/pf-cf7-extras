<?php
/**
 * Created by PhpStorm.
 * User: Matthew Rasmussen
 * Date: 5/28/2019
 * Time: 4:31 PM
 */

namespace PublicFunction\Cf7Extras\UserInfo;


use PublicFunction\Cf7Extras\Core\RunableAbstract;

class Data extends RunableAbstract
{
	const PPC_SESSION_NAME = 'pf_cf7_user_info_ppc';


	/**
	 * Saves the URI to the visited path session variable.
	 * @since   1.0.0
	 */
	public function save_uri_in_session() {
		global $wp_the_query;

		$blacklist = array('/offline');
		$process = true;

		foreach ($blacklist as $word) {
			if (strpos($_SERVER['REQUEST_URI'], $word) === 0) {
				$process = false;
			}
		}

		if ($process) {
			if (!isset($_SESSION['VISITED_PATH'])) {
				$_SESSION['VISITED_PATH'] = '';
			}

			if (!$wp_the_query->is_404 && $this->uri_exists()) {
				$_SESSION['VISITED_PATH'] .= PHP_EOL . $_SERVER['REQUEST_URI'];
			}
		}
	}

	/**
	 * @since   1.0.0
	 */
	public function save_referrer_in_session() {
		if (!isset($_SESSION['HTTP_REFERER']) && isset($_SERVER['HTTP_REFERER'])) {
			$_SESSION['HTTP_REFERER'] = $_SERVER['HTTP_REFERER'];
		}
	}

	/**
	 * @since   1.0.0
	 */
	public function save_ppc_value_in_session() {
		$ppc_keyword = $this->get('config')->get_config('ppc_keyword');

		if ($ppc_keyword
			&& isset($_GET[$ppc_keyword])
			&& !isset($_SESSION[self::PPC_SESSION_NAME])
		) {
			$_SESSION[self::PPC_SESSION_NAME] = $_GET[$ppc_keyword];
		}
	}

	/**
	 * Transforms posted data
	 * @since   1.0.0
	 * @param $form
	 * @return mixed
	 */
	public function alter_db_save_data($form) {
		$form->posted_data['user_path'] = $this->get_visited_path();
		$form->posted_data['user_ip'] = $this->get_geolocation();
		return $form;
	}

	/**
	 * @since   1.0.0
	 * @return  string
	 */
	public function get_referrer() {
		$host = $_SERVER['HTTP_HOST'];
		$referrer = parse_url($_SESSION['HTTP_REFERER'], PHP_URL_HOST);

		return $host === $referrer
			? ''
			: $_SESSION['HTTP_REFERER'];
	}

	/**
	 * @since   1.0.0
	 * @return  string|null
	 */
	public function get_ppc_value() {
		return isset($_SESSION[self::PPC_SESSION_NAME]) ? $_SESSION[self::PPC_SESSION_NAME] : null;
	}

	/**
	 * @since   1.0.0
	 * @param   bool $html
	 * @return  string
	 */
	public function get_geolocation($html = false) {
		return (new Geolocation($html))->get_location();
	}

	/**
	 * @since   1.0.0
	 * @return  string
	 */
	public function get_visited_path() {
		return isset($_SESSION['VISITED_PATH']) ? $_SESSION['VISITED_PATH'] : 'Visited path is not set';
	}

	/**
	 * @since   1.0.0
	 * Converts visited path into html block for email.
	 * @return  string
	 */
	public function get_user_path_html() {
		$elements = array();
		$path = $this->get_visited_path();

		if ($path === 'Visited path is not set') {
			return $path;
		}

		$paths = explode(PHP_EOL, $path);

		foreach ($paths as $path) {
			$elements[] = $this->visitor_path_link($path);
		}

		return join('<br>', $elements);
	}

	/**
	 * @since   1.0.0
	 * @return  mixed
	 */
	public function uri_exists() {
		return !empty($_SERVER['REQUEST_URI']);
	}

	/**
	 * @since   1.0.0
	 * @param   string $uri
	 * @return  null|string
	 */
	public function visitor_path_link($uri) {
		return '<a href="' . $this->build_url($uri) . '">' . $uri . '</a>';
	}

	/**
	 * @since   1.0.0
	 * @param   string $uri
	 * @param   string $query
	 * @return  string
	 */
	public function build_url($uri = '', $query = '') {
		$protocol = $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
		$host = $_SERVER['HTTP_HOST'];

		return $protocol . $host . $uri . $query;
	}

	/**
	 * Initiates browser session
	 * @since   1.0.0
	 */
	public function wpse_session_start() {
		if (!session_id()) {
			session_start();
		}
	}

	public function run()
	{

		$this->loader()->addAction('plugins_loaded', [$this, 'wpse_session_start']);
		$this->loader()->addAction('parse_request', [$this, 'save_referrer_in_session']);
		$this->loader()->addAction('parse_request', [$this, 'save_ppc_value_in_session']);
		$this->loader()->addAction('wp_head', [$this, 'save_uri_in_session']);
	}

}