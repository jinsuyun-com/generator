<?php


namespace maodou\generator\parser\support\model;


class ModelPropertyConfig
{
    const CONFIG = [
        'connection'         => [
            'title' => '数据库连接',
            'type'  => ['string']
        ],
        'pk'                 => [
            'title' => '数据表主键',
            'type'  => ['string', 'array']
        ],
        'table'              => [
            'title' => '数据表名称',
            'type'  => ['string']
        ],
        'jsonAssoc'          => [
            'title' => '格式化JSON',
            'type'  => ['boolean']
        ],
        'autoWriteTimestamp' => [
            'title' => '自动时间戳',
            'type'  => ['boolean']
        ],
        'dataFormat'         => [
            'title' => '时间格式',
            'type'  => ['string', 'boolean']
        ],
        'json'               => [
            'title' => 'JSON字段',
            'type'  => ['array']
        ],
        'defaultSoftDelete'  => [
            'title' => '软删除默认值',
            'type'  => ['integer']
        ],
        'softDelete'         => [
            'title' => '模型软删除',
            'type'  => ['boolean']
        ],
        'deleteTime'         => [
            'title' => '软删除字段',
            'type'  => ['string']
        ],
        'updateTime'         => [
            'title' => '更新时间字段',
            'type'  => ['string', 'boolean']
        ],
        'createTime'         => [
            'title' => '创建时间字段',
            'type'  => ['string', 'boolean']
        ],
        'field'              => [
            'title' => '允许写入字段',
            'type'  => ['array']
        ],
        'type'               => [
            'title' => '字段类型',
            'type'  => ['array']
        ],
        'disuse'             => [
            'title' => '废弃字段',
            'type'  => ['array']
        ],
        'readonly'           => [
            'title' => '只读字段',
            'type'  => ['array']
        ],
        'remark'             => [
            'title' => '模型备注',
            'type'  => ['string']
        ]
    ];

    const EVENT = [
        'onAfterRead'     => '查询后',
        'onBeforeInsert'  => '新增前',
        'onAfterInsert'   => '新增后',
        'onBeforeUpdate'  => '更新前',
        'onAfterUpdate'   => '更新后',
        'onBeforeWrite'   => '写入前',
        'onAfterWrite'    => '写入后',
        'onBeforeDelete'  => '删除前',
        'onAfterDelete'   => '删除后',
        'onBeforeRestore' => '恢复前',
        'onAfterRestore'  => '恢复后',
    ];
}
