<?php
namespace Cyantree\Mosaic;

use Cyantree\Mosaic\Types\FourSide;
use Cyantree\Mosaic\Types\TwoSide;

class Tools
{
    public static function encodeFilePath($path)
    {
        return utf8_decode($path);
    }

    public static function decodeFilePath($path)
    {
        return utf8_encode($path);
    }

    public static function singleOrMultipleValuesToObject($value, $defaultSingleKey = 'default')
    {
        if (is_object($value)) {
            return $value;
        }
        elseif (is_scalar($value)) {
            $result = new \stdClass();
            $result->{$defaultSingleKey} = $value;

            return $result;
        }
        elseif (is_array($value)) {
            $result = new \stdClass();

            foreach ($value as $k => $v) {
                $result->{$k} = $v;
            }

            return $result;
        }

        return null;
    }

    public static function singleOrMultipleValuesToArray($value)
    {
        if (is_array($value)) {
            return $value;
        }
        elseif (is_scalar($value)) {
            return [$value];
        }
        elseif (is_object($value)) {
            return get_object_vars($value);
        }

        return null;
    }

    public static function matches($filters, $expression)
    {
        $filters = self::singleOrMultipleValuesToObject($filters);

        foreach ($filters as $filter) {
            $isNot = substr($filter, 0, 1) == '!';

            if ($isNot) {
                $filter = substr($filter, 1);
            }

            if (substr($filter, 0, 1) == '/') {
                if ($isNot xor preg_match('/' . trim(substr($filter, 2), '/') . '/', $expression)) {
                    return true;
                }
            }
            else {
                if ($isNot xor fnmatch($filter, $expression)) {
                    return true;
                }
            }
        }

        return false;
    }

    /** @return TwoSide */
    public static function decodeTwoSide($value)
    {
        if ($value === null) {
            return null;
        }

        // formats: 1, [1] or [1,2]
        if (is_object($value)) {
            $atbValue = get_object_vars($value);
        }
        else {
            $atbValue = [$value];
        }

        $s = new TwoSide();

        switch (count($atbValue)) {
            case 2:
                $s->sideA = $atbValue[0];
                $s->sideB = $atbValue[1];
                break;
            default:
                $s->sideA = $s->sideB = $atbValue[0];
                break;
        }

        return $s;
    }

    /** @return FourSide|null */
    public static function decodeFourSide($value)
    {
        if ($value === null) {
            return null;
        }

        // formats: 1, [1], [1,2] or [1,2,3,4]
        if (is_object($value)) {
            $atbValue = get_object_vars($value);
        }
        else {
            $atbValue = [$value];
        }

        $s = new FourSide();

        switch (count($atbValue)) {
            case 4:
                $s->top = $atbValue[0];
                $s->right = $atbValue[1];
                $s->bottom = $atbValue[2];
                $s->left = $atbValue[3];
                break;
            case 2:
                $s->top = $s->bottom = $atbValue[0];
                $s->left = $s->right = $atbValue[1];
                break;
            case 3:
                $s->top = $atbValue[0];
                $s->right = $s->left = $atbValue[1];
                $s->bottom = $atbValue[2];
                break;
            default:
                $s->top = $s->right = $s->bottom = $s->left = $atbValue[0];
                break;
        }

        return $s;
    }

    public static function deepCloneObject($o)
    {
        $result = clone $o;
        $list = [$result];

        while ($i = array_pop($list)) {
            foreach ($i as $k => $v) {
                if (is_object($v)) {
                    $i->{$k} = clone $v;

                    $list[] = $i->{$k};
                }
            }
        }

        return $result;
    }

    public static function extendObject($object, $extensionArray, $targetNodeOrKey = null)
    {
        // Works correct?
        if ($targetNodeOrKey === null) {
            $targetNodeOrKey = $object;
        }
        elseif (is_string($targetNodeOrKey)) {
            $path = explode('.', $targetNodeOrKey);

            $node = $object;

            foreach ($path as $p) {
                if (!isset($node->{$p})) {
                    $node->{$p} = new \stdClass();
                }

                $node = $node->{$p};
            }

            $targetNodeOrKey = $node;
        }

        $extends = [
                [$targetNodeOrKey, $extensionArray]
        ];

        while($extend = array_pop($extends)) {
            foreach ($extend[1] as $key => $value) {
                if (is_object($value)) {
                    // Objects will be merged
                    if (!isset($extend[0]->{$key})) {
                        $extend[0]->{$key} = new \stdClass();
                    }

                    $extends[] = [$extend[0]->{$key}, $value];
                }
                else {
                    // Arrays and scalar values will be replaced
                    $extend[0]->{$key} = $value;
                }
            }
        }
    }
}
