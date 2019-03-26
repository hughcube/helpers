<?php

namespace HughCube\Helpers;

class HRandom
{
    /**
     * 随机生成包含小写字母和数字的字符串
     *
     * @param int $length 需要生成的字符串长度
     * @return string
     * @throws
     */
    public static function alnum($length)
    {
        $haystack = '0HJQyVvzK8pRSErq7Bbh96U1WLFsXYTng3NewICtxofAjOM2lD4d5GuZiakcPm';

        return static::string($haystack, $length);
    }

    /**
     * 随机字母字符串
     *
     * @param int $length 返回字符串长度
     * @return string
     */
    public static function alpha($length)
    {
        $haystack = 'GhotODkSdljarEquWHbJXCwpmKUfBMvARsNIQcexLZYziFPgVTny';

        return static::string($haystack, $length);
    }

    /**
     * 随机能作为验证的字符串
     *
     * @param int $length 返回字符串长度
     * @return string
     */
    public static function verifyCode($length)
    {
        $haystack = 'rBfj3pMDC1sa8X5GNRVtTykEzWPHqQ4Jdb7gZxYwnScUuhveKm6F29A';

        return static::string($haystack, $length);
    }

    /**
     * 随机生成一个数字
     *
     * @param integer $min 最小值
     * @param integer $max 最大值
     * @return int
     * @throws
     */
    public static function integer($min, $max, $security = true)
    {
        $int = null;

        if (null === $int && function_exists('random_int') && $security){
            $int = random_int($min, $max);
        }

        if (null === $int){
            $int = mt_rand($min, $max);
        }

        return $int;
    }

    /**
     * 通过一个字符串随机生成一个指定长度的字符串
     *
     * @param string $string 源字符串
     * @param int $length 返回的字符串长度
     * @return string
     */
    public static function string($haystack, $length)
    {
        $string = '';
        $maxIndex = strlen($haystack) - 1;
        for($i = 1; $i <= $length; $i++){
            $index = static::integer(0, $maxIndex);
            $string .= $haystack{$index};
        }

        return $string;
    }

    /**
     * 随机移出一个元素
     *
     * @param array $array 需要删除元素的数组
     * @return array|false
     */
    public static function arrayRemove(array &$array)
    {
        if (empty($array)){
            return false;
        }

        $keys = array_keys($array);
        $key = $keys[static::integer(0, (count($keys) - 1))];

        $value = $array[$key];
        unset($array[$key]);

        return $value;
    }
}
