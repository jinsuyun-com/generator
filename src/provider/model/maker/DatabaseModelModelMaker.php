<?php

namespace maodou\generator\provider\model\maker;



use maodou\base\exception\AppException;
use maodou\base\utils\UtilsTools;
use maodou\generator\contract\model\maker\ModelMakerAbstract;
use maodou\generator\support\traits\ClassMakerTrait;

/**
 * Class DatabaseModelMaker
 * @package maodou\generator\provider\model\maker
 * @method DatabaseModelModelMaker setModelFullName(string $fullName)
 * @method DatabaseModelModelMaker setAddonFields(array $addonFields)
 * @method DatabaseModelModelMaker setModelConnection(string $modelConnection)
 * @method DatabaseModelModelMaker setModelPk(string $modelPk)
 * @method DatabaseModelModelMaker setBindName(string $bindName)
 * @method DatabaseModelModelMaker setModelFile(string $modelFile)
 * @method DatabaseModelModelMaker setExtendModel(string $model)
 */
class DatabaseModelModelMaker extends ModelMakerAbstract
{
    use ClassMakerTrait;
    protected string $defaultNamespace = 'app\model';
    protected array $addonFields = [];
    protected string $modelConnection = '';
    protected string $modelPk = 'id';
    protected string $bindName = '';
    protected array $modelFunctions = [];
    protected string $realExtend;

    protected function getStub(): string
    {
        return $this->getBaseStub().DIRECTORY_SEPARATOR.'Model.stub';
    }

    public function handle()
    {

        $this->modelFile = UtilsTools::replaceSeparator(app()->getRootPath().str_replace(app()->getRootPath(),'',UtilsTools::replaceSeparator($this->modelFile)));
        if(file_exists($this->modelFile)){
            throw new AppException('MakeModel:'.$this->modelFullName.'已存在');
        }
        $this->parseRealExtend();
        $this->parseModelProperty();
        // $this->parseModelFunctions();
        $pathInfo = pathinfo($this->modelFile);
        $namespaceInfo = pathinfo($this->modelFullName);
        $this->defaultNamespace = $namespaceInfo['dirname'];
        $this->modelName = $pathInfo['filename'];
        if (!is_dir($pathInfo['dirname'])) {
            mkdir($pathInfo['dirname'], 0755, true);
        }
        file_put_contents($this->modelFile, self::buildClass());
        return $this;
    }

    protected function buildClass(): array|bool|string
    {
        $stub = file_get_contents($this->getStub());
        if(empty($this->namespacePrefix)){
            $namespace = $this->defaultNamespace;
        }else{
            $namespace = $this->defaultNamespace.'\\'.$this->namespacePrefix;
        }

        if(is_array($this->importClass)&&count($this->importClass)>0){
            $importClass = implode(';'.PHP_EOL,$this->getUseImportClass());
            $importClass .=';'.PHP_EOL.PHP_EOL;
        }else{
            $importClass = '';
        }
        if(is_array($this->modelProperty)&&count($this->modelProperty)>0){
            $modelProperty = "\t";
            $modelProperty .= implode(PHP_EOL."\t",$this->modelProperty);
        }else{
            $modelProperty = '';
        }

        return str_replace(['{%namespace%}','{%importClass%}','{%className%}', '{%baseModel%}',  '{%modelProperty%}','{%modelFunctions%}'], [
            $namespace,
            $importClass,
            $this->modelName,
            $this->realExtend,
            $modelProperty,
            implode("\n",$this->modelFunctions)
        ], $stub);
    }

    protected function parseModelFunctions():self
    {
        return $this;
    }

    // 模型基类
    protected function parseRealExtend(): void
    {
        $classname = class_basename($this->modelFullName);
        $parentClassname = class_basename($this->extendModel);
        $this->importClass[] = $this->extendModel;
        if($classname !== $parentClassname){
            $this->realExtend =$parentClassname;
        }else{
            $this->realExtend ='\\'.UtilsTools::replaceNamespace($this->extendModel);
        }

    }

    protected function parseModelProperty(): void
    {
        $this->isSoftDelete();
        $this->parseBindName();
        $this->parseModelPk();
        $this->parseModelConnection();
        $this->isTimestamp();
    }

    protected function isSoftDelete():self
    {
        if(in_array('delete_time',$this->addonFields)){
            $this->modelProperty[] = 'use SoftDelete;';
            $this->importClass[] = 'think\model\concern\SoftDelete';
        }
        return $this;
    }

    protected function isTimestamp(): self
    {

        if(in_array('update_time',$this->addonFields)===false && in_array('create_time',$this->addonFields) ===false ){
            $this->modelProperty[] = 'protected $autoWriteTimestamp = false;';
        }else{
            $this->modelProperty[] = 'protected $autoWriteTimestamp = \'datetime\';';
            if(in_array('update_time',$this->addonFields)===false){
                $this->modelProperty[] = 'protected $updateTime = false;';
            }
            if(in_array('create_time',$this->addonFields)===false){
                $this->modelProperty[] = 'protected $createTime = false;';
            }
        }
        return $this;
    }


    protected function parseModelPk():self
    {
        if(empty($this->modelPk)===false && strtolower($this->modelPk)!=='id'){
            $this->modelProperty[] = 'protected $pk = \''.$this->modelPk.'\';';
        }
        return $this;
    }

    protected function parseBindName():self
    {
        if(empty($this->bindName) === false){
            $this->modelProperty[] = 'protected $name = \''.$this->bindName.'\';';
        }
        return $this;
    }

    protected function parseModelConnection():self
    {
        if(empty($this->modelConnection)===false){
            $this->modelProperty[] = 'protected $connection = \''.$this->modelConnection.'\';';
        }
        return $this;
    }
}
