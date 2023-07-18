<?php


namespace maodou\generator\generator\logic_skeleton\execute\model;


use maodou\base\base\GetterSetter;
use maodou\base\exception\AppException;
use maodou\base\utils\UtilsTools;
use maodou\generator\generator\logic_skeleton\contract\MakeClassAbstract;
use think\helper\Str;

/**
 * Class MakeModel
 * @package maodou\generator\generator\logic_skeleton\execute\model
 * @method MakeModel setModelFullName(string $fullName)
 * @method MakeModel setExtendModel(string $model)
 * @method MakeModel setAddonFields(array $addonFields)
 * @method MakeModel setIsJsonAssoc(bool $isJsonAssoc)
 * @method MakeModel setModelConnection(string $modelConnection)
 * @method MakeModel setModelPk(string $modelPk)
 * @method MakeModel setBindName(string $bindName)
 * @method MakeModel setModelFile(string $modelFile)
 * @method MakeModel setIsSubModel(bool $isSubModel)
 * @method MakeModel setIsDatabase(bool $isDatabase)
 */
class MakeModel extends MakeClassAbstract
{
    use GetterSetter;
    protected string $defaultNamespace = 'app\model';
    protected bool $isDatabase = true;
    protected array $importClass = [];
    protected string $extendModel;
    protected string $modelFullName;
    protected string $modelName;
    protected array $modelProperty = [];
    protected array $modelFunctions = [];
    protected string $namespacePrefix = '';
    protected string $realExtend;
    protected string $modelPath = '';
    protected array $addonFields = [];
    protected string $modelConnection = '';
    protected string $modelPk = 'id';
    protected string $bindName = '';
    protected string $modelFile = '';
    protected bool $isSubModel = false;
    public function getModelPath()
    {
        return $this->modelPath;
    }
    protected function getStub()
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
        $this->parseModelFunctions();
        $pathInfo = pathinfo($this->modelFile);
        $namespaceInfo = pathinfo($this->modelFullName);
        $this->defaultNamespace = $namespaceInfo['dirname'];
        $this->modelName = $pathInfo['filename'];
        if (!is_dir($pathInfo['dirname'])) {
            mkdir($pathInfo['dirname'], 0755, true);
        }
        file_put_contents($this->modelFile, self::buildClass());
        include $this->modelFile;
        return $this;
    }

    protected function buildClass()
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
        if($this->isDatabase){
            return $this;
        }
        $this->modelFunctions[] = "\tpublic function toArray(): array\n\t{\n\t\t// TODO: Implement toArray() method.\n\t}";
        return $this;
    }

    // 模型基类
    protected function parseRealExtend()
    {
        if($this->isSubModel === false){
            $extendArray = explode('\\',$this->extendModel);
            $this->realExtend = array_pop($extendArray);
            $this->importClass[] = $this->extendModel;
        }else{
            $this->realExtend = '\\'.UtilsTools::replaceNamespace($this->extendModel);
        }
    }

    protected function parseModelProperty()
    {
            $this->isSoftDelete();
            $this->parseBindName();
            $this->parseModelPk();
            $this->parseModelConnection();
            $this->isTimestamp();
    }

    protected function isSoftDelete():self
    {
        if($this->isSubModel || $this->isDatabase === false){
            return $this;
        }
        if(in_array('delete_time',$this->addonFields)){
            $this->modelProperty[] = 'use SoftDelete;';
            $this->importClass[] = 'think\model\concern\SoftDelete';
        }
        return $this;
    }

    protected function isTimestamp(): self
    {
        if($this->isSubModel || $this->isDatabase === false){
            return $this;
        }


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
        if($this->isSubModel || $this->isDatabase === false){
            return $this;
        }
        if(empty($this->modelPk)===false && strtolower($this->modelPk)!=='id'){
            $this->modelProperty[] = 'protected $pk = \''.$this->modelPk.'\';';
        }
        return $this;
    }

    protected function parseBindName():self
    {
        if($this->isDatabase === false || empty($this->bindName)){
            return $this;
        }
        $this->modelProperty[] = 'protected $name = \''.$this->bindName.'\';';
        return $this;
    }

    protected function parseModelConnection():self
    {
        if($this->isSubModel || $this->isDatabase === false){
            return $this;
        }
        if(empty($this->modelConnection)===false){
            $this->modelProperty[] = 'protected $connection = \''.$this->modelConnection.'\';';
        }
        return $this;
    }
}
