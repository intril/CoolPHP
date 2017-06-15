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

    /**
     * 允许上传的文件大小<M>
     * @var int
     */
    public $upload_size = 30;

    /**
     * 允许上传的后缀名
     * @var array
     */
    public $upload_ext = array();

    /**
     * 允话上传的文件mine
     * @var array
     */
    public $upload_mime = array();

    /**
     * 权限
     * @var string
     */
    public $chmod;

    /**
     * 文件的Mime列表
     * @var array
     */
    private $mime = array (
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

    public function __construct($upload_conf = array(), $chmod = 0777) {
        $this->upload_ext = isset($upload_conf['ext']) ? $upload_conf['ext'] : array();
        $this->upload_mime = isset($upload_conf['mine']) ? $upload_conf['mine'] : array();
        $this->upload_size = isset($upload_conf['size']) ? intval($upload_conf['size']) : 0;
        $this->chmod = $chmod;
    }

    /**
     * Delete a folder (and all files and folders below it)
     * @param string $path Path to folder to be deleted
     * @return int|bool Returns the total of deleted files/folder. Returns false if delete failed
     */
    public function delete ( $path ) {
        $operation = dir ( $path );
        while ( false != ($item = $operation->read ()) ) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            if (is_dir ( $operation->path . '/' . $item )) {
                $this->delete ( $operation->path . '/' . $item );
                rmdir ( $operation->path . '/' . $item );
            } else {
                unlink ( $operation->path . '/' . $item );
            }
        }
    }

    /**
     * Move/rename a file/folder
     * @param string $from Original path of the folder/file
     * @param string $to Destination path of the folder/file
     * @return bool Returns true if file/folder created
     */
    public function move ( $from, $to ) {
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
     * Get the space used up by a folder recursively.
     * @param string $dir Directory path.
     * @param string $unit Case insensitive units: B, KB, MB, GB or TB
     * @param int $precision
     * @return float total space used up by the folder (KB)
     */
    public function size( $dir, $unit = 'KB', $precision = 2) {
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
                    $total_size += $this->size ( $dir . $file, false );
                } else {
                    $total_size += filesize ( $dir . $file );
                }
            }
        }
        closedir ( $handle );
        return $this->format_bytes ( $total_size, $unit, $precision );
    }

    /**
     * Convert bytes into KB, MB, GB or TB.
     * @param int $bytes
     * @param string $unit Case insensitive units: B, KB, MB, GB or TB OR false if not to format the size
     * @param int $precision
     * @return float
     */
    public function format_bytes($bytes, $unit = 'KB', $precision = 2) {
        if ($unit === false) {
            return $bytes;
        }
        $unit = strtoupper ( $unit );
        $unitPow = array ( 'B' => 0, 'KB' => 1, 'MB' => 2, 'GB' => 3, 'TB' => 4 );
        $bytes /= pow ( 1024, $unitPow [$unit] );
        return round ( $bytes, $precision );
    }

    /**
     * Get a list of files with its path in a directory (recursively)
     * @param string $path
     * @return array
     */
    public function lists ( $path ) {
        $path = str_replace ( '\\', '/', $path );
        if ($path [strlen ( $path ) - 1] != '/') {
            $path .= '/';
        }
        $handle = opendir ( $path );
        $rs = array ();
        while ( false !== ($file = readdir ( $handle )) ) {
            if ($file != '.' && $file != '..' && $file != '.svn') {
                if (is_dir ( $path . $file ) === true) {
                    $rs = array_merge ( $rs, $this->lists ( $path . $file . '/' ) );
                } else {
                    $rs [$file] = $path . $file;
                }
            }
        }
        closedir ( $handle );
        return $rs;
    }

    /**
     * 获取文件的Mime值
     * @param unknown $filename
     * @return Ambigous <string>|unknown|string
     */
    public function mine ( $filename ) {
        $f = explode ( '.', $filename );
        $ext = strtolower ( array_pop ( $f ) );
        if (array_key_exists ( $ext, $this->mime )) {
            return $this->mime [$ext];
        } elseif (function_exists ( 'finfo_open' )) {
            $finfo = finfo_open ( FILEINFO_MIME );
            $mine = finfo_file ( $finfo, $filename );
            finfo_close ( $finfo );
            return $mine;
        } else {
            return 'application/octet-stream';
        }
    }

    /**
     * 上传图片文件
     * @param unknown $upload_path
     * @param unknown $name
     */
    public function upload_image ( $upload_path, $config = array() ) {
        $this->upload_ext = isset ( $config ['ext'] ) ? $config ['ext'] : array ( 'jpg', 'png', 'gif', 'jpeg' );
        $this->upload_mime = isset ( $config ['mine'] ) ? $config ['mine'] : array ( 'image/jpg', 'image/jpeg', 'image/png', 'image/gif' );
        $this->upload_size = isset ( $config ['size'] ) ? intval ( $config ['size'] ) : 5*1024*1024;
        $file_arr = $this->deal ( $_FILES );
        $return = array();
        foreach ( $file_arr as $key => $value ){
            $result = $this->do_upload ( $value, $upload_path );
            if ($result ['error'] !== 0) { // error
                return $result;
            }
            $return[] = $result['msg'];
        }
        return array( 'error' => 0, 'msg' => $return );
    }

    /**
     * Save the uploaded file(s) in HTTP File Upload variables
     *
     * @param string $uploadPath Path to save the uploaded file(s)
     * @param string $filename The file input field name in $_FILES HTTP File Upload variables
     * @param string $rename Rename the uploaded file (without extension)
     * @return string|array The file name of the uploaded file.
     */
    public function upload ( $upload_path, $files = NULL) {
        if ($files == null){
            $files = $_FILES;
        }
        $file_arr = $this->deal($files);
        $return = array();
        foreach ( $file_arr as $key => $value ){
            $result = $this->do_upload ( $value, $upload_path );
            if ($result ['error'] !== 0) { // error
                return $result;
            }
            $return[] = $result['msg'];
        }
        return array( 'error' => 0, 'msg' => $return );
    }

    /**
     * 确定上传
     * @param unknown $upload_arr
     */
    private function do_upload ($file_arr, $save_path){
        if (! is_dir ( $save_path )) { // 检查有没有上传目录，没有自动创建
            mkdir ( $save_path, $this->chmod, true );
        }
        if ($file_arr ['error'] != UPLOAD_ERR_OK) {
            return array( 'error' => 1, 'msg' => 'upload file fail' );
        }
        $return = array (
            'ext' => strtolower(pathinfo($file_arr ['name'], PATHINFO_EXTENSION)), 'md5' => md5_file ( $file_arr ['tmp_name'] ),
            'name' => $file_arr ['name'], 'type' => $file_arr ['type'], 'size' => $file_arr ['size']
        );
        // 检查扩展名,mine,文件大小
        if (!in_array($return ['ext'], $this->upload_ext) || !in_array($return['type'], $this->upload_mime) || $this->upload_size < $return['size']) {
            return array( 'error' => 2, 'msg' => 'file is invaild ext/mime/size' );
        }
        // 对图像文件进行严格检测
        if (in_array ( $return ['ext'], array ( 'gif', 'jpg', 'jpeg', 'bmp', 'png', 'swf' ) )) {
            $imginfo = getimagesize ( $file_arr ['tmp_name'] );
            if (empty ( $imginfo ) || ($return ['ext'] == 'gif' && empty ( $imginfo ['bits'] ))) {
                return array( 'error' => 2, 'msg' => 'file is not image' );
            }else{
                $return['width'] = $imginfo['0'];
                $return['height'] = $imginfo['1'];
            }
        }
        $return ['save_name'] = date('YmdHis') . '_' . mt_rand ( 10000, 99999 ) . '.' . $return ['ext'];
        if (move_uploaded_file ( $file_arr ['tmp_name'], $save_path . $return ['save_name'] )) {
            return array( 'error' => 0, 'msg' => $return );
        } else {
            return array( 'error' => 1, 'msg' => 'upload file fail' );
        }
    }

    /**
     * 转换上传文件数组变量为正确的方式
     *
     * @access private
     * @param array $files 上传的文件变量
     * @return array
     */
    private function deal ( $files ) {
        $file_arr = array ();
        $n = 0;
        foreach ( $files as $key => $file ) {
            if (is_array ( $file ['name'] )) {
                $keys = array_keys ( $file );
                $count = count ( $file ['name'] );
                for($i = 0; $i < $count; $i ++) {
                    $file_arr [$n] ['key'] = $key;
                    foreach ( $keys as $_key ) {
                        $file_arr [$n] [$_key] = $file [$_key] [$i];
                    }
                    $n ++;
                }
            } else {
                $file_arr[] = array_merge(array('key' => $key), $file);
                break;
            }
        }
        return $file_arr;
    }
}

/* End of file File.php */