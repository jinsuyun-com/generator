<?php


namespace maodou\generator\generator\logic_skeleton\contract;

use maodou\base\base\GetterSetter;
use maodou\generator\generator\logic_skeleton\execute\model\MakeModel;

/**
 * Class MakeModel
 * @package maodou\generator\generator\logic_skeleton\contrac\ModelGenerateAbstract
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
