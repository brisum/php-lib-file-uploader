<?php

namespace Brisum\Lib;

class FileUploader {
    /**
     * @var string
     */
    protected $pathUpload;

    /**
     * @var array
     */
    protected $files;

    /**
     * FileUploader constructor.
     * @param string $pathUpload
     */
    public function __construct($pathUpload)
    {
        $this->pathUpload = $pathUpload;
    }

    /**
     * @return array
     */
    public function save($name)
    {
        $this->files = &$_FILES[$name];

        $count = max(count($_FILES[$name]['tmp_name']), 1);
        $resp = [];
        for ($i = 0; $i < $count; $i++) {
            $resp[] = $this->saveSingle($i);
        }

        unset($this->files);

        return $resp;
    }

    public function saveSingle($i = 0)
    {
        $resp = array(
            'error' => true,
            'name' => '',
            'msg' => "Upload Unsuccessful"
        );

        if (empty($this->files['tmp_name'][$i])) {
            $resp['msg'] = "Upload Unsuccessful (empty temp file)";
            return $resp;
        }

        $isMultiple = is_array($this->files['tmp_name']);
        if (!$isMultiple) {
            $this->files['name'] = array($this->files['name']);
            $this->files['type'] = array($this->files['type']);
            $this->files['tmp_name'] = array($this->files['tmp_name']);
            $this->files['error'] = array($this->files['error']);
            $this->files['size'] = array($this->files['size']);
        }

        if ($this->files['error'][$i] != UPLOAD_ERR_OK) {
            switch ($this->files['error'][$i]) {
                case UPLOAD_ERR_INI_SIZE:
                    $resp['msg'] = "Розмір файла, що завантажувався перевищив максимально дозволений розмір";
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $resp['msg'] = "Розмір файла, що завантажувався перевищив максимально дозволений розмір.\n";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $resp['msg'] = "Завантажуваний файл був отриманий лише частково.";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $resp['msg'] = "Ви не вибрали файл для завантаження.";
                    break;
                #case UPLOAD_ERR_NO_TMP_DIR:
                #    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $resp['msg'] = "Не вдалося зберегти файл на сервері.";
                    break;
                #case UPLOAD_ERR_EXTENSION:
                #    break;
                default:
                    $resp['msg'] = "Upload Unsuccessful";
            }

            return $resp;
        }

        if (!self::validMime($this->files['type'][$i], $_POST['type'])) {
            $resp['msg'] = "error_invalid_type_of_file";

            return $resp;
        }

        if (!is_uploaded_file($this->files['tmp_name'][$i])) {
            $resp['msg'] = "Upload Unsuccessful. File isn't uploaded.";

            return $resp;
        }

        $special_chars = array(
            ' ',
            '`',
            '"',
            '\'',
            '\\',
            '/',
            " ",
            "#",
            "$",
            "%",
            "^",
            "&",
            "*",
            "!",
            "~",
            "‘",
            "\"",
            "’",
            "'",
            "=",
            "?",
            "/",
            "[",
            "]",
            "(",
            ")",
            "|",
            "<",
            ">",
            ";",
            "\\",
            ",",
            "+"
        );
        $filename = str_replace($special_chars, '', $this->files['name'][$i]);
        $filepath = $this->pathUpload . $filename;
        if (!move_uploaded_file($this->files['tmp_name'][$i], $filepath)) {
            $resp['msg'] = "Upload Unsuccessful. File isn't moved to upload folder." . $filepath;

            return $resp;
        }

        @chmod($filepath, 0777);
        $info = pathinfo($filepath);

        $resp = array(
            'error' => false,
            'filename' => $filename,
            'ext' => $info['extension'],
            'file_path' => $filepath,
            'msg' => "Successful upload"
        );

        return $resp;
    }


    /**
     *  Check the mime type of the file for
     *  avoid upload any dangerous file.
     *
     * @param string $mime is the type of file can be "image","audio" or "file"
     * @param string $file_type is the mimetype of the field
     * @return bool
     */
    protected static function validMime($mime, $file_type)
    {
        $imagesExts = array(
            'image/gif',
            'image/jpeg',
            'image/pjpeg',
            'image/png',
            'image/x-png'
        );
        $audioExts = array(
            'audio/mpeg',
            'audio/mpg',
            'audio/x-wav',
            'audio/mp3'
        );

        if ($file_type == "image") {
            if (in_array($mime, $imagesExts)) {
                return true;
            }
        } elseif ($file_type == "audio") {
            if (in_array($mime, $audioExts)) {
                return true;
            }
        } else {
            //TODO: here users should be set what mime types
            //are safety for the "files" type of field
            return true;
        }

        return false;
    }
}
