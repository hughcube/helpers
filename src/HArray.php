<?php

namespace HughCube\Helpers;

use InvalidArgumentException;

class HArray
{
    /**
     * 方法rand,随机在数组里面取一部分元素;
     *
     * @param array $array [必选]    传入的数组;
     * @param number int [必选] 返回的元素个数;
     *
     * @return array:
     */
    public static function randSlice(array $array, $number)
    {
        shuffle($array);

        return array_slice($array, 0, $number, true);
    }

    /**
     * Merges two or more arrays into one recursively.
     * If each array has an element with the same string key value, the latter
     * will overwrite the former (different from array_merge_recursive).
     * Recursive merging will be conducted if both arrays have an element of array
     * type and are having the same key.
     * For integer-keyed elements, the elements from the latter array will
     * be appended to the former array.
     * @param array[] ...$items
     * arrays via third argument, fourth argument etc.
     * @return array the merged array (the original arrays are not changed.)
     */
    public static function merge()
    {
        $args = func_get_args();
        $res = array_shift($args);
        while(!empty($args)){
            $next = array_shift($args);
            foreach($next as $k => $v){
                if (is_int($k)){
                    if (isset($res[$k])){
                        $res[] = $v;
                    }else{
                        $res[$k] = $v;
                    }
                }elseif (is_array($v) && isset($res[$k]) && is_array($res[$k])){
                    $res[$k] = self::merge($res[$k], $v);
                }else{
                    $res[$k] = $v;
                }
            }
        }

        return $res;
    }

    /**
     * Retrieves the value of an array element or object property with the given key or property name.
     * If the key does not exist in the array or object, the default value will be returned instead.
     *
     * The key may be specified in a dot format to retrieve the value of a sub-array or the property
     * of an embedded object. In particular, if the key is `x.y.z`, then the returned value would
     * be `$array['x']['y']['z']` or `$array->x->y->z` (if `$array` is an object). If `$array['x']`
     * or `$array->x` is neither an array nor an object, the default value will be returned.
     * Note that if the array already has an element `x.y.z`, then its value will be returned
     * instead of going through the sub-arrays. So it is better to be done specifying an array of key names
     * like `['x', 'y', 'z']`.
     *
     * Below are some usage examples,
     *
     * ```php
     * // working with array
     * $username = \yii\helpers\ArrayHelper::getValue($_POST, 'username');
     * // working with object
     * $username = \yii\helpers\ArrayHelper::getValue($user, 'username');
     * // working with anonymous function
     * $fullName = \yii\helpers\ArrayHelper::getValue($user, function ($user, $defaultValue) {
     *     return $user->firstName . ' ' . $user->lastName;
     * });
     * // using dot format to retrieve the property of embedded object
     * $street = \yii\helpers\ArrayHelper::getValue($users, 'address.street');
     * // using an array of keys to retrieve the value
     * $value = \yii\helpers\ArrayHelper::getValue($versions, ['1.0', 'date']);
     * ```
     *
     * @param array|object $array array or object to extract value from
     * @param string|\Closure|array $key key name of the array element, an array of keys or property name of the object,
     * or an anonymous function returning the value. The anonymous function signature should be:
     * `function($array, $defaultValue)`.
     * The possibility to pass an array of keys is available since version 2.0.4.
     * @param mixed $default the default value to be returned if the specified array key does not exist. Not used when
     * getting value from an object.
     * @return mixed the value of the element if found, default value otherwise
     * @throws InvalidArgumentException if $array is neither an array nor an object.
     */
    public static function getValue($array, $key, $default = null)
    {
        if ($key instanceof \Closure){
            return $key($array, $default);
        }

        if (is_array($key)){
            $lastKey = array_pop($key);
            foreach($key as $keyPart){
                $array = static::getValue($array, $keyPart);
            }
            $key = $lastKey;
        }

        if (is_array($array) && (isset($array[$key]) || array_key_exists($key, $array))){
            return $array[$key];
        }

        if (($pos = strrpos($key, '.')) !== false){
            $array = static::getValue($array, substr($key, 0, $pos), $default);
            $key = substr($key, $pos + 1);
        }

        if (is_object($array)){
            // this is expected to fail if the property does not exist, or __get() is not implemented
            // it is not reliably possible to check whether a property is accessable beforehand
            return $array->$key;
        }elseif (is_array($array)){
            return (isset($array[$key]) || array_key_exists($key, $array)) ? $array[$key] : $default;
        }else{
            return $default;
        }
    }

    /**
     * Removes an item from an array and returns the value. If the key does not exist in the array, the default value
     * will be returned instead.
     *
     * Usage examples,
     *
     * ```php
     * // $array = ['type' => 'A', 'options' => [1, 2]];
     * // working with array
     * $type = \yii\helpers\ArrayHelper::remove($array, 'type');
     * // $array content
     * // $array = ['options' => [1, 2]];
     * ```
     *
     * @param array $array the array to extract value from
     * @param string $key key name of the array element
     * @param mixed $default the default value to be returned if the specified key does not exist
     * @return mixed|null the value of the element if found, default value otherwise
     */
    public static function remove(&$array, $key, $default = null)
    {
        if (is_array($array) && (isset($array[$key]) || array_key_exists($key, $array))){
            $value = $array[$key];
            unset($array[$key]);

            return $value;
        }

        return $default;
    }

    /**
     * Indexes and/or groups the array according to a specified key.
     * The input should be either multidimensional array or an array of objects.
     *
     * The $key can be either a key name of the sub-array, a property name of object, or an anonymous
     * function that must return the value that will be used as a key.
     *
     * $groups is an array of keys, that will be used to group the input array into one or more sub-arrays based
     * on keys specified.
     *
     * If the `$key` is specified as `null` or a value of an element corresponding to the key is `null` in addition
     * to `$groups` not specified then the element is discarded.
     *
     * For example:
     *
     * ```php
     * $array = [
     *     ['id' => '123', 'data' => 'abc', 'device' => 'laptop'],
     *     ['id' => '345', 'data' => 'def', 'device' => 'tablet'],
     *     ['id' => '345', 'data' => 'hgi', 'device' => 'smartphone'],
     * ];
     * $result = ArrayHelper::index($array, 'id');
     * ```
     *
     * The result will be an associative array, where the key is the value of `id` attribute
     *
     * ```php
     * [
     *     '123' => ['id' => '123', 'data' => 'abc', 'device' => 'laptop'],
     *     '345' => ['id' => '345', 'data' => 'hgi', 'device' => 'smartphone']
     *     // The second element of an original array is overwritten by the last element because of the same id
     * ]
     * ```
     *
     * An anonymous function can be used in the grouping array as well.
     *
     * ```php
     * $result = ArrayHelper::index($array, function ($element) {
     *     return $element['id'];
     * });
     * ```
     *
     * Passing `id` as a third argument will group `$array` by `id`:
     *
     * ```php
     * $result = ArrayHelper::index($array, null, 'id');
     * ```
     *
     * The result will be a multidimensional array grouped by `id` on the first level, by `device` on the second level
     * and indexed by `data` on the third level:
     *
     * ```php
     * [
     *     '123' => [
     *         ['id' => '123', 'data' => 'abc', 'device' => 'laptop']
     *     ],
     *     '345' => [ // all elements with this index are present in the result array
     *         ['id' => '345', 'data' => 'def', 'device' => 'tablet'],
     *         ['id' => '345', 'data' => 'hgi', 'device' => 'smartphone'],
     *     ]
     * ]
     * ```
     *
     * The anonymous function can be used in the array of grouping keys as well:
     *
     * ```php
     * $result = ArrayHelper::index($array, 'data', [function ($element) {
     *     return $element['id'];
     * }, 'device']);
     * ```
     *
     * The result will be a multidimensional array grouped by `id` on the first level, by the `device` on the second one
     * and indexed by the `data` on the third level:
     *
     * ```php
     * [
     *     '123' => [
     *         'laptop' => [
     *             'abc' => ['id' => '123', 'data' => 'abc', 'device' => 'laptop']
     *         ]
     *     ],
     *     '345' => [
     *         'tablet' => [
     *             'def' => ['id' => '345', 'data' => 'def', 'device' => 'tablet']
     *         ],
     *         'smartphone' => [
     *             'hgi' => ['id' => '345', 'data' => 'hgi', 'device' => 'smartphone']
     *         ]
     *     ]
     * ]
     * ```
     *
     * @param array $array the array that needs to be indexed or grouped
     * @param string|\Closure|null $key the column name or anonymous function which result will be used to index the array
     * @param string|string[]|\Closure[]|null $groups the array of keys, that will be used to group the input array
     * by one or more keys. If the $key attribute or its value for the particular element is null and $groups is not
     * defined, the array element will be discarded. Otherwise, if $groups is specified, array element will be added
     * to the result array without any key. This parameter is available since version 2.0.8.
     * @return array the indexed and/or grouped array
     */
    public static function index($array, $key, $groups = [])
    {
        $result = [];
        $groups = (array)$groups;

        foreach($array as $element){
            $lastArray = &$result;

            foreach($groups as $group){
                $value = static::getValue($element, $group);
                if (!array_key_exists($value, $lastArray)){
                    $lastArray[$value] = [];
                }
                $lastArray = &$lastArray[$value];
            }

            if ($key === null){
                if (!empty($groups)){
                    $lastArray[] = $element;
                }
            }else{
                $value = static::getValue($element, $key);
                if ($value !== null){
                    if (is_float($value)){
                        $value = (string)$value;
                    }
                    $lastArray[$value] = $element;
                }
            }
            unset($lastArray);
        }

        return $result;
    }

    /**
     * Returns the values of a specified column in an array.
     * The input array should be multidimensional or an array of objects.
     *
     * For example,
     *
     * ```php
     * $array = [
     *     ['id' => '123', 'data' => 'abc'],
     *     ['id' => '345', 'data' => 'def'],
     * ];
     * $result = ArrayHelper::getColumn($array, 'id');
     * // the result is: ['123', '345']
     *
     * // using anonymous function
     * $result = ArrayHelper::getColumn($array, function ($element) {
     *     return $element['id'];
     * });
     * ```
     *
     * @param array $array
     * @param string|\Closure $name
     * @param boolean $keepKeys whether to maintain the array keys. If false, the resulting array
     * will be re-indexed with integers.
     * @return array the list of column values
     */
    public static function getColumn($array, $name, $keepKeys = true)
    {
        $result = [];
        if ($keepKeys){
            foreach($array as $k => $element){
                $result[$k] = static::getValue($element, $name);
            }
        }else{
            foreach($array as $element){
                $result[] = static::getValue($element, $name);
            }
        }

        return $result;
    }

    /**
     * Builds a map (key-value pairs) from a multidimensional array or an array of objects.
     * The `$from` and `$to` parameters specify the key names or property names to set up the map.
     * Optionally, one can further group the map according to a grouping field `$group`.
     *
     * For example,
     *
     * ```php
     * $array = [
     *     ['id' => '123', 'name' => 'aaa', 'class' => 'x'],
     *     ['id' => '124', 'name' => 'bbb', 'class' => 'x'],
     *     ['id' => '345', 'name' => 'ccc', 'class' => 'y'],
     * ];
     *
     * $result = ArrayHelper::map($array, 'id', 'name');
     * // the result is:
     * // [
     * //     '123' => 'aaa',
     * //     '124' => 'bbb',
     * //     '345' => 'ccc',
     * // ]
     *
     * $result = ArrayHelper::map($array, 'id', 'name', 'class');
     * // the result is:
     * // [
     * //     'x' => [
     * //         '123' => 'aaa',
     * //         '124' => 'bbb',
     * //     ],
     * //     'y' => [
     * //         '345' => 'ccc',
     * //     ],
     * // ]
     * ```
     *
     * @param array $array
     * @param string|\Closure $from
     * @param string|\Closure $to
     * @param string|\Closure $group
     * @return array
     */
    public static function map($array, $from, $to, $group = null)
    {
        $result = [];
        foreach($array as $element){
            $key = static::getValue($element, $from);
            $value = static::getValue($element, $to);
            if ($group !== null){
                $result[static::getValue($element, $group)][$key] = $value;
            }else{
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Checks if the given array contains the specified key.
     * This method enhances the `array_key_exists()` function by supporting case-insensitive
     * key comparison.
     * @param string $key the key to check
     * @param array $array the array with keys to check
     * @param boolean $caseSensitive whether the key comparison should be case-sensitive
     * @return boolean whether the array contains the specified key
     */
    public static function keyExists($key, $array, $caseSensitive = true)
    {
        if ($caseSensitive){
            return array_key_exists($key, $array);
        }else{
            foreach(array_keys($array) as $k){
                if (strcasecmp($key, $k) === 0){
                    return true;
                }
            }

            return false;
        }
    }

    /**
     * Sorts an array of objects or arrays (with the same structure) by one or several keys.
     * @param array $array the array to be sorted. The array will be modified after calling this method.
     * @param string|\Closure|array $key the key(s) to be sorted by. This refers to a key name of the sub-array
     * elements, a property name of the objects, or an anonymous function returning the values for comparison
     * purpose. The anonymous function signature should be: `function($item)`.
     * To sort by multiple keys, provide an array of keys here.
     * @param integer|array $direction the sorting direction. It can be either `SORT_ASC` or `SORT_DESC`.
     * When sorting by multiple keys with different sorting directions, use an array of sorting directions.
     * @param integer|array $sortFlag the PHP sort flag. Valid values include
     * `SORT_REGULAR`, `SORT_NUMERIC`, `SORT_STRING`, `SORT_LOCALE_STRING`, `SORT_NATURAL` and `SORT_FLAG_CASE`.
     * Please refer to [PHP manual](http://php.net/manual/en/function.sort.php)
     * for more details. When sorting by multiple keys with different sort flags, use an array of sort flags.
     * @throws InvalidArgumentException if the $direction or $sortFlag parameters do not have
     * correct number of elements as that of $key.
     */
    public static function multisort(&$array, $key, $direction = SORT_ASC, $sortFlag = SORT_REGULAR)
    {
        $keys = is_array($key) ? $key : [$key];
        if (empty($keys) || empty($array)){
            return;
        }
        $n = count($keys);
        if (is_scalar($direction)){
            $direction = array_fill(0, $n, $direction);
        }elseif (count($direction) !== $n){
            throw new InvalidArgumentException('The length of $direction parameter must be the same as that of $keys.');
        }
        if (is_scalar($sortFlag)){
            $sortFlag = array_fill(0, $n, $sortFlag);
        }elseif (count($sortFlag) !== $n){
            throw new InvalidArgumentException('The length of $sortFlag parameter must be the same as that of $keys.');
        }
        $args = [];
        foreach($keys as $i => $key){
            $flag = $sortFlag[$i];
            $args[] = static::getColumn($array, $key);
            $args[] = $direction[$i];
            $args[] = $flag;
        }

        // This fix is used for cases when main sorting specified by columns has equal values
        // Without it it will lead to Fatal Error: Nesting level too deep - recursive dependency?
        $args[] = range(1, count($array));
        $args[] = SORT_ASC;
        $args[] = SORT_NUMERIC;

        $args[] = &$array;
        call_user_func_array('array_multisort', $args);
    }

    /**
     * Returns a value indicating whether the given array is an associative array.
     *
     * An array is associative if all its keys are strings. If `$allStrings` is false,
     * then an array will be treated as associative if at least one of its keys is a string.
     *
     * Note that an empty array will NOT be considered associative.
     *
     * @param array $array the array being checked
     * @param boolean $allStrings whether the array keys must be all strings in order for
     * the array to be treated as associative.
     * @return boolean whether the array is associative
     */
    public static function isAssociative($array, $allStrings = true)
    {
        if (!is_array($array) || empty($array)){
            return false;
        }

        if ($allStrings){
            foreach($array as $key => $value){
                if (!is_string($key)){
                    return false;
                }
            }

            return true;
        }else{
            foreach($array as $key => $value){
                if (is_string($key)){
                    return true;
                }
            }

            return false;
        }
    }

    /**
     * Returns a value indicating whether the given array is an indexed array.
     *
     * An array is indexed if all its keys are integers. If `$consecutive` is true,
     * then the array keys must be a consecutive sequence starting from 0.
     *
     * Note that an empty array will be considered indexed.
     *
     * @param array $array the array being checked
     * @param boolean $consecutive whether the array keys must be a consecutive sequence
     * in order for the array to be treated as indexed.
     * @return boolean whether the array is associative
     */
    public static function isIndexed($array, $consecutive = false)
    {
        if (!is_array($array)){
            return false;
        }

        if (empty($array)){
            return true;
        }

        if ($consecutive){
            return array_keys($array) === range(0, count($array) - 1);
        }else{
            foreach($array as $key => $value){
                if (!is_int($key)){
                    return false;
                }
            }

            return true;
        }
    }

    /**
     * Check whether an array or [[\Traversable]] contains an element.
     *
     * This method does the same as the PHP function [in_array()](http://php.net/manual/en/function.in-array.php)
     * but additionally works for objects that implement the [[\Traversable]] interface.
     * @param mixed $needle The value to look for.
     * @param array|\Traversable $haystack The set of values to search.
     * @param boolean $strict Whether to enable strict (`===`) comparison.
     * @return boolean `true` if `$needle` was found in `$haystack`, `false` otherwise.
     * @throws InvalidArgumentException if `$haystack` is neither traversable nor an array.
     * @see http://php.net/manual/en/function.in-array.php
     * @since 2.0.7
     */
    public static function isIn($needle, $haystack, $strict = false)
    {
        if ($haystack instanceof \Traversable){
            foreach($haystack as $value){
                if ($needle == $value && (!$strict || $needle === $value)){
                    return true;
                }
            }
        }elseif (is_array($haystack)){
            return in_array($needle, $haystack, $strict);
        }else{
            throw new InvalidArgumentException('Argument $haystack must be an array or implement Traversable');
        }

        return false;
    }

    /**
     * 给数组排序
     *
     * @param array $array
     * @param array $order ['key1' => SORT_DESC, 'key2' => SORT_ASC]
     */
    public static function orderBy(array &$array, array $order = [])
    {
        return uasort($array, function ($a, $b) use ($order){
            foreach($order as $key => $type){
                $aValue = static::getValue($a, $key);
                $bValue = static::getValue($b, $key);

                if ($aValue == $bValue){
                    continue;
                }

                if (SORT_DESC == $type){
                    return ($aValue < $bValue) ? 1 : -1;
                }elseif (SORT_ASC == $type){
                    return ($aValue < $bValue) ? -1 : 1;
                }
            }

            return 0;
        });
    }

    /**
     * 数组按照指定的一列的指定顺序排序
     *
     * For example,
     *
     * ```php
     * $array = [
     *     ['id' => '123', 'name' => 'aaa', 'class' => 'x'],
     *     ['id' => '124', 'name' => 'bbb', 'class' => 'x'],
     *     ['id' => '345', 'name' => 'ccc', 'class' => 'y'],
     * ];
     *
     * $result = static::sortByColumn($array, 'name', ['bbb', 'ccc', 'aaa']);
     * the result is:
     * [
     *     ['id' => '124', 'name' => 'bbb', 'class' => 'x'],
     *     ['id' => '345', 'name' => 'ccc', 'class' => 'y'],
     *     ['id' => '123', 'name' => 'aaa', 'class' => 'x'],
     * ]
     *
     * @param array $array
     * @param string|\Closure $columnName
     * @param array $values
     * @return array
     */
    public static function sortFollowColumn(array $array, $columnName, array $values)
    {
        $items = [];

        foreach($values as $value){
            foreach($array as $item){
                if ($value == static::getValue($item, $columnName)){
                    $items[] = $item;
                }
            }
        }

        return $items;
    }
}
