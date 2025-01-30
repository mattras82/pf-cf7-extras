<?php

namespace PublicFunction\Cf7Extras\Inputs;


use PublicFunction\Cf7Extras\Core\RunableAbstract;
use WPCF7_ContactForm;
use WPCF7_Mail;
use WPCF7_Submission;
use WPCF7_TagGenerator;

class MultiFile extends RunableAbstract
{

    private $uploaded_files = [];

    public function add_form_tag()
    {
        wpcf7_add_form_tag(
            array('multifile', 'multifile*'),
            [$this, 'tag_handler'],
            array(
                'name-attr' => true,
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

        $atts['size'] = $tag->get_size_option('50');
        $atts['class'] = $tag->get_class_option($class);
        $atts['id'] = $tag->get_id_option();
        $atts['tabindex'] = $tag->get_option('tabindex', 'signed_int', true);

        if (function_exists('wpcf7_acceptable_filetypes')) {
            $atts['accept'] = wpcf7_acceptable_filetypes(
                $tag->get_option('filetypes'),
                'attr'
            );
        }

        if ($tag->is_required()) {
            $atts['aria-required'] = 'true';
        }

        $atts['aria-invalid'] = $validation_error ? 'true' : 'false';

        $atts['type'] = 'file';
        $atts['name'] = $tag->name . (substr($tag->name, -2) === '[]' ? '' : '[]');
        $atts['multiple'] = 'multiple';

        $atts = wpcf7_format_atts($atts);

        $html = sprintf(
            '<span class="wpcf7-form-control-wrap %1$s"><input %2$s />%3$s</span>',
            sanitize_html_class($tag->name),
            $atts,
            $validation_error
        );

        return $html;
    }

    public function enctype_filter($enctype)
    {
        $multipart = (bool) wpcf7_scan_form_tags(
            array('type' => array('multifile', 'multifile*'))
        );

        if ($multipart) {
            $enctype = 'multipart/form-data';
        }

        return $enctype;
    }

    private function check_failed_upload($file)
    {
        if (!is_array($file['error'])) return false;

        $valid = true;
        foreach ($file['error'] as $err) {
            if ($err && UPLOAD_ERR_NO_FILE !== $err) {
                $valid = false;
            }
        }
        return $valid;
    }

    private function check_upload_file($file)
    {
        $valid = true;
        foreach ($file['tmp_name'] as $tmp) {
            if (!is_uploaded_file($tmp)) {
                $valid = false;
            }
        }
        return $valid;
    }

    private function check_file_type($file, $file_type_pattern)
    {
        $valid = true;
        foreach ($file['name'] as $name) {
            if (!preg_match($file_type_pattern, $name)) {
                $valid = false;
            }
        }
        return $valid;
    }

    private function check_file_size($file, $allowed_size)
    {
        $valid = true;
        foreach ($file['size'] as $size) {
            if ($allowed_size < $size) {
                $valid = false;
            }
        }
        return $valid;
    }

    private function handle_file_upload($name, $tmp, $tag, $uploads_dir)
    {
        $filename = wpcf7_canonicalize($name, 'as-is');
        $filename = wpcf7_antiscript_file_name($filename);

        $filename = apply_filters(
            'wpcf7_upload_file_name',
            $filename,
            $name,
            $tag
        );

        $filename = wp_unique_filename($uploads_dir, $filename);
        $new_file = path_join($uploads_dir, $filename);

        if (false === @move_uploaded_file($tmp, $new_file)) {
            return false;
        }

        // Make sure the uploaded file is only readable for the owner process
        chmod($new_file, 0400);

        return $new_file;
    }

    private function check_file_uploads($file, $tag)
    {
        wpcf7_init_uploads(); // Confirm upload dir
        $uploads_dir = wpcf7_upload_tmp_dir();
        $uploads_dir = wpcf7_maybe_add_random_dir($uploads_dir);
        $submission = WPCF7_Submission::get_instance();

        foreach ($file['name'] as $i => $name) {
            if ($new_file = $this->handle_file_upload($name, $file['tmp_name'][$i], $tag, $uploads_dir)) {
                if (empty($this->uploaded_files[$tag->name])) {
                    $this->uploaded_files[$tag->name] = [];
                }
                $this->uploaded_files[$tag->name][] = $new_file;
            } else {
                return false;
            }
        }

        return true;
    }

    public function validation_filter($result, $tag)
    {
        $name = $tag->name;

        $file = isset($_FILES[$name]) ? $_FILES[$name] : null;

        if (!$this->check_failed_upload($file)) {
            $result->invalidate($tag, wpcf7_get_message('upload_failed_php_error'));
            return $result;
        }

        if ((empty($file['tmp_name']) || empty($file['tmp_name'][0])) and $tag->is_required()) {
            $result->invalidate($tag, wpcf7_get_message('invalid_required'));
            return $result;
        }

        if (!$this->check_upload_file($file)) {
            return $result;
        }

        /* File type validation */
        if (function_exists('wpcf7_acceptable_filetypes')) {
            $file_type_pattern = wpcf7_acceptable_filetypes(
                $tag->get_option('filetypes'),
                'regex'
            );
        }

        $file_type_pattern = '/\.(' . $file_type_pattern . ')$/i';

        if (!$this->check_file_type($file, $file_type_pattern)) {
            $result->invalidate(
                $tag,
                wpcf7_get_message('upload_file_type_invalid')
            );
            return $result;
        }

        /* File size validation */

        $allowed_size = $tag->get_limit_option();

        if (!$this->check_file_size($file, $allowed_size)) {
            $result->invalidate($tag, wpcf7_get_message('upload_file_too_large'));
            return $result;
        }

        if (!$this->check_file_uploads($file, $tag)) {
            $result->invalidate($tag, wpcf7_get_message('upload_failed'));
            return $result;
        }

        return $result;
    }

    public function add_tag_generator()
    {
        $tag_generator = WPCF7_TagGenerator::get_instance();
        $tag_generator->add('multifile', __('multi-file', $this->get('textdomain')), [$this, 'tag_generator'], ['version' => 2]);
    }

    public function tag_generator(WPCF7_ContactForm $contact_form, $args = '')
    {
        $args = wp_parse_args($args, array());
        $type = 'multifile';

        $description = __("Generate a form-tag for a field that allows uploading multiple files. For more details, see %s.", $this->get('textdomain'));

        $desc_link = wpcf7_link(__('https://contactform7.com/file-uploading-and-attachment/', $this->get('textdomain')), __('File uploading and attachment', $this->get('textdomain')));

?>
        <div class="control-box">
            <fieldset>
                <legend><?= sprintf(esc_html($description), $desc_link) ?></legend>

                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><?= esc_html(__('Field type', $this->get('textdomain'))) ?></th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text"><?= esc_html(__('Field type', $this->get('textdomain'))) ?></legend>
                                    <label><input type="checkbox" name="required" /> <?= esc_html(__('Required field', $this->get('textdomain'))) ?></label>
                                </fieldset>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="<?= esc_attr($args['content'] . '-name') ?>"><?= esc_html(__('Name', $this->get('textdomain'))) ?></label></th>
                            <td><input type="text" name="name" class="tg-name oneline" id="<?= esc_attr($args['content'] . '-name') ?>" /></td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="<?= esc_attr($args['content'] . '-limit') ?>"><?= esc_html(__("File size limit (bytes)", $this->get('textdomain'))) ?></label></th>
                            <td><input type="text" name="limit" class="filesize oneline option" id="<?= esc_attr($args['content'] . '-limit') ?>" /></td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="<?= esc_attr($args['content'] . '-filetypes') ?>"><?= esc_html(__('Acceptable file types', $this->get('textdomain'))) ?></label></th>
                            <td><input type="text" name="filetypes" class="filetype oneline option" id="<?= esc_attr($args['content'] . '-filetypes') ?>" /></td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="<?= esc_attr($args['content'] . '-id') ?>"><?= esc_html(__('Id attribute', $this->get('textdomain'))) ?></label></th>
                            <td><input type="text" name="id" class="idvalue oneline option" id="<?= esc_attr($args['content'] . '-id') ?>" /></td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="<?= esc_attr($args['content'] . '-class') ?>"><?= esc_html(__('Class attribute', $this->get('textdomain'))) ?></label></th>
                            <td><input type="text" name="class" class="classvalue oneline option" id="<?= esc_attr($args['content'] . '-class') ?>" /></td>
                        </tr>

                    </tbody>
                </table>
            </fieldset>
        </div>

        <div class="insert-box">
            <input type="text" name="<?= $type ?>" class="tag code" readonly="readonly" onfocus="this.select()" />

            <div class="submitbox">
                <input type="button" class="button button-primary insert-tag" value="<?= esc_attr(__('Insert Tag', $this->get('textdomain'))) ?>" />
            </div>

            <br class="clear" />

            <p class="description mail-tag"><label for="<?= esc_attr($args['content'] . '-mailtag') ?>"><?= sprintf(esc_html(__("To attach the files uploaded through this field to mail, you need to insert the corresponding mail-tag (%s) into the File Attachments field on the Mail tab.", $this->get('textdomain'))), '<strong><span class="mail-tag"></span></strong>') ?><input type="text" class="mail-tag code hidden" readonly="readonly" id="<?= esc_attr($args['content'] . '-mailtag') ?>" /></label></p>
        </div>
<?php
    }

    public function display_warning_message($page, $action, $object)
    {
        if ($object instanceof WPCF7_ContactForm) {
            $contact_form = $object;
        } else {
            return;
        }

        $has_tags = (bool) $contact_form->scan_form_tags(
            array('type' => array('multifile', 'multifile*'))
        );

        if (!$has_tags) {
            return;
        }

        $uploads_dir = wpcf7_upload_tmp_dir();
        wpcf7_init_uploads();

        if (
            !is_dir($uploads_dir)
            or !wp_is_writable($uploads_dir)
        ) {
            $message = sprintf(__('This contact form contains multi-file uploading fields, but the temporary folder for the files (%s) does not exist or is not writable. You can create the folder or change its permission manually.', $this->get('textdomain')), $uploads_dir);

            echo sprintf(
                '<div class="notice notice-warning"><p>%s</p></div>',
                esc_html($message)
            );
        }
    }

    public function add_file_attachments($components, WPCF7_ContactForm $contact_form, WPCF7_Mail $mailer)
    {
        if ($contact_form != null && !empty($this->uploaded_files)) {
            $templates = apply_filters('pf_cf7_multifile_mail_templates', ['mail', 'mail_2']);
            $mailer_name = $mailer->name();
            if (in_array($mailer_name, $templates) && ($template = $contact_form->prop($mailer_name))) {
                foreach ($this->uploaded_files as $tag_name => $files) {
                    if (false !== strpos($template['attachments'], "[{$tag_name}]")) {
                        $components['attachments'] = array_merge($components['attachments'], $files);
                    }
                }
            }
        }

        return $components;
    }

    public function run()
    {
        if (function_exists('wpcf7_init_uploads')) {
            $this->loader()->addAction('wpcf7_init', [$this, 'add_form_tag']);
            $this->loader()->addFilter('wpcf7_form_enctype', [$this, 'enctype_filter']);
            $this->loader()->addFilter('wpcf7_validate_multifile', [$this, 'validation_filter'], 10, 2);
            $this->loader()->addFilter('wpcf7_validate_multifile*', [$this, 'validation_filter'], 10, 2);
            $this->loader()->addAction('wpcf7_admin_init', [$this, 'add_tag_generator'], 60);
            $this->loader()->addAction('wpcf7_admin_warnings', [$this, 'display_warning_message'], 10, 3);
            $this->loader()->addAction('wpcf7_mail_components', [$this, 'add_file_attachments'], 10, 3);
        }
    }
}
