<?php

namespace Geoportal\Suche;

class ArrayParser {

    public function count($path, $array) {
        $element = $this->get($path, $array);

        if(is_array($element)) {
            return count($element);
        } else if(is_string($element)) {
            return strlen($element);
        }
    }

    public function isEmpty($path, $array) {
        $value = $this->get($path, $array);
        return empty($value);
    }

    public function exists($path, $array, $find = null) {
        $result = $this->get($path, $array);

        if(!is_null($result)) {
            if($find) {
                if((is_array($result) && in_array($find, $result))
                    || (is_array($result) && isset($result[$find]))
                    || (is_string($result) && strstr($result, $find) !== false)) {
                    return true;
                }
            } else {
                return true;
            }
        }

        return false;
    }

    public function append($path, $value, &$array) {
        $element = $this->get($path, $array);

        if($element === null) {
            $element = $value;
        } else if(is_array($element)) {
            $element[] = $value;
        } else {
            $element .= $value;
        }

        return $this->set($path, $element, $array);
    }

    public function delete($path, &$array) {
        $this->recursiveDelete(
            explode(":",$this->cleanPath($path)),
            $array
        );
    }

    public function set($path, $value, &$array) {
        $this->recursiveSet(
            explode(":",$this->cleanPath($path)),
            $value,
            $array
        );

        return $value;
    }

    public function get($path, $array) {
        if(empty($path) || trim($path) === "") {
            return $array;
        }

        return $this->recursiveGet(
            explode(":", $this->cleanPath($path)),
            $array
        );
    }

    public function array_merge_recursive($arr1, $arr2) {
        foreach($arr2 as $key => $val) {
            if(array_key_exists($key, $arr1) && is_array($val))
                $arr1[$key] = $this->array_merge_recursive($arr1[$key], $arr2[$key]);
            else
                $arr1[$key] = $val;
        }
        return $arr1;
    }

    private function cleanPath($path) {
        return trim($path, ":");
    }

    private function recursiveGet($keys, $array) {
        $key = array_shift($keys);

        if(count($keys) === 0 && isset($array[$key])) {
            return $array[$key];
        } else if(count($keys) >= 1 && isset($array[$key])) {
            return $this->recursiveGet($keys, $array[$key]);
        }

        return null;
    }

    private function recursiveSet($path, $value, &$array) {
        $key = array_shift($path);
        if(count($path) == 0) {
            $array[$key] = $value;
        } else if(count($path) > 0) {
            if(!isset($array[$key])) {
                $array[$key] = null;
            }
            $this->recursiveSet($path, $value, $array[$key]);
        }
    }

    private function recursiveDelete($path, &$array) {
        $key = array_shift($path);

        if(isset($array[$key]) && count($path) == 0) {
            unset($array[$key]);
        } else if(isset($array[$key]) && count($path) > 0) {
            $this->recursiveDelete($path, $array[$key]);
        }
    }
}
