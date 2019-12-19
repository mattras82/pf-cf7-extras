<?php

namespace PublicFunction\Cf7Extras;

use PublicFunction\Cf7Extras\Core\Container;
use PublicFunction\Cf7Extras\Core\Loader;
use PublicFunction\Cf7Extras\Core\SingletonTrait;
use PublicFunction\Cf7Extras\Core\JsonConfig;

class Plugin
{
    use SingletonTrait;

    /**
     * Flag used to check for singleton instance
     * @var bool
     */
    protected $started = false;

    /**
     * Storage for all
     * @var Container
     */
    protected $container;

	/**
	 * Used to store the config.json file wrapped in a JsonConfig object
	 * @var JsonConfig
	 */
	protected $config;

    /**
     * Used to enqueue actions and filters for the theme.
     * @var Loader
     */
    protected $loader;

    protected function __construct()
    {
	    $_plugin_dir = trailingslashit( plugins_url('', dirname(__FILE__) ) );
	    $_plugin_path = trailingslashit(realpath(__DIR__ . '/../'));
	    $_theme_dir = trailingslashit(get_stylesheet_directory_uri());
	    $_theme_path = trailingslashit(get_theme_root() . DIRECTORY_SEPARATOR . get_stylesheet());
	    $this->container = new Container([
		    // General
		    // -------------------------------------
		    'plugin' => [
			    'name' => 'PublicFunction Contact Form 7 Extras',
			    'short_name' => 'pf-cf7-extras',
			    'directory' => $_plugin_dir,
			    'path' => $_plugin_path,
			    'version' => '1.0.1',
			    'config_path' => $_plugin_path . 'config/',

			    // Asset paths and directories
			    // -------------------------------------
			    'assets' => [
				    'dir' => trailingslashit($_plugin_dir . 'assets'),
				    'path' => trailingslashit($_plugin_path . 'assets'),

				    'images' => trailingslashit($_plugin_dir . 'assets/images'),
				    'images_path' => trailingslashit($_plugin_path . 'assets/images'),
			    ],
		    ],

		    'theme' => [
			    'directory' => $_theme_dir,
			    'path' => $_theme_path,
			    'config_path' => $_theme_path . 'config/',

			    // Asset paths and directories
			    // -------------------------------------
			    'assets' => [
				    'dir' => trailingslashit($_theme_dir . 'assets'),
				    'path' => trailingslashit($_theme_path . 'assets'),

				    'images_dir' => trailingslashit($_theme_dir . 'assets/images'),
				    'images_path' => trailingslashit($_theme_path . 'assets/images'),
			    ],
		    ],
	    ]);
	    $this->config = new JsonConfig($this->theme_or_plugin('config_path', 'config.json', true));
	    $this->container->bulkSet([
		    // Reset theme array now that we have config values
		    'theme' => [
			    'name' => isset($this->config['theme']['name']) ? $this->config['theme']['name'] : null,
			    'short_name' => isset($this->config['theme']['short_name']) ? $this->config['theme']['short_name'] : null,
			    'directory' => $_theme_dir,
			    'path' => $_theme_path,
			    'version' => $this->config['version'],
			    'config_path' => trailingslashit($_theme_path . 'config'),
			    'icon'      => isset($this->config['styles']['icon']) ? $this->config['styles']['icon'] : null,
			    'build' => $this->config['build'] ?: $this->config['version'],
			    'partials' => untrailingslashit($_theme_path . (isset($this->config['theme']['partials']) ? $this->config['theme']['partials'] : 'templates/partials')),

			    // Asset paths and directories
			    // -------------------------------------
			    'assets' => [
				    'dir' => trailingslashit($_theme_dir . 'assets'),
				    'path' => trailingslashit($_theme_path . 'assets'),

				    'images_dir' => trailingslashit($_theme_dir . 'assets/images'),
				    'images_path' => trailingslashit($_theme_path . 'assets/images'),
			    ],
		    ],

		    'env' => [
			    'production' => $this->config['env']['production'],
			    'development' => $this->config['env']['development']
		    ],

		    'textdomain' => $this->container->get('plugin.short_name'),

		    'wpcf7_includes' => $this->config['wpcf7_includes'],

		    'loader' => function () {
			    return new Loader();
		    },
		    'special_tags' => function (Container &$c) {
			    return new Emails\SpecialTags($c);
		    },
		    'includes' => function (Container &$c) {
			    return new Setup\Includes($c);
		    },
		    'posted_data' => function (Container &$c) {
			    return new FormData\PostedData($c);
		    },
		    'custom_data' => function (Container &$c) {
			    return new FormData\CustomData($c);
		    },
		    'validation' => function (Container &$c) {
			    return new FormData\Validation($c);
		    },
		    'user_info_data' => function (Container &$c) {
			    return new UserInfo\Data($c);
		    },
		    'config' => function (Container &$c) {
			    return new UserInfo\Config($c);
		    }
	    ]);
    }

    /**
     * Runs the app
     */
    protected function _run()
    {
        foreach ($this->container->getRunables() as $name => $runable) {
            if ($name != 'loader')
                $runable->run();
        }

        $this->loader()->run();
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // API
    //
    // Methods here are used throughout the theme. You can use these methods
    // by calling pf_cf7_extras()->filter() or pf_cf7_extras('theme.path') which is the same as
    // Plugin::getInstance()->container()->get('theme.path'). Passing a string
    // to the pf() wrapper function returns an object from the container while
    // using the method pointer `->` returns one of the following methods.
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @param string $offset
     * @return mixed|null
     */
    public function get($offset)
    {
        return $this->container->get($offset);
    }

    /**
     * Returns the container
     * @return Container
     */
    public function container()
    {
        return $this->container;
    }

    /**
     * Returns the loader
     * @return Loader
     */
    public function loader()
    {
        return $this->container->get('loader');
    }

	/**
	 * Checks to see whether a file exists in the current theme. If it does, return theme. Otherwise returns plugin.
	 * @param string $path
	 * @param string $file
	 * @param bool $realpath
	 * @return string
	 */
	public function theme_or_plugin($path, $file, $realpath = false) {
		if (file_exists($this->get('theme.' . ($realpath ? $path : str_replace('dir', 'path', $path))) . $file)) {
			return $this->get("theme.$path") . $file;
		}
		return $this->get("plugin.$path") . $file;
	}

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Static API
    //
    // Primarily used to start and stop this plugin
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Starts the plugin
     * @return Plugin
     */
    public static function start()
    {
        $instance = self::getInstance();

        if (!$instance->started) {
            $instance->_run();
            $instance->started = true;
        }

        return $instance;
    }

    /**
     * Kills the application and redirects to a wordpress error page with a message
     * @param string $error
     * @param string $subtitle
     * @param string $title
     */
    public static function stop($error, $subtitle = '', $title = '')
    {
	    $title = $title ?: __(self::getInstance()->get('plugin.name').' - Error', self::getInstance()->get('textdomain'));
        $message = "<h1>{$title}";

        if ($subtitle)
            $message .= "<br><small>{$subtitle}</small>";

        $message .= "</h1>";
        $message .= "<p>{$error}</p>";

        wp_die($message);
    }
}
