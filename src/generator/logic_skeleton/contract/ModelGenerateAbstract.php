<?php


namespace jsy\generator\generator\logic_skeleton\contract;

use jsy\base\base\GetterSetter;
use jsy\generator\generator\logic_skeleton\execute\model\MakeModel;

/**
 * Class MakeModel
 * @method MakeModel setTimestampFields(array $timestampFields)
 * @method MakeModel setJsonFields(array $jsonFields)
 * @method MakeModel setIsJsonAssoc(bool $isJsonAssoc)
 * @method MakeModel setModelConnection(string $modelConnection)
 * @method MakeModel setModelPk(string $modelPk)
 * @method MakeModel setModelTable(string $modelTable)
 */
abstract class ModelGenerateAbstract extends MakeClassAbstract
{
    use GetterSetter;
    protected $modelPath;
    protected $timestampFields;
    protected $jsonFields;
    protected $isJsonAssoc;
    protected $modelConnection;
    protected $modelPk;
    protected $modelTable;
    public function getModelPath()
    {
        return $this->modelPath;
    }
}
