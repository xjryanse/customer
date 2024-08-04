<?php

namespace xjryanse\customer\service\user;

/**
 * 
 */
trait DimTraits{
    /*
     * page_id维度列表
     * 20231124:只提取有效
     */
    public static function dimCustomerIdsByUserId($userId, $con = []){
        $con[]  = ['user_id','in',$userId];
        $con[]  = ['status','=',1];

        return self::column('customer_id',$con);
    }
    
    /*
     * page_id维度列表
     * 20231124:只提取有效
     */
    public static function dimManageCustomerIdsByUserId($userId, $con = []){
        $con[]  = ['is_manager','=',1];

        return self::dimCustomerIdsByUserId($userId, $con);
    }
}
