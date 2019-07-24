<?php

namespace HughCube\Helpers;

class HCheck
{
    /**
     * 函数checkSAPI,验证php的运行方式;
     * aolserver、apache、 apache2filter、apache2handler、 caudium、cgi,
     * cgi-fcgi、cli、 continuity、embed、 isapi、litespeed、 milter、nsapi、 phttpd、
     * pi3web、roxen、 thttpd、tux、webjames;
     *
     * @param string $serverAPI 默认为cli,验证是否为命令行模式;
     * @return bool;
     */
    public static function sapi($serverAPI = 'cli')
    {
        return strtolower(php_sapi_name()) === strtolower($serverAPI);
    }

    /**
     * 是否在cli模式
     *
     * @return bool
     */
    public static function isCli()
    {
        return static::sapi('cli');
    }

    /**
     * 检查系统
     *
     * @param string $os
     * @return bool
     */
    public static function os($os = 'cli')
    {
        return strtolower(PHP_OS) === strtolower($os);
    }

    /**
     * 是否运行在linux系统
     *
     * @return bool
     */
    public static function isLinux()
    {
        return static::os('Linux');
    }

    /**
     * 是否mac系统
     *
     * @return bool
     */
    public static function isDarwin()
    {
        return static::os('Darwin');
    }

    /**
     * 是否Windows系统
     *
     * @return bool
     */
    public static function isWindows()
    {
        return DIRECTORY_SEPARATOR === '\\';
    }

    /**
     * 判断一个字符串的编码是否为UTF-8;
     *
     * @param string $string
     * @return bool
     */
    public static function isUtf8($string)
    {
        if (null === $string){
            return true;
        }

        $json = @json_encode([$string]);

        return '[null]' !== $json && !empty($json);

        // $temp1 = @iconv("GBK", "UTF-8", $string);
        // $temp2 = @iconv("UTF-8", "GBK", $temp1);
        // return $temp1 == $temp2;

        // return preg_match('%^(?:
        //     [\x09\x0A\x0D\x20-\x7E]              # ASCII
        //     | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
        //     | \xE0[\xA0-\xBF][\x80-\xBF]         # excluding overlongs
        //     | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
        //     | \xED[\x80-\x9F][\x80-\xBF]         # excluding surrogates
        //     | \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
        //     | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
        //     | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
        //     )*$%xs', $string);

        //return static::encoding($string, 'UTF-8');
    }

    /**
     * 判断一个字符串是否为八进制字符;
     *
     * @param string $string [必须] 需要判断的字符;
     * @return bool;
     */
    public static function isOctal($string)
    {
        return 0 < preg_match('/[^0-7]+/', $string);
    }

    /**
     * 判断一个字符串是否为二进制字符;
     *
     * @param string $string [必须] 需要判断的字符;
     * @return bool;
     */
    public static function isBinary($string)
    {
        return 0 < preg_match('/[^01]+/', $string);
    }

    /**
     * 判断一个字符串是否为十六进制字符;
     *
     * @param string $string [必须] 需要判断的字符;
     * @return bool;
     */
    public static function isHex($string)
    {
        return 0 < preg_match('/[^0-9a-f]+/i', $string);
    }

    /**
     * 判断一个字符串是否是数字和字母组成;
     *
     * @param string $string [必须] 需要判断的字符;
     * @return bool;
     */
    public static function isAlnum($string)
    {
        return ctype_alnum($string);
    }

    /**
     * 判断一个字符串是否是字母组成;
     *
     * @param string $string [必须] 需要判断的字符;
     * @return bool;
     */
    public static function isAlpha($string)
    {
        return ctype_alpha($string);
    }

    /**
     * 判断一个字符串是否是符合的命名规则;
     *
     * @param string string [必须] 需要判断的字符;
     * @return bool;
     */
    public static function isNaming($string)
    {
        return 0 < preg_match('/^[a-z\_][a-z1-9\_]*/i', $string);
    }

    /**
     * 判断一个字符串是否为空白符,空格制表符回车等都被视作为空白符,类是\n\r\t;
     *
     * @param string string [必须] 需要判断的字符;
     * @return bool;
     */
    public static function isWhitespace($string)
    {
        return ctype_cntrl($string);
    }

    /**
     * 判断是否为整数
     *
     * @param string $number
     * @param int|null $min
     * @param int|null $max
     * @return bool
     */
    public static function isDigit($number, $min = null, $max = null)
    {
        return static::isNumeric($number, null, $min, $max)
               && ctype_digit(strval($number));
    }

    /**
     * 判断是否是一个合法的邮箱;
     *
     * @param string  string [必须] 需要判断的字符;
     * @param bool [可选] 是否判断域名,该功能只能在linux下使用,默认不判断;
     * @return bool;
     */
    public static function isEmail($string, $isStrict = false)
    {
        $isStrict = static::isTrue($isStrict);

        $result = false !== filter_var($string, FILTER_VALIDATE_EMAIL);
        if ($result && $isStrict && function_exists('getmxrr')){
            list($prefix, $domain) = explode('@', $string);
            $result = getmxrr($domain, $mxhosts);
        }

        return $result;
    }

    /**
     * 判断是否是一个合法的手机号码;
     *
     * @param string string [必须] 需要判断的字符;
     * @return bool;
     */
    const CN_MOBILE_REGULAR_EXPRESSION = '/^(13[0-9]|14[0-9]|15[0-9]|17[0-9]|18[0-9]|19[0-9])\d{8}$/';

    public static function isCnMobile($string)
    {
        return 0 < preg_match(static::CN_MOBILE_REGULAR_EXPRESSION, $string);
    }

    /**
     * 判断是否是一个合法的固定电话号码;
     *
     * @param string string [必须] 需要判断的字符;
     * @return bool;
     */
    public static function isTel($string)
    {
        return 0 < preg_match('/^((\(\d{2,3}\))|(\d{3}\-))?(\(0\d{2,3}\)|0\d{2,3}-)?[1-9]\d{6,7}(\-\d{1,4})?$/', $string);
    }

    /**
     * 判断是否为一个QQ号码;
     *
     * @param string string [必须] 需要判断的字符;
     * @return bool;
     */
    public static function isQQ($string)
    {
        return static::isDigit($string, 10000, 9999999999);
    }

    /**
     * 判断是否为一个邮政编码;
     *
     * @param string string [必须] 需要判断的字符;
     * @return bool;
     */
    public static function isZipCode($string)
    {
        return static::isDigit($string, 100000, 999999);
    }

    /**
     * 判断是否为一个合法的IP地址
     *
     * @param string string [必须] 需要判断的字符;
     * @return bool;
     */
    public static function isIp($ip)
    {
        return false !== filter_var($ip, FILTER_VALIDATE_IP);
    }

    /**
     * 判断是否是ipv4
     *
     * @param string $ip
     * @return bool
     */
    public static function isIp4($ip)
    {
        return false !== filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    }

    /**
     * 判断是否是ipv6
     *
     * @param string $ip
     * @return bool
     */
    public static function isIp6($ip)
    {
        return false !== filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
    }

    /**
     * 判断是否是内网ip
     *
     * @param string $ip
     * @return bool
     */
    public static function isPrivateIp($ip)
    {
        return false === filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)
               && false !== filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE);
    }

    /**
     * 判断是否是公网ip
     *
     * @param string $ip
     * @return bool
     */
    public static function isPublicIp($ip)
    {
        return false !== static::isPrivateIp($ip);
    }

    /**
     * 判断是否为一个URL, 只有在200-299状态情况才算能够访问;
     *
     * @param string $url
     * @param bool $checkAccess 是否需要跑判断能否访问
     * @return bool
     */
    public static function isUrl($url, $checkAccess = false)
    {
        $checkAccess = static::isTrue($checkAccess);

        if (false === filter_var($url, FILTER_VALIDATE_URL)){
            return false;
        }

        if ($checkAccess && !is_array(get_headers($url))){
            return false;
        }

        return true;
    }

    /**
     * 函数ping,判断一个IP是否能够ping的通,该函数需要开启EXEC;
     *
     * @param string $ip [必选] 需要试探的IP地址;
     * @param int $timeout [可选] 超时时间,默认为4000,单位是毫秒,1000毫秒等于一秒;
     * @return bool;
     */
    public static function ping($host, $timeout = 4000)
    {
        if (!static::isDigit($timeout, 0)){
            return false;
        }

        // Create a package.
        $type = "\x08";
        $code = "\x00";
        $checksum = "\x00\x00";
        $identifier = "\x00\x00";
        $seqNumber = "\x00\x00";
        $package = $type . $code . $checksum . $identifier . $seqNumber . 'Ping';

        $calculateChecksumCallable = function ($data){
            $data = strlen($data) % 2 ? ($data . "\x00") : $data;
            $bit = unpack('n*', $data);
            $sum = array_sum($bit);
            while($sum >> 16){
                $sum = ($sum >> 16) + ($sum & 0xffff);
            }

            return pack('n*', ~$sum);
        };

        $checksum = $calculateChecksumCallable($package);
        $package = $type . $code . $checksum . $identifier . $seqNumber . 'Ping';

        $latency = false;
        if (false !== ($socket = @socket_create(AF_INET, SOCK_RAW, 1))){
            socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 10, 'usec' => 0]);
            @socket_connect($socket, $host, null);
            $start = HDate::microtime();
            @socket_send($socket, $package, strlen($package), 0);
            if (false !== socket_read($socket, 255)){
                $latency = HDate::microtime() - $start;
                $latency = round($latency / 1000);
            }
            @socket_close($socket);
        }

        return $latency;
    }

    /**
     * 实现php telnet的功能;
     *
     * @param string $hostname
     * @param int $port
     * @param int $timeout
     * @return bool
     */
    public static function telnet($hostname, $port, $timeout = 1)
    {
        if (!static::isPort($port) || !static::isDigit($timeout, 0)){
            return false;
        }

        if (function_exists('fsockopen')){
            $socket = @fsockopen($hostname, $port, $errno, $errstr, $timeout);
            $results = false !== $socket && 0 == $errno;
            false !== $socket and fclose($socket);

            return $results;
        }

        return true;
    }

    /**
     * 是否合法的端口
     *
     * @param integer $port
     * @return bool
     */
    public static function isPort($port)
    {
        return static::isDigit($port, 1, 65535);
    }

    /**
     * 判断是否是真值
     *
     * "1", "true", "on", "yes", true 返回 true, 其他 false
     *
     * @param string $string
     * @return bool
     */
    public static function isTrue($string)
    {
        if (is_bool($string) && $string){
            return true;
        }

        if (true === filter_var($string, FILTER_VALIDATE_BOOLEAN)){
            return true;
        }

        return false;
    }

    /**
     * 判断是否中文名字
     *
     * @param string $string
     * @return bool
     */
    public static function isChineseName($string)
    {
        return 0 < preg_match('/^([\xe4-\xe9][\x80-\xbf]{2}){2,15}$/', $string);
    }

    /**
     * 是否含有中文
     *
     * @param string $string
     * @return bool
     */
    public static function hasChinese($string)
    {
        return 0 < preg_match('/[\x{4e00}-\x{9fa5}]/u', $string);
    }

    /**
     * 是否中文
     *
     * @param string $string
     * @return bool
     */
    public static function isChinese($string)
    {
        return 0 < preg_match('/^[\x{4e00}-\x{9fa5}]+$/u', $string);
    }

    /**
     * 检查是否是一个数字, 包含
     *
     * @param string $number 对应
     * @param null $decimal
     * @param int|null $min
     * @param int|null $max
     * @return bool
     */
    public static function isNumeric($number, $decimal = null, $min = null, $max = null)
    {
        if (!is_numeric($number)){
            return false;
        }

        if (null !== $max && (!is_numeric($max) || $max < $number)){
            return false;
        }

        if (null !== $min && (!is_numeric($min) || $min > $number)){
            return false;
        }

        if (null === $decimal){
            return true;
        }

        if (!is_array($decimal)

            || 2 != count($decimal)
            || !isset($decimal[0], $decimal[1])

            || !is_numeric($decimal[0])
            || !ctype_digit(strval($decimal[0]))

            || !is_numeric($decimal[1])
            || !ctype_digit(strval($decimal[1]))

            || 0 > $decimal[0]
            || $decimal[0] > $decimal[1]
        ){
            return false;
        }

        if (!preg_match("/^[0-9]+(.[0-9]{{$decimal[0]},{$decimal[1]}})?$/", $number)){
            return false;
        }

        return true;
    }
}
