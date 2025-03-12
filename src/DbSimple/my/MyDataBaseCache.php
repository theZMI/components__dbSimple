<?php

class MyDataBaseCache
{
    const IS_ACTIVE = true;
    const CACHE_DIR = 'tmp/db_cache__as1553xzvghXP/';

    const CACHE_TIME = 30; // В секундах

    public static function fileCache($key, $data = null)
    {
        $isGet = is_null($data);
        $isSet = !$isGet;
        $file  = BASEPATH . self::CACHE_DIR . "{$key}.txt";

        if ($isSet) {
            FileSys::writeFile($file, $data);
            return $data;
        }
        if ($isGet) {
            $r = FileSys::readFile($file);
            if (!$r) {
                return '';
            }

            $data = unserialize($r);
            if ($data['ttl'] < time()) { // Is expired?
                return '';
            }

            return $r;
        }
        return '';
    }

    public static function staticCache($key, $data = null)
    {
        $isGet = is_null($data);
        $isSet = !$isGet;
        static $cache = [];

        if ($isSet) {
            $cache[$key] = $data;
            return $data;
        }
        if ($isGet) {
            return $cache[$key] ?? '';
        }
        return '';
    }

    public static function cacheTag($query, $time = self::CACHE_TIME)
    {
        $h = intval($time / 3600);
        $time -= 3600*$h;
        $m = intval($time / 60);
        $time -= 60*$m;
        $s = intval($time);
        $hms = +$h . 'h ' . +$m . 'm ' . +$s;
        return "-- CACHE: {$hms}" . PHP_EOL . $query;
    }

//    public static function cache($key, $data = null)
//    {
//        $isGet = is_null($data);
//        $isSet = !$isGet;
//
//        $ret = '';
//
//        if ($isSet) {
//            self::set($key, $data);
//        }
//        if ($isGet) {
//            $ret = self::get($key);
//        }
//
//        return $ret;
//    }

    private static function getFileNameByKey($key)
    {
        return BASEPATH . self::CACHE_DIR . $key[0] . '/' . $key[1] . '/' . $key;
    }

    private static function set($key, $data)
    {
        $file = self::getFileNameByKey($key);

        return FileSys::writeFile($file, serialize($data));
    }

    private static function has($key)
    {
        // Если выключен то кеш не будет находиться
        if (self::IS_ACTIVE == false) {
            return false;
        }

        $file = self::getFileNameByKey($key);
        $ret  = is_readable($file);

        return $ret;
    }

    private static function get($key)
    {
        $ret = null;
        if (self::has($key)) {
            $file = self::getFileNameByKey($key);
            $data = FileSys::readFile($file);
            $ret  = unserialize($data);
        }

        return $ret;
    }
}
