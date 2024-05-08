<?php

namespace PublicFunction\Cf7Extras\UserInfo;


use PublicFunction\Cf7Extras\Core\RunableAbstract;

class Data extends RunableAbstract
{
	const PPC_SESSION_NAME = 'pf_cf7_user_info_ppc';


	/**
	 * Saves the URI to the visited path session variable.
	 * @since   1.0.0
	 */
	public function save_uri_in_session()
	{
		if (!$this->wpse_session_start()) return;

		global $wp_the_query;

		$ref_blacklist = array('/sw.js');
        $blacklist = array('/offline.html');
        $process = true;

        /* Referrer Blacklist Check */
        if (isset($_SERVER['HTTP_REFERER'])) {
            foreach ($ref_blacklist as $word) {
                if ((strpos($_SERVER['HTTP_REFERER'], $word) !== false)) {
                    $process = false;
                }
            }
        }

        /* Request URI Blacklist Check */
        foreach ($blacklist as $word) {
            if (strpos($_SERVER['REQUEST_URI'], $word) === 0) {
                $process = false;
            }
        }

		if (isset($_SERVER['HTTP_SEC_FETCH_MODE']) && $_SERVER['HTTP_SEC_FETCH_MODE'] !== 'navigate') {
			$process = false;
		}

		if ($process) {
			if (!isset($_SESSION['VISITED_PATH'])) {
				$_SESSION['VISITED_PATH'] = '';
			}

			if (!$wp_the_query->is_404 && $this->uri_exists()) {
				$_SESSION['VISITED_PATH'] .= PHP_EOL . $_SERVER['REQUEST_URI'];
			}
		}

		session_write_close();
	}

	/**
	 * @since   1.0.0
	 */
	public function save_referrer_in_session()
	{
		if (!empty($_SERVER['HTTP_REFERER'])) {
			$_SESSION['HTTP_REFERER'] = $_SERVER['HTTP_REFERER'];
		}
	}

	/**
	 * @since   1.0.0
	 */
	public function save_ppc_value_in_session()
	{
		$ppc_keyword = $this->get('config')->get_config('ppc_keyword');

		if (
			$ppc_keyword
			&& !empty($_GET[$ppc_keyword])
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
	public function alter_db_save_data($form)
	{
		$form->posted_data['user_path'] = $this->get_visited_path();
		return $form;
	}

	/**
	 * @since   1.0.0
	 * @return  string
	 */
	public function get_referrer()
	{
		@session_start(['read_and_close' => true]);
		$ref = isset($_SESSION['HTTP_REFERER']) ? $_SESSION['HTTP_REFERER'] : '';
		if ($ref) {
			$host = $_SERVER['HTTP_HOST'];
			$referrer = parse_url($ref, PHP_URL_HOST);
	
			return $host === $referrer
				? ''
				: $ref;
		}
		return '';
	}

	/**
	 * @since   1.0.0
	 * @return  string|null
	 */
	public function get_ppc_value()
	{
		@session_start(['read_and_close' => true]);
		return isset($_SESSION[self::PPC_SESSION_NAME]) ? $_SESSION[self::PPC_SESSION_NAME] : null;
	}

	/**
	 * @since   1.0.0
	 * @return  string
	 */
	public function get_visited_path()
	{
		@session_start(['read_and_close' => true]);
		return isset($_SESSION['VISITED_PATH']) ? $_SESSION['VISITED_PATH'] : 'Visited path is not set';
	}

	public function get_user_ip()
	{
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

	/**
	 * @since   1.0.0
	 * Converts visited path into html block for email.
	 * @return  string
	 */
	public function get_user_path_html()
	{
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
	 * @return  bool
	 */
	public function uri_exists()
	{
		return !empty($_SERVER['REQUEST_URI']);
	}

	/**
	 * @since   1.0.0
	 * @param   string $uri
	 * @return  null|string
	 */
	public function visitor_path_link($uri)
	{
		return '<a href="' . $this->build_url($uri) . '">' . $uri . '</a>';
	}

	/**
	 * @since   1.0.0
	 * @param   string $uri
	 * @param   string $query
	 * @return  string
	 */
	public function build_url($uri = '', $query = '')
	{
		$protocol = $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
		$host = $_SERVER['HTTP_HOST'];

		return $protocol . $host . $uri . $query;
	}

	/**
	 * Starts the session, saves PPC & Referrer values, then closes the session
	 * @since 1.0.6
	 */
	public function on_parse_request()
	{
		if ($this->wpse_session_start()) {
			$this->save_ppc_value_in_session();
			$this->save_referrer_in_session();
			session_write_close();
		}
	}

	/**
	 * Initiates browser session
	 * @since   1.0.0
	 * @return bool
	 */
	public function wpse_session_start()
	{
		if ((!defined('DOING_CRON') || !DOING_CRON) 
			&& (!defined('REST_REQUEST') || !REST_REQUEST)) {
            return @session_start();
        }
		return false;
	}

	public function run()
	{
		$this->loader()->addAction('parse_request', [$this, 'on_parse_request']);
		$this->loader()->addAction('template_redirect', [$this, 'save_uri_in_session']);
	}
}
