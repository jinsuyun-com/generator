<?php


namespace maodou\generator\parser\support;


use maodou\base\utils\UtilsTools;

class ParseUtil
{
    public static function parseNamespace(string $class,$root=false): string
    {
        $namespace = trim($class);
        $namespace = preg_replace('/\/+/','\\',$namespace);
        $namespace = preg_replace('/\\\\+/','\\',$namespace);
        $namespace = trim($namespace,'\\');
        if($root) {
            $namespace = '\\'.$namespace;
        }
        return $namespace;
    }

    public static function clearContext(string $codeLine):string
    {
        $codeLine =  preg_replace("/\t/","",$codeLine);
        $codeLine = preg_replace("/\r\n/","",$codeLine);
        $codeLine = preg_replace("/\r/","",$codeLine);
        return preg_replace("/\n/","",$codeLine);
    }

    public static function clearLf(string $codeLine):string
    {
        $codeLine = preg_replace("/\r\n/","",$codeLine);
        $codeLine = preg_replace("/\r/","",$codeLine);
        return preg_replace("/\n/","",$codeLine);
    }



}
