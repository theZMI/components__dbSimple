<?php

class MyDataBaseCache
{
    const IS_ACTIVE = true;
    const CACHE_DIR = 'tmp/db_cache/';

    public static function cache($key, $data)
    {
        $isGet = is_null($data);
        $isSet = !$isGet;

        $ret = '';

        if ($isSet) {
            self::set($key, $data);
        }
        if ($isGet) {
            $ret = self::get($key);
        }

        return $ret;
    }

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
