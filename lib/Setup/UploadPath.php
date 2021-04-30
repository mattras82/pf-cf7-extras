<?php

namespace PublicFunction\Cf7Extras\Setup;


use PublicFunction\Cf7Extras\Core\RunableAbstract;

class UploadPath extends RunableAbstract
{

    public function get_base_path()
    {
        $docRoot = getenv('DOCUMENT_ROOT');
        $path = trailingslashit(substr($docRoot, 0, strrpos( $docRoot, '/')));
        return $path;
    }

    public function get_upload_dir()
    {
        $upload_dir = get_option('upload_dir');
        $path = $this->get_base_path();
        return $path . $upload_dir;
    }

    public function CFDB_filter($formData)
    {
        if (!empty($formData->uploaded_files)) {
            foreach($formData->uploaded_files as $key => $files) {
                $paths = (array) $files;
                foreach ($paths as $file) {
                    $file_pieces = pathinfo($file);
                    $guid = uniqid();
                    $newfile = $this->get_upload_dir().'/'.$guid.'.'.$file_pieces['extension'];
                    if (!is_array($formData->key)) $formData->key = array();
                    $formData->$key[] = $guid.'.'.$file_pieces['extension'];
                    if (!is_array($formData->posted_data[$key])) $formData->posted_data[$key] = array();
                    $formData->posted_data[$key][] = $guid.'.'.$file_pieces['extension'];
    
                    copy($file, $newfile);
                }
            }
            $formData->uploaded_files = [];
        }
        return $formData;
    }

    private function define_upload_temp_dir()
    {
        $temp_dir = $this->get_upload_dir();
        if (!defined('WPCF7_UPLOADS_TMP_DIR') && !empty($temp_dir)) {
            define('WPCF7_UPLOADS_TMP_DIR', trailingslashit($temp_dir) . 'temp');
        }
    }

    public function run()
    {
        $this->loader()->addFilter('cfdb_form_data', [$this, 'CFDB_filter']);
        $this->define_upload_temp_dir();
    }

}