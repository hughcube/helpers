<?php

namespace HughCube\Helpers;

use InvalidArgumentException;

class HTree
{
    /**
     * @var array
     */
    protected $items = [];

    /**
     * items的id的key
     *
     * @var string
     */
    protected $idKey;

    /**
     * items的parent的key
     *
     * @var string
     */
    protected $parentKey;

    /**
     * items 数据的索引
     *
     * @var array
     */
    protected $index = [];

    /**
     * index的tree
     *
     * @var string
     */
    protected $indexTree;

    /**
     * 正在构建的 item
     *
     * @var array
     */
    protected $buildIndexInProcess = [];

    /**
     * 标识是否能否能构建合法的树形
     *
     * @var bool
     */
    protected $isValid = true;

    /**
     * @param array $items 构建树形结构的数组, 每个元素必需包含 id, parent 两个属性
     * @param string $idkey id属性的名字
     * @param string $parentKey parent属性的名字
     */
    protected function __construct(array $items, $idkey, $parentKey)
    {
        $this->idKey = $idkey;
        $this->parentKey = $parentKey;

        $this->setItems($items);

        if (!$this->getIsValid()){
            throw new InvalidArgumentException('$items 不能构建成一个树形');
        }
    }

    /**
     * 是否能构建树形
     *
     * @return bool
     */
    public function getIsValid()
    {
        return $this->isValid;
    }

    /**
     * 获取单个节点数据
     *
     * @param string $id id
     * @return mixed|null
     */
    public function getItem($id)
    {
        if (!array_key_exists($id, $this->items)){
            return null;
        }

        return $this->items[$id];
    }

    /**
     * 是否存在某个节点数据
     *
     * @param string $id id
     * @return bool
     */
    public function hasItem($id)
    {
        return null != $this->getItem($id);
    }

    /**
     * 设置 items
     *
     * @param $items
     */
    protected function setItems(array $items)
    {
        $this->index = $this->items = $this->tree = [];

        $this->items = HArray::index($items, $this->idKey);
        $this->buildIndex();
    }

    /**
     * 获取items
     *
     * @return array
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * 添加一个节点
     *
     * @param array|object $node 节点数据
     *
     * @return static
     */
    public function addChild($node)
    {
        $parent = HArray::getValue($node, $this->parentKey);

        $this->addNote($node, $parent, true);

        return $this;
    }

    /**
     * 获取节点的子节点
     *
     * @param string $nid 节点id
     * @param bool $onlyId 是否只返回 id
     * @param null|integer $startLevel 往下多少级-起始, 为空不限制
     * @param null|integer $endLevel 往下多少级-结束, 为空不限制
     * @param bool $withSelf 结果是否包括自己
     * @return array
     */
    public function getChildren(
        $nid,
        $onlyId = false,
        $startLevel = null,
        $endLevel = null,
        $withSelf = false
    )
    {
        $nodes = $parents = [];

        /** 先收集一次, 防止父 id 不存在不能被收集 */
        foreach($this->items as $id => $item){
            if ($nid == HArray::getValue($item, $this->parentKey)){
                $parents[] = $id;

                $nodes[$id] = ($onlyId ? $id : $this->items[$id]);
            }
        }

        foreach($parents as $parent){
            foreach($this->index as $id => $item){
                if (
                    $item['left'] > $this->index[$parent]['left']
                    && $item['right'] < $this->index[$parent]['right']
                ){
                    $nodes[$id] = ($onlyId ? $id : $this->items[$id]);
                }
            }
        }

        foreach($nodes as $id => $node){
            if (HCheck::isDigit($this->getNodeLevel($id), $startLevel, $endLevel)){
                continue;
            }

            unset($nodes[$id]);
        }

        /** 是否返回自己本身 */
        if ($withSelf && $this->hasItem($nid)){
            $nodes[$nid] = ($onlyId ? $nid : $this->getItem($nid));
        }

        return $nodes;
    }

    /**
     * 获取某一节点的父节点
     *
     * @param string $nid 节点id
     * @param bool $onlyId 是否只返回 id
     * @param null|integer $level 取第几级父节点, 默认取上一级
     * @return int|mixed|null|string
     */
    public function getParent($nid, $onlyId = false, $level = null)
    {
        if (!isset($this->items[$nid])){
            return null;
        }

        $level = null === $level ? ($this->getNodeLevel($nid) - 1) : $level;

        $parent = null;
        foreach($this->index as $id => $item){
            if (
                $item['left'] < $this->index[$nid]['left']
                && $item['right'] > $this->index[$nid]['right']
                && $item['level'] == $level
            ){
                return $onlyId ? $id : $this->items[$id];
            }
        }

        return null;
    }

    /**
     * 获取某一节点的所有父节点
     *
     * @param string $nid 节点id
     * @param bool $onlyId 是否只返回 id
     * @param null|integer $startLevel 往上多少级-起始, 为空不限制
     * @param null|integer $endLevel 往上多少级-结束, 为空不限制
     * @return array
     */
    public function getParents(
        $nid,
        $onlyId = false,
        $startLevel = null,
        $endLevel = null,
        $withSelf = false
    )
    {
        $parents = [];

        if (!$this->hasItem($nid)){
            return $parents;
        }

        foreach($this->index as $id => $item){
            if (
                $item['left'] < $this->index[$nid]['left']
                && $item['right'] > $this->index[$nid]['right']
                && HCheck::isDigit($item['level'], $startLevel, $endLevel)
            ){
                $parents[$id] = ($onlyId ? $id : $this->items[$id]);
            }
        }

        /** 是否返回自己本身 */
        if ($withSelf && $this->hasItem($nid)){
            $parents[$nid] = ($onlyId ? $nid : $this->getItem($nid));
        }

        return $parents;
    }

    /**
     * 树排序
     *
     * @param callable $cmpSortCallable 计算每个元素值的方法, 返回一个数字. 顺序排序, 越大越后面
     * @return $this
     */
    public function treeSort(callable $cmpSortCallable)
    {
        $this->indexTree = $this->recursiveTreeSort($this->indexTree, $cmpSortCallable);

        return $this;
    }

    /**
     * 递归遍历每一个元素, 按照指定的顺, 并且可以改变元素的值
     *
     * @param callable $callable 返回值作为该元素新的值
     * @return $this
     */
    public function treeMap(callable $callable)
    {
        $this->recursiveTreeMap($this->indexTree, $callable);

        return $this;
    }

    /**
     * 获取树结构
     *
     * @param string $childrenKey 子集的数组key
     * @param callable $format 格式化返回的元素
     * @return array
     */
    public function getTree($childrenKey = 'items', callable $format = null)
    {
        return $this->recursiveGetTree($this->indexTree, $childrenKey, $format);
    }

    /**
     * 克隆对象
     *
     * @return static
     */
    public function cloneInstance()
    {
        $instance = clone $this;

        return $instance;
    }

    /**
     * 获取实例
     *
     * @param string $url
     * @return static
     */
    public static function instance(array $items, $idkey = 'id', $parentKey = 'parent')
    {
        return new static($items, $idkey, $parentKey);
    }

    /**
     * 构建 index
     */
    protected function buildIndex()
    {
        $this->index = [];
        $this->buildIndexInProcess = [];
        $this->isValid = true;

        foreach($this->items as $id => $item){
            if (!isset($this->index[$id])){
                $this->recursiveBuildIndex($id);
            }
        }

        $this->buildIndexTree();
    }

    /**
     * 递归构建 index
     *
     * @param $id
     */
    protected function recursiveBuildIndex($id)
    {
        if (!$this->isValid || in_array($id, $this->buildIndexInProcess)){
            return $this->isValid = false;
        }

        $this->buildIndexInProcess[$id] = $id;

        /** @var integer $parent 需要处理的节点父节点id */
        $parent = HArray::getValue($this->items[$id], $this->parentKey);

        /** 如果存在父节点, 并且父节点没有被被处理, 先处理父节点 */
        if (isset($this->items[$parent]) && !isset($this->index[$parent])){
            $this->recursiveBuildIndex($parent);
        }

        /** 添加节点 */
        $this->addNote($this->items[$id], $parent);

        unset($this->buildIndexInProcess[$id]);
    }

    /**
     * 递归遍历每一个元素
     *
     * @param $items
     * @param callable $callable
     */
    protected function recursiveTreeMap($items, $callable)
    {
        if (empty($items)){
            return;
        }

        foreach($items as $id => $item){
            $this->items[$id] = $callable($this->items[$id]);
            $this->recursiveTreeMap($item['items'], $callable);
        }
    }

    /**
     * 递归遍历每一个元素
     *
     * @param $items
     * @param callable $callable
     */
    protected function recursiveGetTree($items, $childrenKey, $format)
    {
        if (empty($items)){
            return [];
        }

        $_ = [];
        foreach($items as $id => $item){
            $node = null === $format ? $this->items[$id] : $format($this->items[$id]);
            $child = $this->recursiveGetTree($item['items'], $childrenKey, $format);
            $child && $node[$childrenKey] = $child;

            $_[] = $node;
        }

        return $_;
    }

    /**
     * 添加一个节点
     *
     * @param $node
     * @param null $parent 添加到那个节点
     * @param bool $buildTree 是否构建 indexTree
     */
    protected function addNote($node, $parent = null, $buildTree = false)
    {
        $id = HArray::getValue($node, $this->idKey);
        $this->items[$id] = $node;

        if (empty($parent) || !isset($this->index[$parent])){
            $plevel = $pleft = 0;
        }else{
            $pleft = $this->index[$parent]['left'];
            $plevel = $this->index[$parent]['level'];
        }

        /** 改变其他元素的, 给当前节点留出位置 */
        foreach($this->index as $key => $item){
            if ($item['left'] > $pleft){
                $this->index[$key]['left'] += 2;
            }

            if ($item['right'] > $pleft){
                $this->index[$key]['right'] += 2;
            }
        }

        $this->index[$id] = [
            'id' => $id,
            'level' => $plevel + 1,
            'left' => $pleft + 1,
            'right' => ($pleft + 1) + 1,
            'parent' => $parent,
        ];

        if ($buildTree){
            $this->buildIndexTree();
        }
    }

    /**
     * 构建 index 的树形结构
     */
    protected function buildIndexTree()
    {
        $items = $this->index;

        foreach($items as $key => $item){
            $items[$key]['items'] = [];
        }

        $tree = [];
        foreach($items as $id => $item){
            if (isset($items[$item['parent']])){
                $items[$item['parent']]['items'][$id] = &$items[$id];
            }else{
                $tree[$id] = &$items[$id];
            }
        }

        $this->indexTree = $tree;
    }

    /**
     * 递归排序
     *
     * @param $items
     * @param $orderBy
     * @return array
     */
    protected function recursiveTreeSort($items, $cmpSortCallable)
    {
        if (empty($items)){
            return [];
        }

        foreach($items as $key => $item){
            $items[$key]['items'] = $this->recursiveTreeSort($item['items'], $cmpSortCallable);
        }

        uasort($items, function ($a, $b) use ($cmpSortCallable){
            $aSort = $cmpSortCallable($this->items[$a['id']]);
            $bSort = $cmpSortCallable($this->items[$b['id']]);

            if ($aSort == $bSort){
                return 0;
            }

            return ($aSort < $bSort) ? -1 : 1;
        });

        return $items;
    }

    /**
     * @param $id
     * @return array|null
     * [
     *      'id' => 'id',
     *      'level' => '级别',
     *      'left' => '左值',
     *      'right' => '右值',
     *      'parent' => '父节点id',
     * ]
     */
    public function getNodeIndex($id)
    {
        if (!isset($this->index[$id])){
            return null;
        }

        return $this->index[$id];
    }

    /**
     * 获取指定节点的level
     *
     * @param $id
     * @return mixed|null
     */
    public function getNodeLevel($id)
    {
        $index = $this->getNodeIndex($id);

        return isset($index['level']) ? $index['level'] : null;
    }

    /**
     * 获取指定节点的left
     *
     * @param $id
     * @return mixed|null
     */
    public function getNodeLeft($id)
    {
        $index = $this->getNodeIndex($id);

        return isset($index['left']) ? $index['left'] : null;
    }

    /**
     * 获取指定节点的right
     *
     * @param $id
     * @return mixed|null
     */
    public function getNodeRight($id)
    {
        $index = $this->getNodeIndex($id);

        return isset($index['right']) ? $index['right'] : null;
    }
}
