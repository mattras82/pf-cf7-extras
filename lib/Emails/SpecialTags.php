<?php

namespace PublicFunction\Cf7Extras\Emails;

use PublicFunction\Cf7Extras\Core\Container;
use PublicFunction\Cf7Extras\Core\RunableAbstract;

class SpecialTags extends RunableAbstract
{

	public function __construct(Container $c)
	{
		require_once trailingslashit(__DIR__) . 'functions.php';
		parent::__construct($c);
	}

	/**
	 * @since   1.0.0
	 * @param $output
	 * @param $tagname
	 * @param $html
	 * @return false|string
	 */
    public function customTags( $output, $tagname, $html)
    {
        if($tagname === '_year')
            $output = date('Y');

        if ($tagname === 'user_ip')
        	$output = $this->get('user_info_data')->get_geolocation(true);

        if($tagname === 'user_path')
        	$output = $this->get('user_info_data')->get_user_path_html();

        return $output;
    }

	/**
	 * @since   1.0.0
	 * @param $formatted
	 * @param $submitted
	 * @param $html
	 * @return string
	 */
    public function formatArray($formatted, $submitted, $html)
    {
        if (is_array($submitted) && is_callable('pf_formatted_list'))
            $formatted = pf_formatted_list($submitted);

        return $formatted;
    }

    public function run()
    {
        $this->loader()->addFilter('wpcf7_special_mail_tags', [$this, 'customTags'], 20, 3);

        $this->loader()->addFilter('wpcf7_mail_tag_replaced', [$this, 'formatArray'], 20, 3);
    }
}
