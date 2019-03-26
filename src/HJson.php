<?php

namespace HughCube\Helpers;

class HJson
{
    /**
     * @param array $value
     * @param null $options
     * @param int $depth
     * @return false|string
     */
    public static function encode($value, $options = null, $depth = 512)
    {
        $options = null === $options ? (JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT) : $options;

        return json_encode($value, $options, $depth);
    }

    /**
     * @param array $value
     * @return false|string
     */
    public static function htmlEncode($value)
    {
        return static::encode($value, JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
    }

    /**
     * @param string $json
     * @param bool $asArray
     * @return mixed
     */
    public static function decode($json, $asArray = true)
    {
        return json_decode($json, $asArray);
    }
}
