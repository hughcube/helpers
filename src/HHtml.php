<?php

namespace HughCube\Helpers;

class HHtml
{
    /**
     * 将特殊字符转换为 HTML 实体
     *
     * @param string $content
     * @param bool $doubleEncode
     * @param string $charset
     * @return string
     */
    public static function encode($content, $doubleEncode = true, $charset = 'UTF-8')
    {
        return htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, $charset, $doubleEncode);
    }

    /**
     * 将特殊的 HTML 实体转换回普通字符
     *
     * @param string $content
     * @return string
     */
    public static function decode($content)
    {
        return htmlspecialchars_decode($content, ENT_QUOTES);
    }
}
