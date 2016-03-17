<?php

/**
 * File Class
 * file write, upload, move, delete etc.
 *
 * @package     CoolPHP
 * @subpackage  setting
 * @category    helper
 * @author      Intril.Leng <jj.comeback@gmail.com>
 */
class File {

    const LIST_FILE = 'file';
    const LIST_FOLDER = 'folder';

    /**
     * 允许上传的文件大小<M>
     * @var unknown
     */
    public $upload_size = 0;

    /**
     * 允许上传的后缀名
     * @var unknown
     */
    public $upload_ext = NULL;

    /**
     * 允话上传的文件mine
     * @var unknown
     */
    public $upload_mine = NULL;

    public $chmod;

    public function __construct($upload_conf = array(), $chmod = 0777) {
        $this->upload_ext = isset($upload_conf['ext']) ? $upload_conf['ext'] : null;
        $this->upload_mine = isset($upload_conf['mine']) ? $upload_conf['mine'] : null;
        $this->upload_size = isset($upload_conf['size']) ? intval($upload_conf['size']) : null;
        $this->chmod = $chmod;
    }

    /**
     * Delete contents in a folder recursively
     * @param string $dir Path of the folder to be deleted
     * @return int Total of deleted files/folders
     */
    public function purge_content($dir) {
        $total_del = 0;
        $handle = opendir ( $dir );
        while ( false !== ($file = readdir ( $handle )) ) {
            if ($file != '.' && $file != '..') {
                if (is_dir ( $dir . $file )) {
                    $total_del += $this->purge_content ( $dir . $file . '/' );
                    if (rmdir ( $dir . $file )){
                        $total_del ++;
                    }
                } else {
                    if (unlink ( $dir . $file )){
                        $total_del ++;
                    }
                }
            }
        }
        closedir ( $handle );
        return $total_del;
    }

    /**
     * Delete a folder (and all files and folders below it)
     * @param string $path Path to folder to be deleted
     * @param bool $deleteSelf true if the folder should be deleted. false if just its contents.
     * @return int|bool Returns the total of deleted files/folder. Returns false if delete failed
     */
    public function delete($path, $delete_self = true) {
        if (is_file ( $path )) {
            // delete all sub folder/files under, then delete the folder itself
            if (is_dir ( $path )) {
                if ($path [strlen ( $path ) - 1] != '/' && $path [strlen ( $path ) - 1] != '\\') {
                    $path .= DS;
                    $path = str_replace ( '\\', '/', $path );
                }
                if ($total = $this->purge_content ( $path )) {
                    if ($delete_self) {
                        if ($t = rmdir ( $path )) {
                            return $total + $t;
                        }
                    }
                    return $total;
                } else if ($delete_self) {
                    return rmdir ( $path );
                }
                return false;
            } else {
                return unlink ( $path );
            }
        }
    }


    /**
     * If the folder does not exist creates it (recursively)
     * @param string $path Path to folder/file to be created
     * @param mixed $content Content to be written to the file
     * @param string $writeFileMode Mode to write the file
     * @return bool Returns true if file/folder created
     */
    public function create($path, $content = null, $mode = 'w+') {
        // create file if content not empty
        if (! empty ( $content )) {
            if (strpos ( $path, '/' ) !== false || strpos ( $path, '\\' ) !== false) {
                $path = str_replace ( '\\', '/', $path );
                $filename = $path;
                $path = explode ( '/', $path );
                array_splice ( $path, sizeof ( $path ) - 1 );

                $path = implode ( '/', $path );
                if ($path [strlen ( $path ) - 1] != '/') {
                    $path .= '/';
                }
            } else {
                $filename = $path;
            }

            if ($filename != $path && ! is_file ( $path )) {
                mkdir ( $path, $this->chmod, true );
            }
            $fp = fopen ( $filename, $mode );
            $rs = fwrite ( $fp, $content );
            fclose ( $fp );
            return ($rs > 0);
        } else {
            if (! is_file ( $path )) {
                return mkdir ( $path, $this->chmod, true );
            } else {
                return true;
            }
        }
    }

    /**
     * Move/rename a file/folder
     * @param string $from Original path of the folder/file
     * @param string $to Destination path of the folder/file
     * @return bool Returns true if file/folder created
     */
    public function move($from, $to) {
        if (strpos ( $to, '/' ) !== false || strpos ( $to, '\\' ) !== false) {
            $path = str_replace ( '\\', '/', $to );
            $path = explode ( '/', $path );
            array_splice ( $path, sizeof ( $path ) - 1 );

            $path = implode ( '/', $path );
            if ($path [strlen ( $path ) - 1] != '/') {
                $path .= '/';
            }
            if (! is_file ( $path )) {
                mkdir ( $path, $this->chmod, true );
            }
        }
        return rename ( $from, $to );
    }

    /**
     * Copy a file/folder to a destination
     * @param string $from Original path of the folder/file
     * @param string $to Destination path of the folder/file
     * @param array $exclude An array of file and folder names to be excluded from a copy
     * @return bool|int Returns true if file copied. If $from is a folder, returns the number of files/folders copied
     */
    public function copy($from, $to, $exclude = array()) {
        if (is_dir ( $from )) {
            if ($to [strlen ( $to ) - 1] != '/' && $to [strlen ( $to ) - 1] != '\\') {
                $to .= DS;
                $to = str_replace ( '\\', '/', $to );
            }
            if ($from [strlen ( $from ) - 1] != '/' && $from [strlen ( $from ) - 1] != '\\') {
                $from .= DS;
                $from = str_replace ( '\\', '/', $from );
            }
            if (! is_file ( $to )) {
                mkdir ( $to, $this->chmod, true );
            }
            return $this->copy_content ( $from, $to, $exclude );
        } else {
            if (strpos ( $to, '/' ) !== false || strpos ( $to, '\\' ) !== false) {
                $path = str_replace ( '\\', '/', $to );
                $path = explode ( '/', $path );
                array_splice ( $path, sizeof ( $path ) - 1 );

                $path = implode ( '/', $path );
                if ($path [strlen ( $path ) - 1] != '/') {
                    $path .= '/';
                }

                if (! is_file ( $path )) {
                    mkdir ( $path, $this->chmod, true );
                }
            }
            return copy ( $from, $to );
        }
    }

    /**
     * Copy contents in a folder recursively
     * @param string $dir Path of the folder to be copied
     * @param string $to Destination path
     * @param array $exclude An array of file and folder names to be excluded from a copy
     * @return int Total of files/folders copied
     */
    public function copy_content($dir, $to, $exclude = array()) {
        $total_copy = 0;
        $handle = opendir ( $dir );
        while ( false !== ($file = readdir ( $handle )) ) {
            if ($file != '.' && $file != '..' && ! in_array ( $file, $exclude )) {
                if (is_dir ( $dir . $file )) {
                    if (! is_file ( $to . $file )) {
                        mkdir ( $to . $file, $this->chmod, true );
                    }
                    $total_copy += $this->copy_content ( $dir . $file . '/', $to . $file . '/', $exclude );
                } else {
                    if (copy ( $dir . $file, $to . $file )){
                        $total_copy ++;
                    }
                }
            }
        }
        closedir ( $handle );
        return $total_copy;
    }


    /**
     * Get the space used up by a folder recursively.
     * @param string $dir Directory path.
     * @param string $unit Case insensitive units: B, KB, MB, GB or TB
     * @param int $precision
     * @return float total space used up by the folder (KB)
     */
    public function get_size($dir, $unit = 'KB', $precision = 2) {
        if (! is_dir ( $dir )) {
            return filesize ( $dir );
        }
        $dir = str_replace ( '\\', '/', $dir );
        if ($dir [strlen ( $dir ) - 1] != '/') {
            $dir .= '/';
        }

        $total_size = 0;
        $handle = opendir ( $dir );
        while ( false !== ($file = readdir ( $handle )) ) {
            if ($file != '.' && $file != '..') {
                if (is_dir ( $dir . $file )) {
                    $total_size += $this->get_size ( $dir . $file, false );
                } else {
                    $total_size += filesize ( $dir . $file );
                }
            }
        }
        closedir ( $handle );
        return self::format_bytes ( $total_size, $unit, $precision );
    }

    /**
     * Convert bytes into KB, MB, GB or TB.
     * @param int $bytes
     * @param string $unit Case insensitive units: B, KB, MB, GB or TB OR false if not to format the size
     * @param int $precision
     * @return float
     */
    public static function format_bytes($bytes, $unit = 'KB', $precision = 2) {
        if ($unit === false) {
            return $bytes;
        }
        $unit = strtoupper ( $unit );
        $unitPow = array (
                'B' => 0,
                'KB' => 1,
                'MB' => 2,
                'GB' => 3,
                'TB' => 4
        );
        $bytes /= pow ( 1024, $unitPow [$unit] );
        return round ( $bytes, $precision );
    }

    /**
     * Get a list of files with its path in a directory (recursively)
     * @param string $path
     * @return array
     */
    public static function get_file_path_list($path) {
        $path = str_replace ( '\\', '/', $path );
        if ($path [strlen ( $path ) - 1] != '/') {
            $path .= '/';
        }
        $handle = opendir ( $path );
        $rs = array ();
        while ( false !== ($file = readdir ( $handle )) ) {
            if ($file != '.' && $file != '..' && $file != '.svn') {
                if (is_dir ( $path . $file ) === true) {
                    $rs = array_merge ( $rs, self::get_file_path_list ( $path . $file . '/' ) );
                } else {
                    $rs [$file] = $path . $file;
                }
            }
        }
        closedir ( $handle );
        return $rs;
    }

    /**
     * Get a list of folders or files or both in a given path.
     *
     * @param string $path Path to get the list of files/folders
     * @param string $listOnly List only files or folders. Use value DooFile::LIST_FILE or DooFile::LIST_FOLDER
     * @param string $unit Unit for the size of files. Case insensitive units: B, KB, MB, GB or TB
     * @param int $precision Number of decimal digits to round the file size to
     * @return array Returns an assoc array with keys: name(file name), path(full path to file/folder), folder(boolean), extension, type, size(KB)
     */
    public function get_list($path, $list_only = null, $unit = 'B', $precision = 2) {
        $path = str_replace ( '\\', '/', $path );
        if ($path [strlen ( $path ) - 1] != '/') {
            $path .= '/';
        }
        $filetype = array ( '.', '..' );
        $name = array ();
        $dir = opendir ( $path );
        if ($dir === false) {
            return false;
        }
        while ( $file = readdir ( $dir ) ) {
            if (! in_array ( substr ( $file, - 1, strlen ( $file ) ), $filetype ) && ! in_array ( substr ( $file, - 2, strlen ( $file ) ), $filetype )) {
                $name [] = $path . $file;
            }
        }
        closedir ( $dir );
        if (count ( $name ) == 0) {
            return false;
        }
        $file_info = array ();
        foreach ( $name as $key => $val ) {
            if ($list_only == File::LIST_FILE) {
                if (is_dir ( $val )) {
                    continue;
                }
            }
            if ($list_only == File::LIST_FOLDER) {
                if (! is_dir ( $val )) {
                    continue;
                }
            }
            $filename = basename ( $val );
            $ext = $this->get_file_extension_from_path ( $val, true );
            if (! is_dir ( $val )) {
                $file_info [] = array (
                        'name' => $filename,
                        'path' => $val,
                        'folder' => is_dir ( $val ),
                        'extension' => $ext,
                        'type' => $this->mime_content_type_ex ( $val ),
                        'size' => $this->format_bytes ( filesize ( $val ), $unit, $precision )
                );
            } else {
                $file_info [] = array (
                        'name' => $filename,
                        'path' => $val,
                        'folder' => is_dir ( $val )
                );
            }
        }
        return $file_info;
    }

    /**
     * check file's mine
     *
     * @param unknown $filename
     * @return Ambigous <string>|unknown|string
     */
    public function mime_content_type_ex($filename) {
        $mime_types = array (
                'txt' => 'text/plain',
                'htm' => 'text/html',
                'html' => 'text/html',
                'php' => 'text/html',
                'css' => 'text/css',
                'js' => 'application/javascript',
                'json' => 'application/json',
                'xml' => 'application/xml',
                'swf' => 'application/x-shockwave-flash',
                'flv' => 'video/x-flv',
                'sql' => 'text/x-sql',

                // images
                'png' => 'image/png',
                'jpe' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'jpg' => 'image/jpeg',
                'gif' => 'image/gif',
                'bmp' => 'image/bmp',
                'ico' => 'image/vnd.microsoft.icon',
                'tiff' => 'image/tiff',
                'tif' => 'image/tiff',
                'svg' => 'image/svg+xml',
                'svgz' => 'image/svg+xml',

                // archives
                'zip' => 'application/zip',
                'rar' => 'application/x-rar-compressed',
                'exe' => 'application/x-msdownload',
                'msi' => 'application/x-msdownload',
                'cab' => 'application/vnd.ms-cab-compressed',

                // audio/video
                'mp3' => 'audio/mpeg',
                'qt' => 'video/quicktime',
                'mov' => 'video/quicktime',

                // adobe
                'pdf' => 'application/pdf',
                'psd' => 'image/vnd.adobe.photoshop',
                'ai' => 'application/postscript',
                'eps' => 'application/postscript',
                'ps' => 'application/postscript',

                // ms office
                'doc' => 'application/msword',
                'rtf' => 'application/rtf',
                'xls' => 'application/vnd.ms-excel',
                'ppt' => 'application/vnd.ms-powerpoint',

                // open office
                'odt' => 'application/vnd.oasis.opendocument.text',
                'ods' => 'application/vnd.oasis.opendocument.spreadsheet'
        );

        $f = explode ( '.', $filename );
        $ext = strtolower ( array_pop ( $f ) );
        if (array_key_exists ( $ext, $mime_types )) {
            return $mime_types [$ext];
        } elseif (function_exists ( 'finfo_open' )) {
            $finfo = finfo_open ( FILEINFO_MIME );
            $mimetype = finfo_file ( $finfo, $filename );
            finfo_close ( $finfo );
            return $mimetype;
        } else {
            return 'application/octet-stream';
        }
    }


    /**
     * Save the uploaded file(s) in HTTP File Upload variables
     *
     * @param string $uploadPath Path to save the uploaded file(s)
     * @param string $filename The file input field name in $_FILES HTTP File Upload variables
     * @param string $rename Rename the uploaded file (without extension)
     * @return string|array The file name of the uploaded file.
     */
    public function upload($upload_path, $name) {
        $file = $_FILES[$name];
        if (empty($file['tmp_name'])) {
            return array('code' => -1, 'data' => '上传文件无效');
        }
        // 检查有没有上传目录，没有自动创建
        if (! is_file ( $upload_path )) {
            $this->create ( $upload_path );
        }
        $return = array();
        if (!is_array($file['name'])) { // 不是数组
            $tmp = $this->do_upload($file, $upload_path);
            if ($tmp['code'] != 1) { // 上传不成功
                return array('code' => $tmp['code'], 'data' => $tmp['code']);
            }
            $return[] = $tmp['data'];
        } else {
            foreach ($file['tmp_name'] as $k => $tmp_name) {
                $file_arr = array(
                        'tmp_name' => $tmp_name,
                        'name' => $file['name'][$k],
                        'type' => $file['type'][$k],
                        'error' => $file['error'][$k],
                        'size' => $file['size'][$k]
                );
                $tmp = $this->do_upload($file_arr, $upload_path);
                if ($tmp['code'] != 1) { // 上传不成功
                    return array('code' => $tmp['code'], 'data' => $tmp['code']);
                }
                $return[] = $tmp['data'];
            }
        }
        return array('code' => 1, 'data' => $return);
    }

    /**
     * 确定上传
     * @param unknown $upload_arr
     */
    private function do_upload ($file_arr, $path){
        if ($file_arr ['error'] != UPLOAD_ERR_OK) {
            return array ( 'code' => -2, 'data' => '上传失败' );
        }
        $return = array (
                'ext' => $this->get_file_extension_from_path ( $file_arr ['name'], true ),
                'md5' => md5_file ( $file_arr ['tmp_name'] ),
                'sha1' => sha1_file ( $file_arr ['tmp_name'] ),
                'name' => $file_arr ['name'],
                'type' => $file_arr ['type'],
                'size' => $file_arr ['size']
        );
        // 检查扩展名
        if ($this->upload_ext != null && !in_array($return ['ext'], $this->upload_ext)) {
            return array('code' => -3, 'data' => '不允许上传的文件扩展名');
        }
        // 检查文件mine
        if ($this->upload_mine != null && !in_array($return['type'], $this->upload_mine)) {
            return array('code' => -4, 'data' => '文件mine类型不合格');
        }
        // 检查文件大小
        if ($this->upload_size > 0 && $this->upload_size < $return['size']){
            return array('code' => -5, 'data' => '上传文件太大');
        }
        // 对图像文件进行严格检测
        if (in_array ( $return ['ext'], array ( 'gif', 'jpg', 'jpeg', 'bmp', 'png', 'swf' ) )) {
            $imginfo = getimagesize ( $file_arr ['tmp_name'] );
            if (empty ( $imginfo ) || ($return ['ext'] == 'gif' && empty ( $imginfo ['bits'] ))) {
                return array (  'code' => - 3, 'data' => '非法图像' );
            }else{
                $return['width'] = $imginfo['0'];
                $return['height'] = $imginfo['1'];
            }
        }
        $return ['save_name'] = time () . '-' . mt_rand ( 1000, 9999 ) . '.' . $return ['ext'];
        if (move_uploaded_file ( $file_arr ['tmp_name'], $path . $return ['save_name'] )) {
            $return['save_path'] = $path;
            return array('code' => 1, 'data' => $return );
        } else {
            return array('code' => -4, 'data' => '上传失败');
        }
    }

    /**
     * Get the uploaded files' type
     *
     * @param string $filename The file field name in $_FILES HTTP File Upload variables
     * @return string|array The image format type of the uploaded image.
     */
    public function get_upload_format($filename) {
        if (! empty ( $_FILES [$filename] )) {
            $type = $_FILES [$filename] ['type'];
            if (is_array ( $type ) === False) {
                if (! empty ( $type )) {
                    return $type;
                }
            } else {
                $typelist = array ();
                foreach ( $type as $t ) {
                    $typelist [] = $t;
                }
                return $typelist;
            }
        }
    }

    /**
     * Checks if file mime type of the uploaded file(s) is in the allowed list
     *
     * @param string $filename The file input field name in $_FILES HTTP File Upload variables
     * @param array $allowType Allowed file type.
     * @return bool Returns true if file mime type is in the allowed list.
     */
    public function check_file_type($filename, $allow_type) {
        $type = $this->get_upload_format ( $filename );
        if (is_array ( $type ) === False) {
            return in_array ( $type, $allow_type );
        } else {
            foreach ( $type as $t ) {
                if ($t === Null || $t === '')
                    continue;
                if (! in_array ( $t, $allow_type )) {
                    return false;
                }
            }
            return true;
        }
    }

    /**
     * Checks if file extension of the uploaded file(s) is in the allowed list.
     *
     * @param string $filename The file input field name in $_FILES HTTP File Upload variables
     * @param array $allowExt Allowed file extensions.
     * @return bool Returns true if file extension is in the allowed list.
     */
    public function check_file_extension($filename, $allow_ext) {
        if (! empty ( $_FILES [$filename] )) {
            $name = $_FILES [$filename] ['name'];
            if (is_array ( $name ) === false) {
                $ext = $this->get_file_extension_from_path ( $name );
                return in_array ( $ext, $allow_ext );
            } else {
                foreach ( $name as $nm ) {
                    $ext = $this->get_file_extension_from_path ( $nm );
                    if (! in_array ( $ext, $allow_ext )) {
                        return false;
                    }
                }
                return true;
            }
        }
    }

    /**
     * Checks if file size does not exceed the max file size allowed.
     *
     * @param string $filename The file input field name in $_FILES HTTP File Upload variables
     * @param int $maxSize Allowed max file size in kilo bytes.
     * @return bool Returns true if file size does not exceed the max file size allowed.
     */
    public function check_file_size($filename, $max_size) {
        if (! empty ( $_FILES [$filename] )) {
            $size = $_FILES [$filename] ['size'];
            if (is_array ( $size ) === False) {
                if (($size / 1024) > $max_size) {
                    return false;
                }
            } else {
                foreach ( $size as $s ) {
                    if (($s / 1024) > $max_size) {
                        return false;
                    }
                }
            }
            return true;
        }
    }

    /**
     * Reads the contents of a given file
     * @param string $fullFilePath Full path to file whose contents should be read
     * @return string|bool Returns file contents or false if file not found
     */
    public function read_file_contents($full_file_path, $flags = 0, resource $context = null, $offset = -1, $maxlen = null) {
        if (is_file ( $full_file_path )) {
            if ($maxlen !== null) {
                return file_get_contents ( $full_file_path, $flags, $context, $offset, $maxlen );
            } else {
                return file_get_contents ( $full_file_path, $flags, $context, $offset );
            }
        } else {
            return false;
        }
    }

    /**
     * Extracts the file extension (characters following last '.' in string) from a file path.
     * @param string $filePath Full path or filename including extension to be extracted
     * @param bool $toLowerCase Should the extension be converted to lower case ?
     * @return string|Returns the file extension (characters following last . in string)
     */
    public function get_file_extension_from_path($path, $toLower_case = false) {
        $ext = substr ( $path, strrpos ( $path, '.' ) + 1 );
        return ($toLower_case == true) ? strtolower ( $ext ) : $ext;
    }
}

/* End of file File.php */