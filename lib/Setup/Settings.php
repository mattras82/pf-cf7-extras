<?php

namespace PublicFunction\Cf7Extras\Setup;


use PublicFunction\Cf7Extras\Core\Container;
use PublicFunction\Cf7Extras\Core\RunableAbstract;

class Settings extends RunableAbstract
{

    private $settings = [
        'pf_redirect' => []
    ];

    public function __construct(Container &$c)
    {
        parent::__construct($c);
    }

    protected function get_settings(\WPCF7_ContactForm $form)
    {
        return apply_filters('pf_cf7_extras_settings', $this->settings, $form);
    }

    protected function get_title()
    {
        return apply_filters('pf_cf7_extras_settings_title', __('PF Settings', $this->get('textdomain')));
    }

    public function add_properties($properties, \WPCF7_ContactForm $form)
    {
        foreach ($this->get_settings($form) as $key => $prop) {
            $properties[$key] = '';
            add_filter("wpcf7_contact_form_property_$key", function($val) use ($key) {
                return isset($_POST[$key]) ? $_POST[$key] : $val;
            });
        }
        return $properties;
    }

    public function add_editor($panels)
    {
        $panels['pf-settings'] = [
            'title' => $this->get_title(),
            'callback' => [$this, 'display_editor']
        ];
        return $panels;
    }

    public function display_editor(\WPCF7_ContactForm $form)
    {
?>
        <h2><?= esc_html($this->get_title()) ?></h2>
        <fieldset>
            <legend>You can customize the settings below that apply to this contact form.</legend>
            <?php foreach ($this->get_settings($form) as $id => $setting) {
                if (isset($setting['display'])) {
                    if (is_callable($setting['display'])) {
                        call_user_func($setting['display'], $id, $setting, $form->prop($id));
                    } else if (is_callable([$this, "{$setting['display']}_setting"])) {
                        call_user_func([$this, "{$setting['display']}_setting"], $id, $setting, $form->prop($id));
                    }
                } else if (is_callable([$this, $id . '_display'])) {
                    call_user_func([$this, $id . '_display'], $form->prop($id));
                } else {
                    $this->generic_setting($id, $setting, $form->prop($id));
                }
            } ?>
        </fieldset>
    <?php
    }

    private function generic_setting($id, $setting, $value = null, $label = null)
    {
        $label = $this->process_label($id, $setting, $label);
        $attr = [
            'value' => $value,
            'type'  => !empty($setting['type']) ? $setting['type'] : 'text',
            'id'    => 'wpcf7-' . $id,
            'name'  => $id,
            'class' => 'large-text'
        ];
        $attributes = $this->process_attributes($setting, $attr);
        $this->display_setting(
            $id,
            $label,
            sprintf('<input %s/>', $attributes),
            !empty($setting['desc']) ? $setting['desc'] : ''
        );
    }

    private function display_setting($id, $label, $input_html, $desc)
    {
        ?>
                <div class="wpcf7-pf-setting wpcf7-pf-setting--<?= esc_attr($id) ?>">
                    <?php if ($label) : ?>
                    <label for="<?= 'wpcf7-' . $id ?>"><strong><?= esc_html($label) ?></strong></label><br />
                    <?php endif ?>
                    <?= $input_html ?>
                    <?php if ($desc) : ?>
                    <p class="description"><?= $desc ?></p>
                    <?php endif ?>
                </div>
        <?php
    }

    private function textarea_setting($id, $setting, $value, $label = '')
    {
        $label = $this->process_label($id, $setting, $label);
        $attr = [
            'id'    => "wpcf7-$id",
            'name'  => $id,
            'class' => 'large-text',
            'style' => 'min-height:100px'
        ];
        $attributes = $this->process_attributes($setting, $attr);
        $this->display_setting(
            $id,
            $label,
            sprintf('<textarea %s>%s</textarea>', $attributes, $value),
            !empty($setting['desc']) ? $setting['desc'] : ''
        );
    }

    private function checkbox_setting($id, $setting, $value, $label = '')
    {
        $label = $this->process_label($id, $setting, $label);
        $attr = [
            'value' => 'on',
            'checked'   => $value == 'on' ? 'checked' : '',
            'type'  => 'checkbox',
            'id'    => 'wpcf7-' . $id,
            'name'  => $id
        ];
        $attributes = $this->process_attributes($setting, $attr);
        $this->display_setting(
            $id,
            null, // We're including the label in the input HTML below
            sprintf('<label><strong>%s</strong><input %s/></label>', esc_html($label), $attributes),
            !empty($setting['desc']) ? $setting['desc'] : ''
        );
    }

    private function process_attributes($setting, $attr = [])
    {
        $attributes = '';if (!empty($setting['attr']) && is_array($setting['attr'])) {
            foreach ($setting['attr'] as $name => $val) {
                $attr[$name] = $val;
            }
        }
        foreach ($attr as $a => $v) {
            $v = esc_attr__($v, $this->get('textdomain'));
            $attributes .= " {$a}=\"{$v}\"";
        }
        return $attributes;
    }

    private function process_label($id, $setting, $label)
    {
        if (!empty($setting['label'])) {
            $label = $setting['label'];
        }
        if (!$label) {
            $label = ucwords(str_replace('_', ' ', str_replace('pf_', 'PF ', $id)));
        }
        return $label;
    }

    private function pf_redirect_display($value = null)
    {
        $setting = [
            'desc'  => 'Upon a successful submission, the site will redirect the visitor to the given URL',
            'attr'  => [
                'placeholder' => '/contact-us/thank-you/'
            ]
        ];
        $this->generic_setting('pf_redirect', $setting, $value, __('Redirect', $this->get('textdomain')));
    }

    public function handle_redirect(\WPCF7_ContactForm $form)
    {
        if (!defined('REST_REQUEST') && ($redirect = $form->prop('pf_redirect'))) {
            wp_redirect($redirect, 302, 'PublicFunction CF7 Extras');
            exit;
        }
    }

    public function add_redirect_script($html, $class, $content, \WPCF7_ContactForm $form)
    {
        if ($redirect = $form->prop('pf_redirect')) {
            $id = $form->unit_tag();
            $html .= "\n<script>document.querySelector('#{$id}').addEventListener('wpcf7mailsent', function(e){location.href='{$redirect}';e.stopPropagation ? e.stopPropagation() : e.cancelBubble=true;})</script>";
        }
        return $html;
    }

    public function run()
    {
        $this->loader()->addFilter('wpcf7_pre_construct_contact_form_properties', [$this, 'add_properties'], 10, 2);
        $this->loader()->addFilter('wpcf7_editor_panels', [$this, 'add_editor']);
        $this->loader()->addAction('wpcf7_mail_sent', [$this, 'handle_redirect']);
        $this->loader()->addFilter('wpcf7_form_response_output', [$this, 'add_redirect_script'], 10, 5);
    }
}
