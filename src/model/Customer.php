<?php
namespace xjryanse\customer\model;

use xjryanse\system\service\SystemFileService;
use xjryanse\logic\Debug;
/**
 * 客户表
 */
class Customer extends Base
{
    public function setTable($tableArr = [],$con = [])
    {
        $prefix = config('database.prefix');
        $sql = "(SELECT
                a.* ,
                count(*) as SCuser_id
            FROM
                ydzb_customer AS a
                left JOIN ". $prefix ."customer_user AS b ON a.id = b.customer_id
                group by a.id) as eee";
        Debug::debug("sql",$sql);
        $this->table = $sql;
        return $this->table;
    }
    
    /**
     * 营业执照照片
     * @param type $value
     * @return type
     */
    public function getLicencePicAttr( $value )
    {
        return $value ? SystemFileService::getInstance( $value )->get() : $value ;
    }
    /**
     * 图片修改器，图片带id只取id
     * @param type $value
     * @throws \Exception
     */
    public function setLicencePicAttr( $value )
    {
        if((is_array($value)|| is_object($value)) && isset( $value['id'])){
            $value = $value['id'];
        }
        return $value;
    }


}