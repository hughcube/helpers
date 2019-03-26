<?php

namespace Hughcube\Helpers;
/**
 * https://secure.php.net/manual/en/function.base-convert.php
 * php官方文档上面的代码,  只是为了方便引用而已
 */
class HBase
{
    const BASE_10 = '0123456789';
    const BASE_2  = '01';

    /**
     * @param $numberInput
     * @return string
     */
    public static function conv10To2($numberInput)
    {
        return static::conv($numberInput, static::BASE_10, static::BASE_2);
    }

    /**
     * @param $numberInput
     * @return string
     */
    public static function conv2To10($numberInput)
    {
        return static::conv($numberInput, static::BASE_2, static::BASE_10);
    }

    /**
     * 进制转换
     *
     * @param string $numberInput 需要转换的数据
     * @param string $fromBaseInput 原始进制的对照表
     * @param string $toBaseInput 转换后进制的对照表
     * @return string
     */
    public static function conv($numberInput, $fromBaseInput, $toBaseInput)
    {
        if ($fromBaseInput == $toBaseInput){
            return $numberInput;
        }
        $fromBase = str_split($fromBaseInput, 1);
        $toBase = str_split($toBaseInput, 1);
        $number = str_split($numberInput, 1);
        $fromLen = strlen($fromBaseInput);
        $toLen = strlen($toBaseInput);
        $numberLen = strlen($numberInput);
        $retval = '';
        if ($toBaseInput == '0123456789'){
            $retval = 0;
            for($i = 1; $i <= $numberLen; $i++)
                $retval = bcadd($retval, bcmul(array_search($number[$i - 1], $fromBase), bcpow($fromLen, $numberLen - $i)));

            return $retval;
        }
        if ($fromBaseInput != '0123456789'){
            $base10 = static::conv($numberInput, $fromBaseInput, '0123456789');
        }else{
            $base10 = $numberInput;
        }
        if ($base10 < strlen($toBaseInput)){
            return $toBase[$base10];
        }
        while($base10 != '0'){
            $retval = $toBase[bcmod($base10, $toLen)] . $retval;
            $base10 = bcdiv($base10, $toLen, 0);
        }

        return strval($retval);
    }
}
