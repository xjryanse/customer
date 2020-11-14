<?php
namespace xjryanse\customer\service;

/**
 * 客户表
 */
class CustomerService
{
    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;

    protected static $mainModel;
    protected static $mainModelClass    = '\\xjryanse\\customer\\model\\Customer';

   /**
     * 根据统一社会信用代码取信息
     * @param type $licenceNo   统一社会信用代码
     * @param type $con         查询条件
     * @return type
     */
    public static function getByLicenceNo( $licenceNo ,$con=[])
    {
        $con[] = [ 'licence', '=', $licenceNo ];
        $id = self::mainModel()->where($con)->value('id');
        //拿取带详情的数据
        return $id ? self::getInstance( $id )->info() : [] ;
    }
    
    /**
     * 客户信息保存，并返回id
     * @param type $userId  发布用户
     * @param type $data    商户信息
     * @param type $con
     * @return type
     */
    public static function customerSaveGetId( $userId, $data = [] ,$con = [])
    {
        //当前系统统一社会信用代码唯一
        $tmInfo = self::getByLicenceNo( $data['licence'] ,$con );
        if( $tmInfo ){
            $data['id'] = $tmInfo['id']; 
        }
        $data['owner_id'] = $userId;
        //信息保存
        $mainId   = self::customerSave( $data );
        return $mainId;
    }
    
    /**
     * 客户保存
     */
    public static function customerSave( $data )
    {
        $remain = [];
        if( isset($data['id']) && $data['id'] ){
            $con[]  = ['id','=',$data['id']];
            $remain = self::find($con);
        }
        $mainId = '';
        if( $remain ){
            $mainId = $data['id'];
            //更新
            $res = self::getInstance( $data['id'] )->update( $data );
        } else {
            //新增
            if(isset($data['id'])){         unset( $data['id'] );   }

            $res = self::save( $data );
            $mainId = $res['id'];
        }

        return $mainId;
    }
}
