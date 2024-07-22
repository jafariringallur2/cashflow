<?php

namespace Ocw\AgGrid\Utilities;
use Illuminate\Contracts\Support\Arrayable;


class Helper
{

    public static function includeInArray($item, $array)
    {
        if (self::isItemOrderInvalid($item, $array)) {
            return array_merge($array, [$item['name'] => $item['content']]);
        }

        $count = 0;
        $last  = $array;
        $first = [];
        foreach ($array as $key => $value) {
            if ($count == $item['order']) {
                return array_merge($first, [$item['name'] => $item['content']], $last);
            }

            unset($last[$key]);
            $first[$key] = $value;

            $count++;
        }
    }

    /**
     * Check if item order is valid.
     *
     * @param  array  $item
     * @param  array  $array
     * @return bool
     */
    protected static function isItemOrderInvalid($item, $array)
    {
        return $item['order'] === false || $item['order'] >= count($array);
    }

    /**
     * Determines if content is callable or blade string, processes and returns.
     *
     * @param  mixed  $content  Pre-processed content
     * @param  array  $data  data to use with blade template
     * @param  mixed  $param  parameter to call with callable
     * @return mixed
     */
    public static function compileContent($content, array $data, $param)
    {
        if (is_string($content)) {
            return static::compileBlade($content, static::getMixedValue($data, $param));
        } elseif (is_callable($content)) {
            return $content($param);
        }

        return $content;
    }
    /**
     * Parses and compiles strings by using Blade Template System.
     *
     * @param  string  $str
     * @param  array  $data
     * @return mixed
     *
     * @throws \Exception
     */
    public static function compileBlade($str, $data = [])
    {
        if (view()->exists($str)) {
            return view($str, $data)->render();
        }

        ob_start() && extract($data, EXTR_SKIP);
        eval('?>' . app('blade.compiler')->compileString($str));
        $str = ob_get_contents();
        ob_end_clean();

        return $str;
    }

    /**
     * Get a mixed value of custom data and the parameters.
     *
     * @param  array  $data
     * @param  mixed  $param
     * @return array
     */
    public static function getMixedValue(array $data, $param)
    {
        $casted = self::castToArray($param);

        $data['model'] = $param;

        foreach ($data as $key => $value) {
            if (isset($casted[$key])) {
                $data[$key] = $casted[$key];
            }
        }

        return $data;
    }

    public static function castToArray($param)
    {
        if ($param instanceof \stdClass) {
            $param = (array) $param;

            return $param;
        }

        if ($param instanceof Arrayable) {
            return $param->toArray();
        }

        return $param;
    }

    /**
     * Converts array object values to associative array.
     *
     * @param  mixed  $row
     * @param  array  $filters
     * @return array
     */
    public static function convertToArray($row)
    {
        $data = $row instanceof Arrayable ? $row->toArray() : (array) $row;

        foreach ($data as &$value) {
            if (is_object($value) || is_array($value)) {
                $value = self::convertToArray($value);
            }

            unset($value);
        }

        return $data;
    }
}
