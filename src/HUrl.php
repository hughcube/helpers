<?php

namespace HughCube\Helpers;

use InvalidArgumentException;

class HUrl
{
    private static $defaultPorts = [
        'http' => 80,
        'https' => 443,
        'ftp' => 21,
        'gopher' => 70,
        'nntp' => 119,
        'news' => 119,
        'telnet' => 23,
        'tn3270' => 23,
        'imap' => 143,
        'pop' => 110,
        'ldap' => 389,
    ];

    /**
     * @var array
     */
    protected $components = [];

    const SCHEME   = 'scheme';
    const HOST     = 'host';
    const PORT     = 'port';
    const USER     = 'user';
    const PASS     = 'pass';
    const PATH     = 'path';
    const QUERY    = 'query';
    const FRAGMENT = 'fragment';

    /**
     * HUrl constructor.
     * @param string $url url
     */
    protected function __construct($url = null)
    {
        if (empty($url)){
            return;
        }

        if (!empty($url) && !filter_var($url, FILTER_VALIDATE_URL)){
            throw new InvalidArgumentException('the parameter must be a url');
        }

        $this->components = parse_url($url);

        if (isset($this->components[static::QUERY])){
            parse_str($this->components[static::QUERY], $query);
            $this->components[static::QUERY] = (!is_array($query) || empty($query)) ? [] : $query;
        }

        if (empty($this->components[static::PORT]) || !HCheck::isPort($this->components[static::PORT])){
            $this->components[static::PORT] = $this->getSchemeDefaultPort($this->components[static::SCHEME]);
        }
    }

    /**
     * 获取url协议
     *
     * @return string|null
     */
    public function getScheme()
    {
        return $this->getComponent(static::SCHEME);
    }

    /**
     * 设置url协议
     *
     * @param string $scheme url协议
     * @param bool $linkagePort 是否跟随协议变更端口
     * @return $this
     */
    public function withScheme($scheme, $linkagePort = false)
    {
        $instance = clone $this;

        $instance->setComponent(static::SCHEME, $scheme);
        if ($linkagePort && null != ($port = $instance->getSchemeDefaultPort($scheme))){
            $instance->setComponent(static::PORT, $port);
        }

        return $instance;
    }

    /**
     * 获取host
     *
     * @return string|null
     */
    public function getHost()
    {
        return $this->getComponent(static::HOST);
    }

    /**
     * 设置host
     *
     * @param string $host host部分
     * @return $this
     */
    public function withHost($host)
    {
        $instance = clone $this;
        $instance->setComponent(static::HOST, $host);

        return $instance;
    }

    /**
     * 获取端口
     *
     * @return integer|null
     */
    public function getPort()
    {
        return $this->getComponent(static::PORT);
    }

    /**
     * 设置端口
     *
     * @param int $port 端口
     * @param bool $linkageScheme 是否根据端口变更协议
     * @return $this
     */
    public function withPort($port, $linkageScheme = false)
    {
        $instance = clone $this;

        $instance->setComponent(static::PORT, $port);
        if ($linkageScheme && null != ($scheme = $instance->getPortDefaultScheme($port))){
            $instance->setComponent(static::SCHEME, $scheme);
        }

        return $instance;
    }

    /**
     * 获取包含的用户
     *
     * @return string|null
     */
    public function getUser()
    {
        return $this->getComponent(static::USER);
    }

    /**
     * 设置用户
     *
     * @param string $user 用户名
     * @param null $password 用户密码, false表示清除, null表示不改变, 其他为赋值
     * @return $this
     */
    public function withUser($user, $password = null)
    {
        $instance = clone $this;

        $instance->setComponent(static::USER, $user);
        if (false === $password){
            $instance->setComponent(static::PASS, null);
        }elseif (null === $password){
        }else{
            $instance->setComponent(static::PASS, $password);
        }

        return $instance;
    }

    /**
     * 移除用户和密码
     *
     * @return $this
     */
    public function removeUser()
    {
        return $this->withUser(null, false);
    }

    /**
     * 获取包含的用户密码
     *
     * @return string|null
     */
    public function getPassword()
    {
        return $this->getComponent(static::PASS);
    }

    /**
     * 获取url的path
     *
     * @return string|null
     */
    public function getPath()
    {
        return $this->getComponent(static::PATH);
    }

    /**
     *  设置url的path
     *
     * @param string $path url的路径
     * @return $this
     */
    public function withPath($path)
    {
        $instance = clone $this;
        $instance->setComponent(static::PATH, $path);

        return $instance;
    }

    /**
     * 获取锚点数据
     *
     * @return string|null
     */
    public function getFragment()
    {
        return $this->getComponent(static::FRAGMENT);
    }

    /**
     * 设置锚点数据
     *
     * @param string $fragment 锚点数据
     * @return $this
     */
    public function withFragment($fragment)
    {
        $instance = clone $this;
        $instance->setComponent(static::FRAGMENT, $fragment);

        return $instance;
    }

    /**
     * 删除锚点
     *
     * @return $this
     */
    public function removeFragment()
    {
        return $this->withFragment(null);
    }

    /**
     * 获取query参数
     *
     * @return array
     */
    public function getQueryParams()
    {
        $queryParams = $this->getComponent(static::QUERY);

        return (empty($queryParams) || !is_array($queryParams)) ? [] : $queryParams;
    }

    /**
     * 获取query字符串
     *
     * @return string
     */
    public function getQueryString()
    {
        $queryParams = $this->getQueryParams();

        return http_build_query($queryParams);
    }

    /**
     * 查看某个参数是否存在
     *
     * @param string $name 参数名
     * @return bool
     */
    public function existsQueryParam($name)
    {
        $queryParams = $this->getQueryParams();

        return array_key_exists($name, $queryParams);
    }

    /**
     * 获取参数
     *
     * @param string $name 参数名
     * @param null $default 如果参数不存在, 默认返回的数据
     * @return mixed
     */
    public function getQueryParam($name, $default = null)
    {
        if (!$this->existsQueryParam($name)){
            return $default;
        }

        return $this->getQueryParams()[$name];
    }

    /**
     * 设置query参数
     *
     * @param array $params query的数组
     * @return $this
     */
    public function withQueryParams(array $params)
    {
        $instance = clone $this;
        $instance->setComponent(static::QUERY, $params);

        return $instance;
    }

    /**
     * 设置参数
     *
     * @param string $name 参数名
     * @param string|null|array $value 参数值
     * @return $this
     */
    public function withQueryParam($name, $value)
    {
        $queryParams = $this->getQueryParams();
        $queryParams[$name] = $value;

        return $this->withQueryParams($queryParams);
    }

    /**
     * 追加参数, 如果存在不操作
     *
     * @param string $name 参数名
     * @param string|null|array $value 参数值
     * @return $this
     */
    public function addQueryParam($name, $value)
    {
        if ($this->existsQueryParam($name)){
            $instance = clone $this;

            return $instance;
        }

        return $this->withQueryParam($name, $value);
    }

    /**
     * 删除参数
     *
     * @param string $name 参数名
     * @return $this
     */
    public function removeQueryParam($name)
    {
        if ($this->existsQueryParam($name)){
            $instance = clone $this;

            return $instance;
        }

        $queryParams = $this->getQueryParams();
        unset($queryParams[$name]);

        return $this->withQueryParams($queryParams);
    }

    /**
     * 删除空参数
     *
     * @return static
     */
    public function removeEmptyQueryParam()
    {
        $queryParams = $this->getQueryParams();
        foreach($queryParams as $name => $value){
            if (null === $value || '' === $value){
                unset($queryParams[$name]);
            }
        }

        return $this->withQueryParams($queryParams);
    }

    /**
     * 设置属性值
     *
     * @param string $name 属性名
     * @param mixed $value 属性值
     */
    protected function setComponent($name, $value)
    {
        $this->components[$name] = $value;
    }

    /**
     * 获取属性
     *
     * @param string $name 属性名
     * @param mixed $default 默认值
     * @return mixed
     */
    protected function getComponent($name, $default = null)
    {
        return HArray::getValue($this->components, $name, $default);
    }

    /**
     * 检查port是否是scheme的默认端口
     *
     * @param string $scheme
     * @param integer $port
     * @return bool
     */
    public static function isDefaultPort($scheme, $port)
    {
        if (!isset(static::$defaultPorts[$scheme])){
            return false;
        }

        return static::$defaultPorts[$scheme] == $port;
    }

    /**
     * 获取scheme的默认端口
     *
     * @param string $scheme
     * @return null|integer
     */
    public static function getSchemeDefaultPort($scheme)
    {
        if (!isset(static::$defaultPorts[$scheme])){
            return null;
        }

        return static::$defaultPorts[$scheme];
    }

    /**
     * 获取port的默认scheme
     *
     * @param integer $p
     * @return null|string
     */
    public static function getPortDefaultScheme($p)
    {
        foreach(static::$defaultPorts as $scheme => $port){
            if ($p == $port && HCheck::isPort($p)){
                return $scheme;
            }
        }

        return null;
    }

    /**
     * 获取url
     *
     * @return string
     */
    public function getUrl()
    {
        $url = '';

        if (empty($this->components)){
            return $url;
        }

        $url .= ($scheme = $this->getScheme());
        $url .= '://';

        $user = $this->getUser();
        if (!empty($user)){
            $url .= "{$user}";

            $password = $this->getPassword();
            $url .= ((!empty($password)) ? ":{$password}" : '');
            $url .= '@';
        }

        $url .= ($host = $this->getHost());

        $port = $this->getPort();
        if (!empty($scheme) && !empty($port) && !$this->isDefaultPort($scheme, $port)){
            $url .= ":{$port}";
        }

        $url .= ($path = $this->getPath());

        $queryString = $this->getQueryString();
        $url .= (empty($queryString) ? '' : "?{$queryString}");

        $fragment = $this->getFragment();
        $url .= (empty($fragment) ? '' : "#{$fragment}");

        return $url;
    }

    /**
     * 检查host
     *
     * @param string $domain 最比对的host
     * @param bool $strict true: 强一致检查,  false: 把$domain当做顶级域名检查
     * @return bool
     */
    public function checkHost($domain, $strict = false)
    {
        $host = $this->getHost();

        if ($domain === $host){
            return true;
        }

        if ($strict){
            return false;
        }

        if (HCheck::isIp($host)){
            return false;
        }

        $pattern = strtr($domain, ['.' => '\.', '*' => '(.*)']);
        $pattern = "/^({$pattern})$/i";

        return 0 < preg_match($pattern, $this->getHost());
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getUrl();
    }

    /**
     * 获取实例
     *
     * @param string $url
     * @return static
     */
    public static function instance($url = null)
    {
        return new static($url);
    }
}
