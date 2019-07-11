<?php

namespace PublicFunction\Cf7Extras\Setup;


use PublicFunction\Cf7Extras\Core\Container;
use PublicFunction\Cf7Extras\Core\RunableAbstract;

class Includes extends RunableAbstract
{

	private $pages;

	private $addedResources;

	public function __construct(Container $c)
	{
		parent::__construct($c);

		if ($this->container->get('wpcf7_includes') !== 'all') {
			$this->pages = $this->container->get('wpcf7_includes');
		}
	}

	private function is_associative($arr)
	{
		return count(array_filter(array_keys($arr), 'is_string')) > 0;
	}

	private function add_resources()
	{
		if (function_exists('wpcf7_enqueue_scripts')) {
			wpcf7_enqueue_scripts();
		}

		if (function_exists('wpcf7_enqueue_styles')) {
			wpcf7_enqueue_styles();
			$this->addedResources = true;
		}
	}

	public function enqueue()
	{
		if (is_array($this->pages) && $this->is_associative($this->pages)) {
			foreach ($this->pages as $post_type => $slug) {
				if (is_singular($post_type)) {
					if ($slug === 'all') {
						$this->add_resources();
					} elseif ($post_type === 'page' && is_page($slug)) {
						$this->add_resources();
					} elseif (is_single($slug)) {
						$this->add_resources();
					}
				} elseif (is_front_page() && ($post_type === 'frontpage' || $slug === 'frontpage' ||
						(is_array($slug) && in_array('frontpage', $slug)))) {
					$this->add_resources();
				}
			}
		} elseif (is_page($this->pages)) {
			$this->add_resources();
		}
		if ($this->addedResources === false) {
			wp_dequeue_script('google-recaptcha');
		}
	}

	public function run()
	{
		if (!empty($this->pages)) {
			$this->loader()->addFilter('wpcf7_load_js', '__return_false');
			$this->loader()->addFilter('wpcf7_load_css', '__return_false');
			$this->loader()->addAction('wp_enqueue_scripts', [$this, 'enqueue'], 15);
			$this->addedResources = false;
		}
	}

}
