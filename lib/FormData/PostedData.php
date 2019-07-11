<?php

namespace PublicFunction\Cf7Extras\FormData;


use PublicFunction\Cf7Extras\Core\RunableAbstract;

class PostedData extends RunableAbstract
{
	/**
	 * @since   1.0.0
	 * @param \WPCF7_ContactForm $cf7
	 * @return \WPCF7_ContactForm
	 */
    public function addDataToSession(\WPCF7_ContactForm $cf7) {
        if ($postedData = \WPCF7_Submission::get_instance()->get_posted_data()) {
            $data = array(
                'postedData'  => $postedData,
                'cf7'         => $cf7
            );
            $filteredData = apply_filters('pf_cf7_data', $data);
            if (isset($filteredData['postedData'])) {
                $_SESSION['PF_POSTED_DATA'] = $filteredData['postedData'];
            }
            if (isset($filteredData['cf7'])) {
                return $filteredData['cf7'];
            }
        }

        return $cf7;
    }

	/**
	 * @since   1.0.0
	 * @param $args
	 * @return string
	 */
    public function getPostedData($args) {
        if (isset($args['field']) && isset($_SESSION['PF_POSTED_DATA'][$args['field']])) {
            return $_SESSION['PF_POSTED_DATA'][$args['field']];
        }
        return '';
    }

	/**
	 * Transforms posted data
	 * @since   1.0.0
	 * @param $form
	 * @return mixed
	 */
	public function alterDbSaveData($form) {
		$form->posted_data['user_path'] = $this->get('user_info_data')->get_visited_path();
		$form->posted_data['user_ip'] = $this->get('user_info_data')->get_geolocation();
		return $form;
	}

	/**
	 * @since   1.0.0
	 * @param   $posted_data
	 * @return  mixed
	 */
	public function addUserInfo($posted_data) {
		$posted_data['user_referrer'] = $this->get('user_info_data')->get_referrer();

		if ($this->get('config')->get_config('enable_ppc_keyword')) {
			$posted_data['user_ppc'] = (string) $this->get('user_info_data')->get_ppc_value();
		}

		/** Clean all posted data of tags */
		foreach ($posted_data as $key => $data) {
			if (is_string($data)) {
				$posted_data[$key] = $this->remove_tags($data);
			}
		}

		return $posted_data;
	}

	/**
	 * Strips HTML/Script tags from string (comments)
	 * @since   1.0.0
	 * @param   $comment
	 * @return  string
	 */
	private function remove_tags($comment) {
		return preg_replace('#<script(.*?)>(.*?)</script>#is', '', strip_tags($comment));
	}

    public function run() {
        $this->loader()->addFilter( 'wpcf7_before_send_mail', [$this, 'addDataToSession'], 20, 1 );
	    $this->loader()->addFilter('cfdb_form_data', [$this, 'alterDbSaveData'], 10, 1);
	    $this->loader()->addFilter('wpcf7_posted_data', [$this, 'addUserInfo'],10,1);

        $this->loader()->addShortcode('pf_contact_data', [$this, 'getPostedData']);
    }

}
