<?php

namespace PublicFunction\Cf7Extras\FormData;

use PublicFunction\Cf7Extras\Core\Container;
use PublicFunction\Cf7Extras\Core\RunableAbstract;

class Validation extends RunableAbstract
{

    /**
     * @since   1.0.0
     * @param \WPCF7_Validation $result
     * @param $tag
     * @return \WPCF7_Validation
     */
    public function validatePhone( \WPCF7_Validation $result, $tag )
    {
        $tag = new \WPCF7_FormTag( $tag );

        if($value = $_POST[$tag->name]) {
            $first = substr(preg_replace('/[^0-9]/','',$value), 0, 1);

            if($first == '1' || $first == '0')
                $result->invalidate($tag,
                    sprintf(__('Phone numbers cannot start with a %d.', $this->get('textdomain')), $first));

            $phone = preg_replace( '/\s+/', '', $value );
            if ( !preg_match( '/^(\+?1-?)?(\([2-9]([02-9]\d|1[02-9])\)|[2-9]([02-9]\d|1[02-9]))-?[2-9]([02-9]\d|1[02-9])-?\d{4}$/', $phone ) ) {
                $result->invalidate( $tag, 'Please specify a valid phone number.' );
            }
        }

        return $result;
    }

    /**
     * @since   1.0.0
     * @param \WPCF7_Validation $result
     * @param $tag
     * @return \WPCF7_Validation
     */
    public function validateMin(\WPCF7_Validation $result, $tag)
    {
        $tag = new \WPCF7_FormTag( $tag );
        if(!in_array($tag->name, ['first-name', 'last-name', 'full-name']) && ($value = $_POST[$tag->name]) && ( strlen($value) < 2 ) )
            $result->invalidate($tag, __('This field is required and is too short.', $this->get('textdomain')));

        return $result;
    }

    public function run()
    {
        // Validate min character requirements
        $this->loader()->addFilter('wpcf7_validate_tel*',  [$this, 'validateMin'], 20, 2);
        $this->loader()->addFilter('wpcf7_validate_text*', [$this, 'validateMin'], 20, 2);
        $this->loader()->addFilter('wpcf7_validate_textarea*', [$this, 'validateMin'], 20, 2);

        // Validate phone numbers
        $this->loader()->addFilter('wpcf7_validate_tel',   [$this, 'validatePhone'], 20, 2 );
        $this->loader()->addFilter('wpcf7_validate_tel*',  [$this, 'validatePhone'], 20, 2 );
    }
}