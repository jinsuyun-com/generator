<?php


namespace jsy\generator\builder;


class CodeBuilder
{
    /**
     * @desc 声明字符串变量
     * @param string $name
     * @param null $value
     * @param int $tabTimes
     * @param false $withLF
     * @return string
     */
    public static function stringVar( string$name,$value = null,int $tabTimes = 0,$withLF=false): string
    {
        $code = '';
        for ($i=0;$i<$tabTimes;$i++){
            $code .="\t";
        }
        $code .='$'.$name;
        if(is_null($value===false)){
            $code .=' = \''.$value.'\'';
        }
        $code .=';';
        return $withLF ? $code."\n" : $code;
    }

    /**
     * @desc 数字型变量
     * @param string $name
     * @param null $value
     * @param int $tabTimes
     * @param false $withLF
     * @return string
     */
    public static function numericVar(string $name,$value = null,int $tabTimes = 0,$withLF=false): string
    {
        $code = '';
        $code .= self::getTab($tabTimes);
        $code .='$'.$name;
        if(is_null($value===false)){
            $code .=' = '.$value;
        }
        $code .=';';
        return $withLF ? $code."\n" : $code;
    }

    /**
     * @desc 布尔型变量
     * @param string $name
     * @param bool $value
     * @param int $tabTimes
     * @param false $withLF
     * @return string
     */
    public static function booleanVar(string $name,bool $value,int $tabTimes = 0,$withLF=false): string
    {
        $code = '';
        $code .= self::getTab($tabTimes);
        $code .='$'.$name;
        $code .=' = '.strval($value);
        $code .=';';
        return $withLF ? $code."\n" : $code;
    }

    public static function arrayVar(?string $name,array $value =[],int $tabTimes = 0,$withLF=true,$subKey = false): string
    {
        $code = '';
        $code .= self::getTab($tabTimes);
        if(is_null($name)===false){
            if($subKey === false){
                $code .='$'.$name.' = ';
            }elseif(is_null($subKey)===false){
                $code .= $subKey.' = ';
            }
        }
        $code .=self::parseArray($value,$tabTimes,$withLF);
        if($subKey === false){
            $code .=';';
        }
        return $withLF ? $code."\n" : $code;
    }

    public static function parseArray($value,int $tabTimes = 0,$withLF=false): string
    {
        $code = '[';
        $code .= self::getTab($tabTimes);
        $startIndex = 0;
        foreach ($value as $index => $item){
            if($index !==$startIndex){
                if(is_int($index)){
                    $code .= $index.' => ';
                }else{
                    $code .= '\''.$index.'\' => ';
                }
            }
            switch (true){
                case is_numeric($item) || is_bool($item):
                    $code .= $item;
                    break;
                case is_null($item):
                    $code .= 'null';
                    break;
                case is_array($item):
                    $code .= self::parseArray($item,$tabTimes+1,true);
                    break;
                default:
                    $code .='\''.strval($item).'\'';
            }
            $startIndex++;
            if($startIndex < count($value)){
                $code .=',';
                if($withLF){
                    $code.="\n";
                }
            }
        }
        $code .= ']';
        return $code;
    }

    public static function getTab(int $tabTimes = 0): string
    {
        $tab = '';
        for ($i=0;$i<$tabTimes;$i++){
            $tab .="\t";
        }
        return $tab;
    }

    public static function getCodeLine(string $codeLine,int $tab = 0,bool $withLF = false): string
    {
        $code = '';
        for ($index = 0;$index < $tab;$index++){
            $code .="\t";
        }
        $code .= $codeLine;
        return $withLF ? $code."\n" : $code;
    }

    public static function varExport($var,string $type=null): ?string
    {
        $type = is_null($type) ? gettype($var) : $type;
        $type = strtolower($type);
        switch (true){
            case $type === 'array':
                return self::parseArray($var);
            case in_array($type,['int','float','integer','double']):
                return $var;
            case in_array($type,['bool','boolean']):
                return $var ? 'true' : 'false';
            case $type === 'null':
                return 'null';
            default:
                return '\''.(string)$var.'\'';
        }
    }
}
