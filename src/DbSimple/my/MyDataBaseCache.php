<?php

class MyDataBaseCache
{
    const IS_ACTIVE = true;

    const CACHE_DIR = 'tmp/db_cache/';

    public static function Cache($key, $data)
    {
        $isGet = is_null($data);
        $isSet = !$isGet;

        $ret = '';

        if ($isSet) {
            self::Set($key, $data);
        }
        if ($isGet) {
            $ret = self::Get($key);
        }

        return $ret;
    }

    private static function GetFileNameByKey($key)
    {
        return BASEPATH . self::CACHE_DIR . $key[0] . '/' . $key[1] . '/' . $key;
    }

    private static function Set($key, $data)
    {
        $file = self::GetFileNameByKey($key);

        return FileSys::WriteFile($file, serialize($data));
    }

    private static function Has($key)
    {
        // Если выключен то кеш не будет находиться
        if (self::IS_ACTIVE == false) {
            return false;
        }

        $file = self::GetFileNameByKey($key);
        $ret  = is_readable($file);

        return $ret;
    }

    private static function Get($key)
    {
        $ret = null;
        if (self::Has($key)) {
            $file = self::GetFileNameByKey($key);
            $data = FileSys::ReadFile($file);
            $ret  = unserialize($data);
        }

        return $ret;
    }
}
