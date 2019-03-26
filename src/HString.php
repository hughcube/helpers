<?php

namespace HughCube\Helpers;

class HString
{
    /**
     * 改变字符的编码;
     *
     * @param string|array $contents 需要转行的数据;
     * @param string $from 原始编码,('gbk','utf-8');
     * @param string $to 目标编码,('gbk','utf-8');
     *
     * @return string;
     */
    public static function convEncoding($contents, $from = 'gbk', $to = 'utf-8')
    {
        $from = strtoupper($from);
        $to = strtoupper($to);

        $from = $from == 'UTF8' ? 'utf-8' : $from;
        $to = $to == 'UTF8' ? 'utf-8' : $to;

        if ($from === $to
            || empty($contents)
            || (is_scalar($contents) && !is_string($contents))
        ){
            return $contents;
        }

        if (function_exists('mb_convert_encoding')){
            return mb_convert_encoding($contents, $to, $from);
        }else{
            return iconv($from, $to, $contents);
        }
    }

    /**
     * 函数msubstr,实现中文截取字符串;
     *
     * @param string $string 需要截取的字符串;
     * @param int $length 截取字符的长度,按照一个汉字的长度算作一个字符;
     * @param int $start 从那里开始截取;
     * @param string $suffix 截取字符后加上的后缀,默认为@...;
     * @param string $charset 字符的编码,默认为 utf-8, 可选范围utf-8, GBK;
     *
     * @return string;
     */
    public static function msubstr($str, $start = 0, $length = null, $suffix = '...', $charset = 'utf-8')
    {
        $length = null === $length ? strlen($length) : $length;
        $charLen = in_array($charset, ['utf-8', 'UTF8']) ? 3 : 2;

        // 小于指定长度，直接返回
        if (strlen($str) <= ($length * $charLen)){
            return $str;
        }

        if (function_exists('mb_substr')){
            $slice = mb_substr($str, $start, $length, $charset);
        }elseif (function_exists('iconv_substr')){
            $slice = iconv_substr($str, $start, $length, $charset);
        }else{
            $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
            $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
            $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
            $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
            preg_match_all($re[$charset], $str, $match);
            $slice = join("", array_slice($match[0], $start, $length));
        }

        return $slice . $suffix;
    }

    /**
     * 文本关键词高亮
     *
     * @param string $text 文本
     * @param array $keywords 关键词
     * @return string
     */
    public static function keywordHighlight($text, array $keywords, $template = '<span style="color: red; ">{{keyword}}</span>')
    {
        if (!is_string($text) || empty($text) || empty($keywords)){
            return $text;
        }

        $from = [];
        foreach($keywords as $keyword){
            $from[$keyword] = strtr($template, ['{{keyword}}' => $keyword]);
        }

        return strtr($text, $from);
    }

    /**
     * 字符串的字节长度
     *
     * @param string $string
     * @return int
     */
    public static function byteLength($string)
    {
        return mb_strlen($string, '8bit');
    }

    /**
     * 字符串截取字节长度
     *
     * @param string $string
     * @param int $start
     * @param int|null $length
     * @return string
     */
    public static function byteSubstr($string, $start, $length = null)
    {
        return mb_substr(
            $string,
            $start,
            $length === null ? mb_strlen($string, '8bit') : $length,
            '8bit'
        );
    }

    /**
     * 查看字符串是否以另一字符串开始
     *
     * @param $string
     * @param $with
     * @param bool $caseSensitive
     * @param string $encoding
     * @return bool
     */
    public static function startsWith($string, $with, $caseSensitive = true, $encoding = 'UTF-8')
    {
        if (!$bytes = static::byteLength($with)){
            return true;
        }
        if ($caseSensitive){
            return strncmp($string, $with, $bytes) === 0;

        }

        return mb_strtolower(mb_substr($string, 0, $bytes, '8bit'), $encoding) === mb_strtolower($with, $encoding);
    }

    /**
     * 查看字符串是否以另一字符串结尾
     *
     * @param string $string
     * @param string $with
     * @param bool $caseSensitive
     * @param string $encoding
     * @return bool
     */
    public static function endsWith($string, $with, $caseSensitive = true, $encoding = 'UTF-8')
    {
        if (!$bytes = static::byteLength($with)){
            return true;
        }
        if ($caseSensitive){
            // Warning check, see http://php.net/manual/en/function.substr-compare.php#refsect1-function.substr-compare-returnvalues
            if (static::byteLength($string) < $bytes){
                return false;
            }

            return substr_compare($string, $with, -$bytes, $bytes) === 0;
        }

        return mb_strtolower(mb_substr($string, -$bytes, mb_strlen($string, '8bit'), '8bit'), $encoding) === mb_strtolower($with, $encoding);
    }

    /**
     * 分割字符串
     *
     * @param $string
     * @param string $delimiter
     * @param bool $trim
     * @param bool $skipEmpty
     * @return array
     */
    public static function explode($string, $delimiter = ',', $trim = true, $skipEmpty = false)
    {
        $result = explode($delimiter, $string);
        if ($trim){
            if ($trim === true){
                $trim = 'trim';
            }elseif (!is_callable($trim)){
                $trim = function ($v) use ($trim){
                    return trim($v, $trim);
                };
            }
            $result = array_map($trim, $result);
        }
        if ($skipEmpty){
            // Wrapped with array_values to make array keys sequential after empty values removing
            $result = array_values(array_filter($result, function ($value){
                return $value !== '';
            }));
        }

        return $result;
    }

    /**
     * 统计单词数
     *
     * @param string $string
     * @return int
     */
    public static function countWords($string)
    {
        return count(preg_split('/\s+/u', $string, null, PREG_SPLIT_NO_EMPTY));
    }

    /**
     * 获取指定位置的字符, 不能支持负数位置
     *
     * @param string $string
     * @param int $index
     * @return null|string
     */
    public static function offsetGet($string, $index)
    {
        $char = substr($string, $index, 1);

        return false === $char ? null : $char;
    }

    /**
     * 过滤不完整的UTF8字符，UTF8的合法字符范围为：
     *
     * 一字节字符：0x00-0x7F
     * 二字节字符：0xC0-0xDF 0x80-0xBF
     * 三字节字符：0xE0-0xEF 0x80-0xBF 0x80-0xBF
     * 四字节字符：0xF0-0xF7 0x80-0xBF 0x80-0xBF 0x80-0xBF
     *
     * @param string $string
     * @return null|string
     */
    public static function filterPartialUTF8($string)
    {
        $string = preg_replace("/[\\xC0-\\xDF](?=[\\x00-\\x7F\\xC0-\\xDF\\xE0-\\xEF\\xF0-\\xF7]|$)/", '', $string);
        $string = preg_replace("/[\\xE0-\\xEF][\\x80-\\xBF]{0,1}(?=[\\x00-\\x7F\\xC0-\\xDF\\xE0-\\xEF\\xF0-\\xF7]|$)/", '', $string);
        $string = preg_replace("/[\\xF0-\\xF7][\\x80-\\xBF]{0,2}(?=[\\x00-\\x7F\\xC0-\\xDF\\xE0-\\xEF\\xF0-\\xF7]|$)/", '', $string);

        return strval($string);
    }

    /**
     * 生成一个可以安全显示的手机号码
     *
     * @param $mobile
     * @return bool|null
     */
    public static function getSafeCnMobile($mobile, $start = 3, $length = 4, $replacement = '*')
    {
        if (!HCheck::isCnMobile($mobile)){
            return null;
        }

        $replacement = str_pad('', $length, $replacement);

        return substr_replace($mobile, $replacement, $start, $length);
    }

    /**
     * 比较两个版本的大小
     *
     * @param string $a 版本1
     * @param string $b 版本2
     * @param string|null $operator 比较运算符
     * @param integer|null $depth 比较的深度, null表示不限制
     * @return int
     * 0: 两个版本相等
     * 1: $a > $b
     * 2: $b < $a
     *
     * <、 lt、<=、 le、>、 gt、>=、 ge、==、 =、eq、 !=、<> 和 ne。
     */
    public static function versionCompare($a, $b, $operator = null, $compareDepth = null)
    {
        /**
         * 分割成数组
         */
        $a = explode(".", $a);
        $b = explode(".", $b);

        /**
         * 确定最大比较的深度
         */
        $maxDepth = max(count($a), count($b));
        $maxDepth = (null != $compareDepth && $maxDepth > $compareDepth) ? $compareDepth : $maxDepth;

        /**
         * 补全长度, 防止 1.0.1 < 1.0.1.0 的情况
         */
        $a = array_pad($a, $maxDepth, '0');
        $b = array_pad($b, $maxDepth, '0');

        /**
         * 截取长度, 只比较指定深度
         */
        $a = array_slice($a, 0, $maxDepth);
        $b = array_slice($b, 0, $maxDepth);

        /**
         * 重新拼接成字符串
         */
        $a = implode('.', $a);
        $b = implode('.', $b);

        return null === $operator ? version_compare($a, $b) : version_compare($a, $b, $operator);
    }
}
