<?php

namespace PublicFunction\Cf7Extras\UserInfo;


use PublicFunction\Cf7Extras\Core\RunableAbstract;

class Config extends RunableAbstract
{
	const CONFIG_NAME = 'pf_cf7_user_info_settings';
	
	private $config;

	public function add_options_page() {
		add_options_page('Contact Form 7 User Info', 'PF CF7 User Info', 'manage_options', 'pf_cf7_user_info', array($this, 'render_options_page') );
	}

	public function init_options_page() {
		register_setting('pf_cf7_user_info', self::CONFIG_NAME);
		add_settings_section(
			'general',
			'General',
			array($this, 'general_description'),
			'pf_cf7_user_info'
		);
		add_settings_field(
			'enable_ppc_keyword',
			'Enable PPC keyword',
			array($this, 'render_enable_ppc_keyword'),
			'pf_cf7_user_info',
			'general'
		);
		add_settings_field(
			'ppc_keyword',
			'PPC keyword',
			array($this, 'render_ppc_keyword'),
			'pf_cf7_user_info',
			'general'
		);
	}

	public function render_options_page() {
		echo "
            <form action='options.php' method='post'>
                <h2>PF CF7 User Info</h2>
        ";
		settings_fields( 'pf_cf7_user_info' );
		do_settings_sections( 'pf_cf7_user_info' );
		submit_button();
		echo "</form>";
	}

	public function general_description() {
		echo '';
	}

	public function render_ppc_keyword() {
		echo '<input type="text" id="ppc_keyword" name="'.self::CONFIG_NAME.'[ppc_keyword]" value="'.$this->get_config('ppc_keyword').'">';
	}

	public function render_enable_ppc_keyword() {
		$enabled = boolval($this->get_config('enable_ppc_keyword'));
		echo "<select name='".self::CONFIG_NAME."[enable_ppc_keyword]'>
                <option value='1' ".($enabled ? 'selected' : '').">Yes</option>
                <option value='0' ".($enabled ? '' : 'selected').">No</option>
            </select>";
	}

	/**
	 * Grab configuration for plugin.
	 *
	 * @since     1.0.0
	 * @param     null $field
	 * @return    null
	 */
	public function get_config($field = null) {
		if (!isset($this->config)) {
			$this->config = get_option(self::CONFIG_NAME);
			if (!$this->config)
				return null;
		}

		if ($field) {
			if (is_array($field)) {
				$value = $this->config;
				foreach ($field as $level) {
					if (isset($value[$level]))
						$value = $value[$level];
					else {
						return null;
					}
				}
			} else {
				$value = isset($this->config[$field]) ? $this->config[$field] : null;
			}
			return $value;
		}

		return $this->config;
	}

	public function run()
	{
		$this->loader()->addAction('admin_menu', [$this, 'add_options_page']);
		$this->loader()->addAction('admin_init', [$this, 'init_options_page']);
	}
}