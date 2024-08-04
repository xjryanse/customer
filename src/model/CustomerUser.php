<?php
namespace xjryanse\customer\model;

/**
 * 客户用户
 */
class CustomerUser extends Base
{
    use \xjryanse\traits\ModelUniTrait;
    // 20230516:数据表关联字段
    public static $uniFields = [
        [
            'field'     =>'customer_id',
            'uni_name'  =>'customer',
            'uni_field' =>'id',
            'del_check' => true,
            'del_msg'   => '该单位有绑定用户，请先解绑'
        ],
        [
            'field'     =>'user_id',
            'uni_name'  =>'user',
            'uni_field' =>'id',
            'del_check' => true
        ]
    ];

}