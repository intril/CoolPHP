<?php

/**
 * CSV parse class
 * this parse the csv file to array
 *
 * @package     CoolPHP
 * @subpackage  setting
 * @category    helper
 * @author      Intril.Leng <jj.comeback@gmail.com>
 */
class CSV {

    /**
     * 分隔符
     * @var string
     */
    private $_delimiter = ',';

    /**
     * 界限符
     * @var string
     */
    private $_enclosure = '"';

    /**
     * 行结束符
     * @var string
     */
    private $_line_ending = "\r\n";

    /**
     * 配置参数
     * @param string $delimiter 分隔符
     * @param string $enclosure 界限符
     * @param string $line_ending 行结束符
     */
    public function config($delimiter, $enclosure, $line_ending) {
        $this->_delimiter = $delimiter;
        $this->_enclosure = $enclosure;
        $this->_line_ending = $line_ending;
    }

    /**
     * 在给定的字符串两边包裹指定的字符串
     * @param string $inner_str
     * @param string $outer_str
     * @return string
     */
    private function wrap_str($inner_str, $outer_str) {
        return $outer_str . $inner_str . $outer_str;
    }

    /**
     * 添加界限符
     * @param string $val
     * @param bool $force_val_to_string
     * @return string
     */
    private function add_enclosure($val, $force_val_to_string) {
        $val = str_replace($this->_enclosure, $this->_enclosure . $this->_enclosure, $val);
        if (strpos($val, $this->_delimiter) !== FALSE || strpos($val, $this->_enclosure) !== FALSE || strpos($val, "\r") !== FALSE || strpos($val, "\n") !== FALSE || $force_val_to_string) {
            return $this->wrap_str($val, $this->_enclosure);
        } else {
            return $val;
        }
    }

    /**
     * 移去界限符
     * @param string $val
     * @param bool $addslashes
     * @return string
     */
    private function remove_enclosure($val, $addslashes) {
        $val = preg_replace('/^' . preg_quote($this->_enclosure) . '|' . preg_quote($this->_enclosure) . '$/', '', $val);
        $val = str_replace($this->_enclosure . $this->_enclosure, $this->_enclosure, $val);
        if ($addslashes) {
            return addslashes($val);
        }
        return $val;
    }

    /**
     * 将给定的二维数组构造成CSV格式的字符串
     * @param array $row_arr
     * @param bool $handle_column_header
     * @param bool $force_val_to_string
     * @return string
     */
    public function encode($row_arr, $handle_column_header = TRUE, $force_val_to_string = FALSE) {
        $data_str = '';

        if ($handle_column_header) {
            // 处理字段名(取键名)
            foreach ($row_arr as $row) {
                $temp_arr = array();
                foreach ($row as $key => $val) {
                    $temp_arr[] = $this->add_enclosure($key, $force_val_to_string);
                }
                $data_str .= implode($this->_delimiter, $temp_arr) . $this->_line_ending;
                break;
            }
        }

        // 处理字段内容(取值)
        foreach ($row_arr as $row) {
            $temp_arr = array();
            foreach ($row as $val) {
                $temp_arr[] = $this->add_enclosure($val, $force_val_to_string);
            }
            $data_str .= implode($this->_delimiter, $temp_arr) . $this->_line_ending;
        }
        return $data_str;
    }

    /**
     * 将给定CSV格式的字符串还原到数组当中
     * @param string $data_str
     * @param bool $addslashes
     * @return array
     */
    public function decode($data_str, $addslashes = FALSE) {
        // 数据字节长度
        $data_length = strlen($data_str);
        // 行结束符字节长度
        $line_ending_length = strlen($this->_line_ending);
        // 分隔符字节长度
        $delimiter_length = strlen($this->_delimiter);
        // 当前位置
        $cur = 0;
        // 截取开始位置
        $start = 0;
        // 界限符个数
        $enclosure_count = 0;
        // 行号
        $row = 0;
        $data_arr = array();
        while ($cur < $data_length) {
            if ($data_str[$cur] === $this->_line_ending[0] && ($enclosure_count % 2 === 0)) {
                ++$cur;
                $cur2 = 1;
                $flag = TRUE;
                while ($cur < $data_length && $cur2 < $line_ending_length) {
                    if ($data_str[$cur] !== $this->_line_ending[$cur2]) {
                        $flag = FALSE;
                        break;
                    }
                    ++$cur;
                    ++$cur2;
                }
                if ($flag) {
                    $data_arr[$row][] = $this->remove_enclosure(substr($data_str, $start, $cur - $line_ending_length - $start), $addslashes);
                    $start = $cur;
                    $enclosure_count = 0;
                    ++$row;
                }
                continue;
            } else if ($data_str[$cur] === $this->_delimiter[0] && ($enclosure_count % 2 === 0)) {
                ++$cur;
                $cur3 = 1;
                $flag = TRUE;
                while ($cur < $data_length && $cur3 < $delimiter_length) {
                    if ($data_str[$cur] !== $this->_delimiter[$cur3]) {
                        $flag = FALSE;
                        break;
                    }
                    ++$cur;
                    ++$cur3;
                }
                if ($flag) {
                    $data_arr[$row][] = $this->remove_enclosure(substr($data_str, $start, $cur - $delimiter_length - $start), $addslashes);
                    $start = $cur;
                    $enclosure_count = 0;
                }
                continue;
            } else if ($data_str[$cur] === $this->_enclosure) {
                ++$enclosure_count;
            }
            ++$cur;
        }
        return $data_arr;
    }

}
