<?php


namespace jsy\generator\support\fields\model;


use jsy\generator\support\fields\ModelSchemaField;

class ModelFieldModel extends ModelSchemaField
{
    protected bool $isSelf = false;



    protected bool $isTable = false;
    /**
     * 属性类型 getter setter relation
     * @var string|null
     */
    protected string|null $propertyType = null;
    /**
     * 关联类型 hasOne hasMany hasManyThrough hasOneThrough belongsToMany morphMany morphTo
     * @var string|null
     */
    protected string|null $relationType = null;

    protected string|null $relationModel = null;

    protected string|null $throughModel = null;

    public function toArray(): array
    {
        $data = parent::toArray();
        $data['is_table'] = $this->isTable();
        $data['is_self'] = $this->isSelf();
        $data['is_disuse'] = $this->isDisuse();
        $data['property_type'] = $this->getPropertyType();
        $data['relation_type'] = $this->getRelationType();
        $data['relation_model'] = $this->getRelationModel();
        $data['through_model'] = $this->getThroughModel();
        return $data;
    }



    public function isTable():bool
    {
        return $this->isTable;
    }

    public function isSelf():bool
    {
        return $this->isSelf;
    }

    public function getPropertyType():?string
    {
        return $this->propertyType;
    }

    public function getThroughModel():?string
    {
        return $this->throughModel;
    }

    public function getRelationType():?string
    {
        return $this->relationType;
    }

    public function getRelationModel():?string
    {
        return $this->relationModel;
    }

    public function setIsTable(bool $isTable):self
    {
        $this->isTable = $isTable;
        return $this;
    }



    // 是否为模型本身属性字段（包含trait），false时表示继承自父类
    public function setIsSelf(bool $isSelf):self
    {
        $this->isSelf = $isSelf;
        return $this;
    }

    // 属性类型
    public function setPropertyType(string $propertyType): self
    {
        $this->propertyType = $propertyType;
        return $this;
    }

    // 关联类型
    public function setRelationType(string $relationType):self
    {
        $this->relationType = $relationType;
        return $this;
    }

    // 关联模型
    public function setRelationModel(string $relationModel,string|null $throughModel = null):self
    {
        $this->relationModel = $relationModel;
        if(is_null($throughModel)){
            $this->throughModel = $throughModel;
        }
        return $this;
    }

}
