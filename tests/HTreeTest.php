<?php

namespace HughCube\Helpers;

use PHPUnit\Framework\TestCase;

class HTreeTest extends TestCase
{
    /**
     * 设置的子集的key名
     */
    const CHILDREN_KEY = 'children';

    /**
     * 根据 static::getItems,  200足够把数组遍历几遍, 基本可以触发所有的情况
     */
    const ITERATION_COUNT = 200;

    /**
     * @return HTree
     */
    public function testInstance()
    {
        $items = $this->getItems();
        $instance = HTree::instance($items, 'id', 'parent');

        $this->assertInstanceOf(HTree::class, $instance);

        return $instance;
    }

    /**
     * @param HTree $tree
     * @depends testInstance
     * @expectedException \InvalidArgumentException
     */
    public function testGetIsValid($tree)
    {
        $this->assertTrue($tree->getIsValid());


        $items = $this->getBadItems();
        $instance = HTree::instance($items, 'id', 'parent');
        $this->assertFalse($instance->getIsValid());
    }

    /**
     * @param HTree $tree
     * @return HTree
     * @depends testInstance
     */
    public function testGetItems($tree)
    {
        $items = $this->getItems();
        $this->assertSame(array_values($items), array_values($tree->getItems()));

        return $tree;
    }

    /**
     * @param HTree $tree
     * @depends testGetItems
     */
    public function testGetItem($tree)
    {
        $items = $tree->getItems();

        foreach([1, 2, 4, 10, 17, 25, 26, 78, 10000] as $id){
            $this->assertSame(
                $tree->getItem($id),
                isset($items[$id]) ? $items[$id] : null
            );
        }
    }


    /**
     * @param HTree $tree
     * @depends testGetItems
     */
    public function hasItem($tree)
    {
        $items = $tree->getItems();

        foreach([1, 2, 4, 10, 17, 25, 26, 78, 10000] as $id){
            $this->assertSame($tree->hasItem($id), isset($items[$id]));
        }
    }

    /**
     * @param HTree $tree
     * @depends testGetItems
     */
    public function testNodeIndex($tree)
    {
        $id = $this->randItemId();
        $index = $tree->getNodeIndex($id);

        $this->assertArrayHasKey('id', $index);
        $this->assertArrayHasKey('level', $index);
        $this->assertArrayHasKey('left', $index);
        $this->assertArrayHasKey('right', $index);
        $this->assertArrayHasKey('parent', $index);
    }

    /**
     * @param HTree $tree
     * @depends testGetItems
     */
    public function testAddChild($tree)
    {
        $parent = $this->randItemId();
        $id = 9999999999;

        $tree->addChild(['id' => 9999999999, 'parent' => $parent]);

        $item = $tree->getItem($id);

        $this->assertTrue($tree->getNodeIndex($id)['parent'] == $parent);

        $this->assertParentChildAttributes($tree, $parent, $id);

        $this->assertLevelRange(
            $tree,
            $id,
            ($tree->getNodeLevel($parent) + 1),
            ($tree->getNodeLevel($parent) + 1)
        );
    }


    /**
     * @param HTree $tree
     * @depends testGetItems
     */
    public function testGetChildren($tree)
    {
        /** 是否只返回id OR item */
        for($i = 0; $i <= static::ITERATION_COUNT; $i++){
            foreach([0, 1] as $onlyId){
                $parent = $this->randItemId();
                $children = $tree->getChildren($parent, $onlyId);

                foreach($children as $id => $child){
                    if ($onlyId){
                        $this->assertTrue(HCheck::isDigit($child));
                        $this->assertSame($id, $child);
                    }//

                    else{
                        $this->assertSame($tree->getItem($id), $child);
                    }

                    $this->assertParentChildAttributes($tree, $parent, $id);
                    $this->assertLevelRange($tree, $id, ($tree->getNodeLevel($parent) + 1));
                }
            }
        }

        /** 开始 */
        for($i = 0; $i <= static::ITERATION_COUNT; $i++){
            foreach([1, 2, 3, 4, 5] as $startLevel){
                $parent = $this->randItemId();
                $children = $tree->getChildren($parent, true, $startLevel);

                foreach($children as $id => $child){
                    $this->assertParentChildAttributes($tree, $parent, $child);
                    $this->assertLevelRange($tree, $id, ($tree->getNodeLevel($parent) + 1));
                }
            }
        }

        /** 开始和结束 */
        for($i = 0; $i <= static::ITERATION_COUNT; $i++){
            foreach([1, 2, 3, 4, 5] as $startLevel){
                foreach([1, 2, 3] as $limit){
                    $endLevel = $startLevel + $limit;

                    $parent = $this->randItemId();
                    $children = $tree->getChildren($parent, true, $startLevel, $endLevel);

                    foreach($children as $id => $child){
                        $this->assertParentChildAttributes($tree, $parent, $child);
                        $this->assertLevelRange($tree, $id, $startLevel, $endLevel);
                    }
                }
            }
        }

        /** 结束 */
        for($i = 0; $i <= static::ITERATION_COUNT; $i++){
            foreach([1, 2, 3, 4, 5] as $endLevel){
                $parent = $this->randItemId();
                $children = $tree->getChildren($parent, true, null, $endLevel);

                foreach($children as $id => $child){
                    $this->assertParentChildAttributes($tree, $parent, $child);
                    $this->assertLevelRange($tree, $id, ($tree->getNodeLevel($parent) + 1));
                }
            }
        }
    }


    /**
     * @param HTree $tree
     * @depends testGetItems
     */
    public function testGetParent($tree)
    {
        $items = $tree->getItems();
        $items = HArray::index($items, 'id');

        /** 默认上一级 */
        for($i = 0; $i <= static::ITERATION_COUNT; $i++){
            $childId = $this->randItemId();
            $parent = $tree->getParent($childId);

            $childLevel = $tree->getNodeLevel($childId);
            if (1 >= $childLevel){
                continue;
            }

            $this->assertTrue(($childLevel - 1) === $tree->getNodeLevel($parent['id']));
        }

        /** 指定多少级 */
        for($i = 0; $i <= static::ITERATION_COUNT; $i++){
            foreach([1, 2, 3, 4, 5] as $level){
                $childId = $this->randItemId();
                $parent = $tree->getParent($childId, true, $level);

                $childLevel = $tree->getNodeLevel($childId);
                if (
                    1 >= $childLevel
                    || !isset($items[$items[$childId]['parent']])
                    || $level > $tree->getNodeLevel($items[$items[$childId]['parent']]['id'])
                ){
                    continue;
                }

                $this->assertTrue($level === $tree->getNodeLevel($parent));
            }
        }
    }

    /**
     * @param HTree $tree
     * @depends testGetItems
     */
    public function testGetParents($tree)
    {
        /** 是否只返回id OR item */
        for($i = 0; $i <= static::ITERATION_COUNT; $i++){
            foreach([0, 1] as $onlyId){
                $childId = $this->randItemId();
                $parents = $tree->getParents($childId, $onlyId);

                foreach($parents as $id => $parent){
                    if ($onlyId){
                        $this->assertTrue(HCheck::isDigit($parent));
                        $this->assertSame($id, $parent);
                    }//

                    else{
                        $this->assertSame($id, $parent['id']);
                        $this->assertSame($tree->getItem($id), $parent);
                    }

                    $this->assertParentChildAttributes($tree, $id, $childId);
                    $this->assertLevelRange($tree, $id, 1, ($tree->getNodeLevel($childId) - 1));
                }
            }
        }

        /** 开始 */
        for($i = 0; $i <= static::ITERATION_COUNT; $i++){
            foreach([1, 2, 3, 4, 5] as $startLevel){
                $childId = $this->randItemId();
                $parents = $tree->getParents($childId, true, $startLevel);

                foreach($parents as $id => $parent){
                    $this->assertParentChildAttributes($tree, $parent, $childId);
                    $this->assertLevelRange($tree, $id, $startLevel, ($tree->getNodeLevel($childId) - 1));
                }

                $this->assertLevelIsCoherent($tree, $parents);
            }
        }

        /** 开始和结束 */
        for($i = 0; $i <= static::ITERATION_COUNT; $i++){
            foreach([1, 2, 3, 4, 5] as $startLevel){
                foreach([1, 2, 3] as $limit){
                    $endLevel = $startLevel + $limit;

                    $childId = $this->randItemId();
                    $parents = $tree->getParents($childId, true, $startLevel, $endLevel);

                    foreach($parents as $id => $parent){
                        $this->assertParentChildAttributes($tree, $parent, $childId);
                        $this->assertLevelRange($tree, $id, $startLevel, $endLevel);
                    }

                    $this->assertLevelIsCoherent($tree, $parents);
                }
            }
        }

        /** 结束 */
        for($i = 0; $i <= static::ITERATION_COUNT; $i++){
            foreach([1, 2, 3, 4, 5] as $endLevel){
                $childId = $this->randItemId();
                $parents = $tree->getParents($childId, true, null, $endLevel);

                foreach($parents as $id => $parent){
                    $this->assertParentChildAttributes($tree, $parent, $childId);
                    $this->assertLevelRange($tree, $id, 1, $endLevel);
                }

                $this->assertLevelIsCoherent($tree, $parents);
            }
        }
    }


    /**
     * @param HTree $tree
     * @depends testGetItems
     */
    public function testTreeSort($tree)
    {
        $tree->treeSort(function ($item){
            return $item['id'];
        });

        $treeArray = $tree->getTree(static::CHILDREN_KEY, function ($item){
            return $item;
        });

        $this->recursiveTree($treeArray, function ($item){
            $ids = $_ids = HArray::getColumn($item[static::CHILDREN_KEY], 'id');
            sort($_ids);
            $this->assertSame($ids, $_ids);
        });

        return $tree;
    }


    /**
     * @param HTree $tree
     * @depends testTreeSort
     */
    public function testTreeMap($tree)
    {
        $items = [];
        foreach($tree->getItems() as $item){
            $item['_'] = $item['id'];
            $items[] = $item;
        }
        sort($items);

        $tree->treeMap(function ($item){
            $item['_'] = $item['id'];

            return $item;
        });

        $_items = $tree->getItems();
        sort($_items);

        $this->assertSame($_items, $items);

        return $tree;
    }


    /**
     * @param HTree $tree
     * @depends testTreeMap
     */
    public function testGetTree($tree)
    {
        $tree = $tree->getTree(static::CHILDREN_KEY, function ($item){
            return ['_' => time()];
        });

        $this->recursiveTree($tree, function ($item){
            $this->assertTrue(isset($item['_'], $item[static::CHILDREN_KEY]));
        });
    }

    /**
     * 递归遍历每一个元素
     *
     * @param $items
     * @param callable $callable
     */
    protected function recursiveTree($items, $callable)
    {
        if (empty($items)){
            return;
        }

        foreach($items as $id => $item){
            $callable($item);
            $this->recursiveTree($item[static::CHILDREN_KEY], $callable);
        }
    }

    /**
     * 检查父子级的属性是否正确
     *
     * @param HTree $tree
     * @param integer $parentId 父级ID
     * @param integer $childId 子级ID
     */
    protected function assertParentChildAttributes(HTree $tree, $parentId, $childId)
    {
        $this->assertTrue($tree->getNodeLevel($childId) > $tree->getNodeLevel($parentId));
        $this->assertTrue($tree->getNodeLeft($childId) > $tree->getNodeLeft($parentId));
        $this->assertTrue($tree->getNodeRight($childId) < $tree->getNodeRight($parentId));
    }

    /**
     * 检查id的层级是否连贯的
     *
     * @param HTree $tree
     * @param array $nids
     */
    protected function assertLevelIsCoherent(HTree $tree, array $nids)
    {
        $levels = HArray::getColumn($nids, function ($nid) use ($tree){
            return $tree->getNodeLevel($nid);
        });
        sort($levels);

        $start = null;
        foreach($levels as $level){
            if (null === $start){
                $start = $level;
                continue;
            }

            $this->assertTrue(1 == abs($level - $start));

            $start = $level;
        }
    }

    /**
     * 检查层级的范围
     *
     * @param HTree $tree
     * @param $nid
     * @param null|integer $startLevel
     * @param null|integer $endLevel
     */
    protected function assertLevelRange(HTree $tree, $nid, $startLevel = null, $endLevel = null)
    {
        $level = $tree->getNodeLevel($nid);
        $this->assertTrue(HCheck::isDigit($level, $startLevel, $endLevel));
    }

    /**
     * 随机提取一个id
     *
     * @return bool|mixed
     */
    protected function randItemId()
    {
        static $ids = [];

        if (empty($ids)){
            $ids = HArray::getColumn($this->getItems(), 'id');
        }

        return HRandom::arrayRemove($ids);
    }

    protected function getBadItems()
    {
        return [
            /* */ ['id' => 1, 'parent' => 2],
            /* */ /* */ ['id' => 2, 'parent' => 1],
        ];
    }

    protected function getItems()
    {
        return [
            /* */ ['id' => 1, 'parent' => 0],
            /* */ /* */ ['id' => 2, 'parent' => 1],
            /* */ /* */ /* */ ['id' => 3, 'parent' => 2],
            /* */ /* */ /* */ /* */ ['id' => 4, 'parent' => 3],
            /* */ /* */ /* */ /* */ /* */ ['id' => 5, 'parent' => 4],
            /* */ /* */ /* */ /* */ /* */ ['id' => 6, 'parent' => 4],
            /* */ /* */ /* */ /* */ /* */ ['id' => 7, 'parent' => 4],

            /* */ /* */ ['id' => 8, 'parent' => 1],
            /* */ /* */ /* */ ['id' => 9, 'parent' => 8],
            /* */ /* */ /* */ /* */ ['id' => 14, 'parent' => 9],
            /* */ /* */ /* */ /* */ ['id' => 15, 'parent' => 9],
            /* */ /* */ /* */ /* */ ['id' => 16, 'parent' => 9],
            /* */ /* */ /* */ /* */ ['id' => 17, 'parent' => 9],
            /* */ /* */ /* */ /* */ ['id' => 18, 'parent' => 9],

            /* */ /* */ /* */ ['id' => 10, 'parent' => 8],
            /* */ /* */ /* */ ['id' => 11, 'parent' => 8],
            /* */ /* */ /* */ ['id' => 12, 'parent' => 8],
            /* */ /* */ /* */ ['id' => 13, 'parent' => 8],

            /* */ /* */ ['id' => 19, 'parent' => 1],
            /* */ /* */ ['id' => 20, 'parent' => 1],
            /* */ /* */ ['id' => 21, 'parent' => 1],
            /* */ /* */ ['id' => 22, 'parent' => 1],
            /* */ /* */ ['id' => 23, 'parent' => 1],
            /* */ /* */ ['id' => 24, 'parent' => 1],
        ];
    }
}
