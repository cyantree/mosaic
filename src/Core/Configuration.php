<?php
namespace Cyantree\Mosaic\Core;

use Cyantree\Grout\Filter\ArrayFilter;
use Cyantree\Mosaic\Tools;
use Cyantree\Mosaic\Types\FourSide;
use Cyantree\Mosaic\Types\TwoSide;
use Symfony\Component\Yaml\Yaml;

class Configuration
{
    public $configuration;

    const VALUE_UNSET = -93785582716;
    const VALUE_REQUIRED = -93785582715;

    public function __construct($configuration = null)
    {
        if ($configuration === null) {
            $configuration = new \stdClass();
        }

        $this->configuration = $configuration;
    }

    public static function createFromFile($path)
    {
        $config = new Configuration();
        $config->load($path);

        return $config;
    }

    public function load($path)
    {
        if (!is_file($path)) {
            throw new \Exception('Configuration file does not exist: ' . $path);
        }

        $configuration = Yaml::parse(file_get_contents($path));
        $this->configuration = json_decode(json_encode($configuration));
    }

    public function extend($array, $targetNodeOrKey = null)
    {
        Tools::extendObject($this->configuration, $array, $targetNodeOrKey);
    }

    public function getSubConfiguration($key)
    {
        $c = new Configuration();
        $c->configuration = $this->get($key);

        return $c;
    }

    public function getNode($key, $default = null, $getCopy = false)
    {
        $val = $this->get($key);

        if ($val === self::VALUE_UNSET) {
            if ($default === null) {
                return new \stdClass();
            }
            else {
                $val = $default;
            }
        }

        if ($getCopy) {
            return Tools::deepCloneObject($val);
        }

        return $val;
    }

    public function has($key)
    {
        $path = explode('.', $key);

        $node = $this->configuration;

        while ($key = array_shift($path)) {
            if (!isset($node->{$key})) {
                return false;
            }

            $node = $node->{$key};
        }

        return true;
    }

    public function get($key, $default = self::VALUE_UNSET)
    {
        $path = explode('.', $key);

        $node = $this->configuration;

        while ($key = array_shift($path)) {
            if (!isset($node->{$key})) {
                if ($default == self::VALUE_REQUIRED) {
                    throw new \Exception('Key ' . $key . ' is required');
                }

                return $default;
            }

            $node = $node->{$key};
        }

        return $node;
    }

    public function getSoft($key, $default = null)
    {
        return $this->get($key, $default === self::VALUE_REQUIRED ? self::VALUE_REQUIRED : self::VALUE_UNSET);
    }

    public function getString($key, $default = null)
    {
        $val = $this->getSoft($key, $default);
        if ($val === self::VALUE_UNSET) {
            return $default;
        }

        return strval($val);
    }

    public function getInt($key, $default = 0)
    {
        $val = $this->getSoft($key, $default);
        if ($val === self::VALUE_UNSET) {
            return $default;
        }

        return intval($val);
    }

    public function getFloat($key, $default = 0)
    {
        $val = $this->getSoft($key, $default);
        if ($val === self::VALUE_UNSET) {
            return $default;
        }

        return floatval($val);
    }

    public function getBool($key, $default = false)
    {
        $val = $this->getSoft($key, $default);

        if ($val === self::VALUE_UNSET) {
            return $default;
        }

        return !!$val;
    }

    public function getArray($key, $default = null)
    {
        $val = $this->getSoft($key, $default);
        if ($node === self::VALUE_UNSET) {
            return $default;
        }

        return get_object_vars($node);
    }

    public function getArrayFilter($key, $defaultContents = null)
    {
        $filter = new ArrayFilter();

        $node = $this->getSoft($key, $defaultContents);
        if ($node === self::VALUE_UNSET) {
            $filter->setData($defaultContents);
        }
        else {
            $filter->setData(get_object_vars($node));
        }

        return $filter;
    }

    public function getColor($key, $default = null)
    {
        $color = $this->getSoft($key, $default);
        if ($color === self::VALUE_UNSET) {
            return $default;
        }

        /*
         * Supported formats:
         * RRGGBBAA (FF08CA30)
         * [RRGGBB,A] ([FF08CA,150], [FF08CA,.4], [FF08CA,1f])
         * [R,G,B,A] ([255,0,255,128], [.4, .3, 1f, .7], [160, 120, 88, .3])
         * No Alpha: Alpha = 1
         */

        if (is_string($color)) {
            // Parse string values

            $components = explode(',', $color);

            $color = str_split($components[0], 2);
            foreach ($color as $i => $v) {
                $color[$i] = hexdec($v);
            }

            if (count($components) > 1) {
                $component = $components[1];

                if (is_string($component) || is_float($component)) {
                    $component = round(floatval($component) * 255);
                }

                $color[] = $component;
            }
        }
        else {
            // Normalize array values
            $color = get_object_vars($color);

            foreach ($color as $i => $v) {
                if (is_string($v) || is_float($v)) {
                    $color[$i] = round(floatval($v) * 255);
                }
            }
        }

        if (!isset($color[3])) {
            // Set default alpha

            $color[3] = 255;
        }

        //            A            B                  G                   R
        return $color[3] + ($color[2] << 8) + ($color[1] << 16) + ($color[0] * pow(2, 24)); // Doesn't lead to overflow
    }

    /** @return FourSide */
    public function getFourSide($key, $defaultValue = null)
    {
        $value = $this->getSoft($key, $defaultValue);

        if ($value === null || $value === Configuration::VALUE_UNSET) {
            $value = $defaultValue;
        }

        return Tools::decodeFourSide($value);
    }

    /** @return TwoSide */
    public function getTwoSide($key, $defaultValue = null)
    {
        $value = $this->getSoft($key, $defaultValue);

        if ($value === null || $value === Configuration::VALUE_UNSET) {
            $value = $defaultValue;
        }

        return Tools::decodeTwoSide($value);
    }
}
