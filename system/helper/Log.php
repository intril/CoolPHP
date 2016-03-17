<?php

/**
 * Log class
 * the debug and write file class
 *
 * @package     CoolPHP
 * @subpackage  setting
 * @category    helper
 * @author      Intril.Leng <jj.comeback@gmail.com>
 */
class Log {

    /**
     * Log/Profile rotate file size in KB
     *
     * @var int
     */
    protected $rotate_size = 0;

    /**
     * logs content array
     *
     * @var array
     */
    private $_logs = array();

    /**
     * if open debug model
     * @var Boolean
     */
    private $_debug;

    /**
     * 初始化
     * @param string $debug
     */
    public function __construct ( $debug = true ) {
        $this->_debug = $debug;
    }

    /**
     * Set the Log rotate file size in KB
     *
     * @param int $size File size in KB
     */
    public function rotate_file ( $size ) {
        $this->rotate_size = $size * 1024;
    }

    /**
     * Turn on/off debug mode for CooLog
     * Traces will only be logged in debug mode.
     *
     * @param bool $enable
     */
    public function debug ( $enable ) {
        $this->_debug = $enable;
    }

    /**
     * Writes the log messages into a file.
     *
     * @param string $filename File name for log.
     * @param bool   $xml      Whether to write as plain text or XML file.
     */
    public function write ( $msg, $filename = 'logger.log', $xml = FALSE ) {
        $this->_logs [] = array(
            $msg,
            microtime( true )
        );
        $this->write_to_file( $this->show_logs( $xml ), $filename );
    }

    /**
     * Return a neatly formatted XML log view, filtered by level or category.
     *
     * @return string formatted XML log view
     */
    public function show_logs ( $xml = FALSE ) {
        $msg = "\n<!-- Generate on " . date( 'Y-m-d H:i:s', time() ) . " -->\n";
        $keep = $msg;
        foreach ( $this->_logs as $k => $p ) {
            if ( $p [0] != '' ) {
                $msg .= $this->format( $p [0], $p [1], $xml );
            }
        }
        if ( $keep != $msg ) {
            return $msg;
        } else {
            return;
        }
    }

    /**
     * Write to file.
     * If rotate file size is set, logs and profiles are automatically rotate when the file size is reached.
     *
     * @param string $data     Data string to be logged
     * @param string $filename File name for the log/profile
     */
    protected function write_to_file ( $data, $filename ) {
        // only write to file if there's a record
        if ( $data != NULL ) {
            $mode = 'a+';
            if ( isset ( Cool::$GC['log_path'] ) ) {
                $filename = Cool::$GC['log_path'] . DS . $filename;
            }

            if ( $this->rotate_size != 0 ) {
                if ( is_file( $filename ) && filesize( $filename ) > $this->rotate_size ) {
                    $mode = 'w+';
                }
            }
            Cool::load_sys( 'File' );
            $file = new File ( 0777 );
            $file->create( $filename, $data, $mode );
        }
    }

    /**
     * Format a single log message
     * Example formatted message:
     * <code>2009-6-22 15:21:30 User johnny has logined from 60.30.142.85</code>
     * <code>2009/6/22 15:21:30 User johnny has logined from 60.30.142.85</code>
     *
     * @param string $msg  Log message
     * @param float  $time Time used in second
     * @return string A formatted log message
     */
    protected function format ( $msg, $time, $xml ) {
        if ( $xml == true ) {
            return "<log><date>" . date( 'Y-m-d H:i:s', $time ) . "</date><access_uri><![CDATA[{$_SERVER['REQUEST_URI']}]]></access_uri><msg><![CDATA[$msg]]></msg></log>\n";
        } else {
            $return  = "----------------------------REQUEST_URI-----------------------------\n";
            $return .= "{$_SERVER['REQUEST_URI']}\n";
            $return .= "----------------------------LOG_DATA--------------------------------\n";
            $return .= $msg."\n";
            return $return;
        }
    }

    /**
     * Returns the memory usage of the current application.
     * This method relies on the PHP function memory_get_usage().
     * If it is not available, the method will attempt to use OS programs
     * to determine the memory usage. A value 0 will be returned if the
     * memory usage can still not be determined.
     *
     * @return integer memory usage of the application (in bytes).
     */
    public function memory_used () {
        // might be disabled
        if ( function_exists( 'memory_get_usage' ) ) {
            return memory_get_usage();
        } else {
            $output = array();
            if ( strncmp( PHP_OS, 'WIN', 3 ) === 0 ) {
                exec( 'tasklist /FI "PID eq ' . getmypid() . '" /FO LIST', $output );
                return isset ( $output [5] ) ? preg_replace( '/[\D]/', '', $output [5] ) * 1024 : 0;
            } else {
                $pid = getmypid();
                exec( "ps -eo%mem,rss,pid | grep $pid", $output );
                $output = explode( "  ", $output [0] );
                return isset ( $output [1] ) ? $output [1] * 1024 : 0;
            }
        }
    }
}

/* End of file Log.php */