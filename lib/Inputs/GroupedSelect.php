<?php

namespace PublicFunction\Cf7Extras\Inputs;


use PublicFunction\Cf7Extras\Core\RunableAbstract;
use WPCF7_ContactForm;
use WPCF7_Mail;
use WPCF7_Submission;
use WPCF7_TagGenerator;

class GroupedSelect extends RunableAbstract
{

    public function add_form_tag()
    {
        wpcf7_add_form_tag(
            array('grouped_select', 'grouped_select*'),
            [$this, 'tag_handler'],
            array(
                'name-attr' => true,
                'selectable-values' => true,
            )
        );
    }

    public function tag_handler($tag)
    {
        if (empty($tag->name)) {
            return '';
        }

        $validation_error = wpcf7_get_validation_error($tag->name);

        $class = wpcf7_form_controls_class($tag->type);

        if ($validation_error) {
            $class .= ' wpcf7-not-valid';
        }

        $atts = array();

        $atts['class'] = $tag->get_class_option($class);
        $atts['id'] = $tag->get_id_option();
        $atts['tabindex'] = $tag->get_option('tabindex', 'signed_int', true);

        if ($tag->is_required()) {
            $atts['aria-required'] = 'true';
        }

        $atts['aria-invalid'] = $validation_error ? 'true' : 'false';

        $multiple = $tag->has_option('multiple');
        $include_blank = $tag->has_option('include_blank');
        $first_as_label = $tag->has_option('first_as_label');

        if ($tag->has_option('size')) {
            $size = $tag->get_option('size', 'int', true);

            if ($size) {
                $atts['size'] = $size;
            } elseif ($multiple) {
                $atts['size'] = 4;
            } else {
                $atts['size'] = 1;
            }
        }

        if ($data = (array) $tag->get_data_option()) {
            // If we've got an associative array from the data option, process the values & labels separately
            if (count(array_filter(array_keys($data), 'is_string')) > 0) {
                $tag->values = $data;
                $data_labels = true;
            } else {
                $tag->values = array_merge($tag->values, array_values($data));
                $tag->labels = array_merge($tag->labels, array_values($data));
            }
        }

        $values = $tag->values;
        $labels = $data_labels ? [] : $tag->labels;

        $default_choice = $tag->get_default_option(null, array(
            'multiple' => $multiple,
            'shifted' => $include_blank,
        ));

        if (
            $include_blank
            or empty($values)
        ) {
            array_unshift($labels, '---');
            array_unshift($values, '');
        } elseif ($first_as_label) {
            $values[0] = '';
        }

        $html = '';
        $hangover = wpcf7_get_hangover($tag->name);
        $optgroup = false;

        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $html .= sprintf('<optgroup label="%s">', $key);
                $labels = array_merge($tag->labels, array_values($value));
                foreach ($value as $o_key => $o_val) {
                    $html .= $this->process_option_output($o_key, $o_val, $labels, $hangover, $default_choice);
                }
                $html .= '</optgroup>';
            } else if (substr($value, 0, 2) === '==' && substr($value, -2) === '==') {
                if ($optgroup) $html .= '</optgroup>';
                $html .= sprintf('<optgroup label="%s">', str_replace('==', '', $value));
                $optgroup = true;
            } else {
                $html .= $this->process_option_output($key, $value, $labels, $hangover, $default_choice);
            }
        }

        if ($optgroup) $html .= '</optgroup>';

        if ($multiple) {
            $atts['multiple'] = 'multiple';
        }

        $atts['name'] = $tag->name . ($multiple ? '[]' : '');

        $atts = wpcf7_format_atts($atts);

        $html = sprintf(
            '<span class="wpcf7-form-control-wrap %1$s"><select %2$s>%3$s</select>%4$s</span>',
            sanitize_html_class($tag->name),
            $atts,
            $html,
            $validation_error
        );

        return $html;
    }

    private function process_option_output($key, $value, $labels, $hangover, $default_choice)
    {
        if ($hangover) {
            $selected = in_array($value, (array) $hangover, true);
        } else {
            $selected = in_array($value, (array) $default_choice, true);
        }

        $item_atts = array(
            'value' => $value,
            'selected' => $selected ? 'selected' : '',
        );

        $item_atts = wpcf7_format_atts($item_atts);

        $label = isset($labels[$key]) ? $labels[$key] : $value;

        if (is_string($key) && $key !== $value) {
            $label = $key;
        }

        return sprintf(
            '<option %1$s>%2$s</option>',
            $item_atts,
            esc_html($label)
        );
    }

    public function validation_filter($result, $tag)
    {
        $name = $tag->name;

        $has_value = isset($_POST[$name]) && '' !== $_POST[$name];

        if ($has_value and $tag->has_option('multiple')) {
            $vals = array_filter((array) $_POST[$name], function ($val) {
                return '' !== $val;
            });

            $has_value = !empty($vals);
        }

        if ($tag->is_required() and !$has_value) {
            $result->invalidate($tag, wpcf7_get_message('invalid_required'));
        }

        return $result;
    }

    public function add_tag_generator()
    {
        $tag_generator = WPCF7_TagGenerator::get_instance();
        $tag_generator->add('grouped_select', __('grouped drop-down', $this->get('textdomain')), [$this, 'tag_generator'], ['version' => 2]);
    }

    public function tag_generator(WPCF7_ContactForm $contact_form, $args = '')
    {
        $args = wp_parse_args($args, array());

        $description = __("Generate a form-tag for a drop-down menu that is grouped using the <optgroup> element. For more details, see %s.", 'contact-form-7');

        $desc_link = wpcf7_link(__('https://contactform7.com/checkboxes-radio-buttons-and-menus/', 'contact-form-7'), __('Checkboxes, radio buttons and menus', 'contact-form-7'));

?>
        <div class="control-box">
            <fieldset>
                <legend><?php echo sprintf(esc_html($description), $desc_link); ?></legend>

                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><?php echo esc_html(__('Field type', 'contact-form-7')); ?></th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text"><?php echo esc_html(__('Field type', 'contact-form-7')); ?></legend>
                                    <label><input type="checkbox" name="required" /> <?php echo esc_html(__('Required field', 'contact-form-7')); ?></label>
                                </fieldset>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="<?php echo esc_attr($args['content'] . '-name'); ?>"><?php echo esc_html(__('Name', 'contact-form-7')); ?></label></th>
                            <td><input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr($args['content'] . '-name'); ?>" /></td>
                        </tr>

                        <tr>
                            <th scope="row"><?php echo esc_html(__('Options', 'contact-form-7')); ?></th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text"><?php echo esc_html(__('Options', 'contact-form-7')); ?></legend>
                                    <textarea name="values" class="values" id="<?php echo esc_attr($args['content'] . '-values'); ?>"></textarea>
                                    <label for="<?php echo esc_attr($args['content'] . '-values'); ?>"><span class="description"><?php echo esc_html(__("One option per line.", 'contact-form-7')); ?><br>To group options, wrap the group label in two equal characters (ie ==Group Name==) and place the group label <em>before</em> the options.</span></label><br />
                                    <label><input type="checkbox" name="multiple" class="option" /> <?php echo esc_html(__('Allow multiple selections', 'contact-form-7')); ?></label><br />
                                    <label><input type="checkbox" name="include_blank" class="option" /> <?php echo esc_html(__('Insert a blank item as the first option', 'contact-form-7')); ?></label>
                                </fieldset>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="<?php echo esc_attr($args['content'] . '-id'); ?>"><?php echo esc_html(__('Id attribute', 'contact-form-7')); ?></label></th>
                            <td><input type="text" name="id" class="idvalue oneline option" id="<?php echo esc_attr($args['content'] . '-id'); ?>" /></td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="<?php echo esc_attr($args['content'] . '-class'); ?>"><?php echo esc_html(__('Class attribute', 'contact-form-7')); ?></label></th>
                            <td><input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr($args['content'] . '-class'); ?>" /></td>
                        </tr>

                    </tbody>
                </table>
            </fieldset>
        </div>

        <div class="insert-box">
            <input type="text" name="grouped_select" class="tag code" readonly="readonly" onfocus="this.select()" />

            <div class="submitbox">
                <input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr(__('Insert Tag', 'contact-form-7')); ?>" />
            </div>

            <br class="clear" />

            <p class="description mail-tag"><label for="<?php echo esc_attr($args['content'] . '-mailtag'); ?>"><?php echo sprintf(esc_html(__("To use the value input through this field in a mail field, you need to insert the corresponding mail-tag (%s) into the field on the Mail tab.", 'contact-form-7')), '<strong><span class="mail-tag"></span></strong>'); ?><input type="text" class="mail-tag code hidden" readonly="readonly" id="<?php echo esc_attr($args['content'] . '-mailtag'); ?>" /></label></p>
        </div>
<?php
    }

    public function run()
    {
        if (function_exists('wpcf7_init')) {
            $this->loader()->addAction('wpcf7_init', [$this, 'add_form_tag']);
            $this->loader()->addFilter('wpcf7_validate_grouped_select', [$this, 'validation_filter'], 10, 2);
            $this->loader()->addFilter('wpcf7_validate_grouped_select*', [$this, 'validation_filter'], 10, 2);
            $this->loader()->addAction('wpcf7_admin_init', [$this, 'add_tag_generator'], 60);
        }
    }
}
