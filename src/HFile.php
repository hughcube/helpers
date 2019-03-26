<?php

namespace HughCube\Helpers;

class HFile
{
    /**
     * 函数directorySize,用于获取一个目录的大小,单位@字节;
     *
     * @param string $directory 需要统计的目录;
     * @return int;
     */
    public static function directorySize($directory)
    {
        if (is_readable($directory)){
            return false;
        }

        $directorySize = 0;
        if (is_dir($directory)){
            if ($handle = opendir($directory)){
                while(false !== ($file = readdir($handle))){
                    if ($file != '.' && $file != '..'){
                        $thePath = $directory . DIRECTORY_SEPARATOR . $file;
                        $results = static::directorySize($thePath);

                        if (false === $results){
                            return false;
                        }

                        $directorySize += $results;
                    }
                }
                closedir($handle);
            }
        }elseif (is_file($directory)){
            $directorySize += filesize($directory);
        }

        return $directorySize;
    }

    /**
     * 读取文件;
     *
     * @param  string $file 文件名
     * @param  boolean $lockNB 是否堵塞;
     * @return string 文件内容
     */
    public static function getContents($file, $lockNB = false)
    {
        $contents = false;
        if (false !== ($fp = fopen($file, 'r'))){
            if (flock($fp, $lockNB ? (LOCK_SH | LOCK_NB) : LOCK_SH)){
                $contents = stream_get_contents($fp);
                flock($fp, LOCK_UN);
            }
            fclose($fp);
        }

        return $contents;
    }

    /**
     * 安全写入文件内容, 文件不存在就创建文件;
     *
     * @param string $file 文件名
     * @param string $string 写入的内容
     * @param boolean $append 是否使用追加的方式写入;
     * @param boolean $lockNB 是否堵塞;
     * @param integer $fileMode 创建文件的赋予的权限;
     * @return boolean;
     */
    public static function putContents($file, $string, $append = false, $lockNB = false, $fileMode = 0775)
    {
        $result = false;
        if (false !== ($fp = fopen($file, $append ? 'a' : 'w'))){
            if (flock($fp, $lockNB ? (LOCK_EX | LOCK_NB) : LOCK_EX)){
                $result = fwrite($fp, $string);
                null === $fileMode or chmod($file, intval($fileMode));
                flock($fp, LOCK_UN);
            }
            fclose($fp);
        }

        return $result;
    }

    /**
     * 优化include, 主要防止变量污染
     *
     * @param string $file 需要引入的文件
     * @param bool $skipNotExist 是否跳过不存在的文件
     * @param null $default 如果文件不存在的默认值
     * @return mixed|null
     */
    public static function getInclude($file, $skipNotExist = false, $default = null)
    {
        if ($skipNotExist && !is_file($file)){
            return $default;
        }

        return (include $file);
    }

    /**
     * 创建目录
     *
     * @param string $path 创建的目录
     * @param int $mode
     * @param bool $recursive
     * @return bool
     */
    public static function createDirectory($path, $mode = 0775, $recursive = true)
    {
        if (is_dir($path)){
            return true;
        }

        $parentDir = dirname($path);
        if ($recursive
            && !is_dir($parentDir)
            && $parentDir !== $path
            && false == static::createDirectory($parentDir, $mode, true)
        ){
            return false;
        }

        if (false === @mkdir($path, $mode)){
            return false;
        }

        return true;
    }

    /**
     * 返回路径中的文件名部分
     *
     * @param string $path
     * @param string $suffix
     * @return string
     */
    public static function basename($path, $suffix = '')
    {
        if (($len = mb_strlen($suffix)) > 0 && mb_substr($path, -$len) === $suffix){
            $path = mb_substr($path, 0, -$len);
        }

        $path = rtrim(str_replace('\\', '/', $path), '/\\');
        if (($pos = mb_strrpos($path, '/')) !== false){
            return mb_substr($path, $pos + 1);
        }

        return $path;
    }

    /**
     * 获取目录部分
     *
     * @param string $path
     * @return string
     */
    public static function dirname($path)
    {
        $pos = mb_strrpos(str_replace('\\', '/', $path), '/');
        if ($pos !== false){
            return mb_substr($path, 0, $pos);
        }

        return '';
    }
}
