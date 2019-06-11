<?php

namespace HughCube\Helpers;

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
     * @param array $items
     * @param $idkey
     * @param $pkey
     */
    protected function __construct(array $items, $idkey, $parentKey)
    {
        $this->idKey = $idkey;
        $this->parentKey = $parentKey;

        $this->setItems($items);
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
     * 添加一个节点
     *
     * @param array|object $node 节点数据
     * @param null $parent 添加到那个节点下面
     *
     * @return static
     */
    public function addChild($node, $parent = null)
    {
        $this->addNote($node, $parent, true);

        return $this;
    }

    /**
     * 获取节点的子节点
     *
     * @param string $nid 节点id
     * @param bool $onlyId 是否只返回 id
     * @param null $level 指定层级的, 未指定返回所有的子节点包括孙子节点或者以下
     * @return array
     */
    public function getChildren($nid, $onlyId = false, $level = null)
    {
        if (null !== $level && 0 == $level){
            return [];
        }

        $nodes = $parents = [];

        // 先收集一次, 防止父 id 不存在不能被收集
        foreach($this->items as $id => $item){
            if ($nid == HArray::getValue($item, $this->parentKey)){
                $parents[] = $id;

                $nodes[$id] = ($onlyId ? $id : $this->items[$id]);
            }
        }

        if (1 == $level){
            return $nodes;
        }

        $level = null === $level ? null : $level + 1;

        foreach($this->index as $id => $item){
            foreach($parents as $parent){
                if ($item['left'] > $this->index[$parent]['left']
                    && $item['right'] < $this->index[$parent]['right']
                    && (null === $level || $item['level'] - $this->index[$parent]['level'] <= $level)
                ){
                    $nodes[$id] = ($onlyId ? $id : $this->items[$id]);
                }
            }
        }

        return $nodes;
    }

    /**
     * 获取某一节点的父节点
     *
     * @param string $nid 节点id
     * @param bool $onlyId 是否只返回 id
     * @param null $level 指定层级的, 默认1返回上一级的父节点
     * @return int|mixed|null|string
     */
    public function getParent($nid, $onlyId = false, $level = 1)
    {
        if (!isset($this->items[$nid])){
            return null;
        }

        $parent = null;
        foreach($this->index as $id => $item){
            if ($item['left'] < $this->index[$nid]['left']
                && $item['right'] > $this->index[$nid]['right']
                && $this->index[$nid]['level'] - $item['level'] == $level
            ){
                return $onlyId ? $id : $this->items[$id];
            }
        }

        return null;
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
            $node[$childrenKey] = $this->recursiveGetTree($item['items'], $childrenKey, $format);

            $_[] = $node;
        }

        return $_;
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
}
