<?php

namespace PublicFunction\Cf7Extras\FormData;


use PublicFunction\Cf7Extras\Core\RunableAbstract;

class CustomData extends RunableAbstract
{
	/**
	 * @since   1.0.0
	 * @param $n
	 * @param $options
	 * @param $args
	 * @return array|null
	 */
    public function addCustomPostTypes($n, $options, $args)
    {
        $types = get_post_types();

        // Filter types for custom data sets
        $types = apply_filters('pf_cf7_data_types', $types);

        foreach ($types as $post_type) {
            if (in_array("pf.$post_type", $options)) {
            	// Default query args
            	$args = [
		            'post_type'     => $post_type,
		            'nopaging'      => true,
		            'order'         => 'ASC',
		            'orderby'       => 'title'
	            ];

            	// Filter query args for custom queries
            	$args = apply_filters("pf_cf7_data_args_$post_type", $args);

                $posts = new \WP_Query($args);

                $items = [];

                foreach ($posts->posts as $post) {
                    $items[] = $post->post_title;
                }

                if (!empty($items)) return $items;
            }
        }

        return null;
    }

    public function run()
    {
        $this->loader()->addFilter('wpcf7_form_tag_data_option', [$this, 'addCustomPostTypes'], 10, 3);
    }
}
