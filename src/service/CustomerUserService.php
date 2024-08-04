<?php

namespace xjryanse\customer\service;

use xjryanse\logic\Arrays;
use xjryanse\logic\DataCheck;
use xjryanse\logic\Strings;
use xjryanse\order\service\OrderService;
use xjryanse\user\service\UserService;
use xjryanse\wechat\service\WechatWePubFansUserService;
use xjryanse\wechat\service\WechatWePubFansService;
use Exception;
/**
 * 客户用户表
 */
class CustomerUserService {

    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;
    use \xjryanse\traits\MainModelRamTrait;
    use \xjryanse\traits\MainModelCacheTrait;
    use \xjryanse\traits\MainModelCheckTrait;
    use \xjryanse\traits\MainModelGroupTrait;
    use \xjryanse\traits\MainModelQueryTrait;

    use \xjryanse\traits\MiddleModelTrait;
    use \xjryanse\traits\StaticModelTrait;

    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\customer\\model\\CustomerUser';
    //直接执行后续触发动作
    protected static $directAfter = true;
    
    use \xjryanse\customer\service\user\DimTraits;

    
    public static function extraDetails($ids) {
        return self::commExtraDetails($ids, function($lists) use ($ids){
            $userIds = array_column($lists,'user_id');
            $cond[]     = ['user_id','in',$userIds];
            $wechatWePubBindCounts  = WechatWePubFansUserService::mainModel()->where($cond)->group('user_id')->column('count(1) as number','user_id');            
            $cond[]     = ['customer_id','in',array_column($lists,'customer_id')];
            $baoTangCounts = OrderService::mainModel()->alias('a')->join('w_order_bao_bus b','a.id = b.order_id')
                    ->where($cond)->group('customer_id,user_id')->column('count(1) as number','concat(customer_id,user_id)');
            foreach ($lists as &$v) {
                //微信公众号绑定数
                $v['wechatWePubBindCount']  = Arrays::value($wechatWePubBindCounts, $v['user_id'],0);
                // 包车趟数
                $v['baoTangCount']          = Arrays::value($baoTangCounts, $v['customer_id'].$v['user_id'],0);
            }
            
            return $lists;
        });
    }
    
    /**
     * 用户的客户列表
     */
    public static function userCustomerIds($userId,$order=''){
        $con[] = ['user_id','in',$userId];
        return self::mainModel()->where($con)->order($order)->column('customer_id');
    }
    /**
     * 20240407
     * @param type $userId
     * @param type $customerType
     */
    public static function userCustomerIdWithType($userId, $customerType){
        $con[] = ['a.user_id','in',$userId];
        $con[] = ['b.customer_type','in',$customerType];
        $customerTable = CustomerService::getTable();
        return self::mainModel()->alias('a')
                ->join($customerTable.' b','a.customer_id=b.id')
                ->where($con)
                ->column('customer_id');
    }
    
    /**
     * 该用户是管理员的客户id数组
     * 用 dimManageCustomerIdsByUserId 替代
     * @param type $userId
     * @return type
     */
    public static function userManageCustomerIds($userId){
        $conCust[] = ['user_id','in',$userId];
        // 只提取管理员
        $conCust[] = ['is_manager', '=', 1];
        return self::mainModel()->where($conCust)->column('customer_id');
    }
    
    /**
     * 客户信息
     */
    public static function customerUserInfos($customerIds, $con = []){
        $con[]      = ['customer_id','in',$customerIds];
        $userTable  = UserService::getTable();
        return self::middleInfos('user_id', $userTable, $con,'nickname,realname,phone');
    }

    /**
     * 提取客户下挂管理员（一般用于消息推送）
     * @param type $customerId
     * @return type
     */
    public static function customerManageUserIds($customerId){
        $conCust[] = ['customer_id','in',$customerId];
        // 只提取管理员
        $conCust[] = ['is_manager', '=', 1];

        return self::staticConColumn('user_id', $conCust);
    }
    
    /**
     * 公司和用户进行绑定
     * @param type $customerId
     * @param type $userId
     */
    public static function bind( $customerId, $userId ){
        if(!$customerId || !$userId){
            return false;
        }

        $con[] = ['customer_id','=',$customerId];
        $con[] = ['user_id','=',$userId];
        $res    = self::find( $con ,86400);
        //没有绑定记录则绑定，有绑定记录不处理
        if(!$res){
            $joinData['customer_id']    = $customerId;
            $joinData['user_id']        = $userId;
            $res = self::save( $joinData );
        }
        return $res;
    }
    /**
     * 20230522:执行ajax绑定
     * @param type $id
     * @param type $param
     */
    public static function doPhoneBind($id,$param){
        DataCheck::must($param, ['phone','customer_id']);
        $phone          = Arrays::value($param, 'phone');
        $customerId     = Arrays::value($param, 'customer_id');
        if(!Strings::isPhone($phone)){
            throw new Exception('手机号码格式错误');
        }
        $data['realname']   = Arrays::value($param, 'realname');
        return self::phoneBind($customerId, $phone, $data);
    }
    /**
     * 20230522：手机号码绑定
     */
    public static function phoneBind($customerId, $phone, $userData = []){
        $userId = UserService::phoneGetId($phone, $userData);
        return self::bind($customerId, $userId);
    }
    //2023-01-08：
    public static function extraAfterSave(&$data, $uuid) {
        UserService::clearCommExtraDetailsCache($data['user_id']);
    }
    //2023-01-08：
    public static function extraPreUpdate(&$data, $uuid) {
        $info = self::getInstance($uuid)->get();
        if($info['user_id']){
            UserService::clearCommExtraDetailsCache($info['user_id']);
        }
    }
    //2023-01-08：
    public static function extraAfterUpdate(&$data, $uuid) {
        if($data['user_id']){
            UserService::clearCommExtraDetailsCache($data['user_id']);
        }
    }
    
    public function extraAfterDelete($data){
    //2023-01-08：
        UserService::clearCommExtraDetailsCache($data['user_id']);
    }
    
    public function extraPreDelete(){
        self::checkTransaction();
        $info = $this->get();
        if(!$info){
            throw new Exception('信息不存在'.$this->uuid);
        }
        $con[] = ['customer_id','=',$info['customer_id']];
        $con[] = ['user_id','=',$info['user_id']];
        $res = OrderService::mainModel()->where($con)->count(1);
        if($res){
            // 20240510：业务员解绑
            // throw new Exception('该用户单位有订单，不可删');
        }
    }
    /**
     * 20230424：获取用户所属的客户，部门
     * @param type $customerId
     * @param type $userId
     */
    public static function getDeptId($customerId, $userId){
        $con[] = ['customer_id','=',$customerId];
        $con[] = ['user_id','=',$userId];
        $info = self::staticConFind($con);
        return $info ? $info['dept_id'] : '';
    }

    /**
     *
     */
    public function fId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     *
     */
    public function fAppId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     *
     */
    public function fCompanyId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 客户id
     */
    public function fCustomerId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 用户id
     */
    public function fUserId() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 职位
     */
    public function fJob() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 排序
     */
    public function fSort() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 状态(0禁用,1启用)
     */
    public function fStatus() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 有使用(0否,1是)
     */
    public function fHasUsed() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 锁定（0：未锁，1：已锁）
     */
    public function fIsLock() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 锁定（0：未删，1：已删）
     */
    public function fIsDelete() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 备注
     */
    public function fRemark() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 创建者，user表
     */
    public function fCreater() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 更新者，user表
     */
    public function fUpdater() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 创建时间
     */
    public function fCreateTime() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 更新时间
     */
    public function fUpdateTime() {
        return $this->getFFieldValue(__FUNCTION__);
    }

}
