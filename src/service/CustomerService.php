<?php

namespace xjryanse\customer\service;

use xjryanse\bus\service\BusService;
use xjryanse\bus\service\BusFixService;
use xjryanse\bus\service\BusTypeCustomerService;
use app\location\service\LocationService;
use xjryanse\user\service\UserService;
use xjryanse\order\service\OrderService;
use xjryanse\customer\service\CustomerUserService;
use xjryanse\finance\service\FinanceStatementOrderService;
use xjryanse\finance\service\FinanceAccountLogService;
use xjryanse\system\service\SystemCompanyDeptService;
use xjryanse\system\service\SystemCompanyService;
use xjryanse\logic\Arrays;
use xjryanse\logic\Arrays2d;
use xjryanse\logic\DbOperate;
use xjryanse\logic\Gps;
use think\facade\Cache;
use Exception;

/**
 * 客户表
 */
class CustomerService {

    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;
    use \xjryanse\traits\MainModelRamTrait;
    use \xjryanse\traits\MainModelCacheTrait;
    use \xjryanse\traits\MainModelCheckTrait;
    use \xjryanse\traits\MainModelGroupTrait;
    use \xjryanse\traits\MainModelQueryTrait;

    use \xjryanse\traits\RamModelTrait;
    use \xjryanse\traits\ObjectAttrTrait;

    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\customer\\model\\Customer';
    //直接执行后续触发动作
    protected static $directAfter = true;
    // 定义对象的属性
    protected $objAttrs = [];
    // 定义对象是否查询过的属性
    protected $hasObjAttrQuery = [];
    // 定义对象属性的配置数组
    protected static $objAttrConf = [
        //20220814 financeManageAccount
        'financeManageAccount' => [
            'class' => '\\xjryanse\\finance\\service\\FinanceManageAccountService',
            'keyField' => 'belong_table_id',
            'master' => true
        ],
    ];
    
    public static function extraPreSave(&$data, $uuid) {
        if (!Arrays::value($data, 'customer_name')) {
            throw new Exception('公司名称必须');
        }
        $con[] = ['customer_name', '=', Arrays::value($data, 'customer_name')];
        // if(self::find($con)){
        if (self::ramConCount($con)) {
            throw new Exception('公司名称已存在-' . $data['customer_name']);
        }
        return $data;
    }

    /**
     * 额外输入信息
     */
    public static function extraAfterSave(&$data, $uuid) {
        self::getInstance($uuid)->frBindCompanyUser();
    }

    public static function extraAfterUpdate(&$data, $uuid) {
        self::getInstance($uuid)->frBindCompanyUser();
    }

    public function extraPreDelete() {
        self::checkTransaction();
        $con[] = ['customer_id', '=', $this->uuid];
        $res = OrderService::mainModel()->where($con)->count(1);
        if ($res) {
            throw new Exception('该单位有订单，不可删');
        }
        $rr = FinanceStatementOrderService::mainModel()->where($con)->count(1);
        if ($rr) {
            throw new Exception('该单位有账单，不可删');
        }

        $rb = FinanceAccountLogService::mainModel()->where($con)->count(1);
        if ($rb) {
            throw new Exception('该单位有收付款记录，不可删');
        }
        $fix = BusFixService::mainModel()->where('fix_customer_id', $this->uuid)->count(1);
        if ($fix) {
            throw new Exception('该单位有车辆维修记录，不可删');
        }
    }

    public function extraAfterDelete() {
        $con[] = ['customer_id', '=', $this->uuid];
        if (!$this->get(0)) {
            //删除用户的关联
            CustomerUserService::mainModel()->where($con)->delete();
        }
    }

    /**
     * 法人绑定为公司用户
     */
    public function frBindCompanyUser() {
        $info = $this->get(true);
        if ($info['fr_mobile']) {
            $data['nickname'] = $info['fr_name'];
            $data['realname'] = $info['fr_name'];
            $userId = UserService::phoneGetId($info['fr_mobile'], $data);
            if ($userId) {
                CustomerUserService::bind($this->uuid, $userId);
            }
        }
    }
    
    /**
     * 20230618：用于车队在线调车:带地理位置定位
     * @param type $con
     * @return type
     */
    public static function fleetArrWithUserLocation() {
        $con[] = ['company_id', '=', session(SESSION_COMPANY_ID)];
        $con[] = ['customer_type', '=', 'fleet'];
        $con[] = ['status', '=', 1];
        //默认带数据权限
        $conAll     = array_merge($con, self::commCondition());
        // 20230327:最原始，比较快
        $listsObj   = self::where($conAll)->select();
        $lists      = $listsObj ? $listsObj->toArray() : [];
        // 获取用户的位置信息
        $userId = session(SESSION_USER_ID);
        $userLocation = LocationService::userLastLocation($userId);
        // 针对用户，计算地理位置
        if($userLocation){
            foreach($lists as &$v){
                // 用户定位
                $uLng = Arrays::value($userLocation, 'longitude');
                $uLat = Arrays::value($userLocation, 'latitude');
                // 车队定位
                $cLng = Arrays::value($v, 'longitude');
                $cLat = Arrays::value($v, 'latitude');
                // 计算距离
                $v['distance']  = $uLng && $cLng ? Gps::getDistance($uLng, $uLat, $cLng, $cLat) : 0;
                $v['disKilo']   = round($v['distance'] / 1000, 2);
                // 距离近的排前面
                $v['sort']      = $v['distance'] ? $v['distance'] * 1000 + $v['sort'] : 9999999 * 1000 + $v['sort'];
            }
            // 升序，近在前
            $lists = Arrays2d::sort($lists, 'sort');
        }
        
        // $lists = self::paginate($con,'status desc,sort',1000);
        $ids = $lists ? array_column($lists, 'id') : [];

        $cone[] = ['is_external', '=', 1];
        $cone[] = ['status', '=', 1];
        $customerUserList = CustomerUserService::customerUserInfos($ids, $cone);
        // 20230327:车型
        $customerBusTypeList = BusTypeCustomerService::customerBusTypeInfos($ids);

        foreach ($lists as &$v) {
            $conV   = [];
            $conV[] = ['customer_id', '=', $v['id']];
            // 客户业务员
            $v['customerUsers']     = Arrays2d::listFilter($customerUserList, $conV);
            // 客户车型
            $v['customerBusTypes']  = Arrays2d::listFilter($customerBusTypeList, $conV);
        }

        return $lists;
    }

    /**
     * 20230522：客户管理员视角
     * @param type $con
     */
    public static function paginateForCustomerManager($con) {
        $conCust[] = ['user_id', '=', session(SESSION_USER_ID)];
        // 只提取管理员
        $conCust[] = ['is_manager', '=', 1];
        $ids = CustomerUserService::mainModel()->where($conCust)->column('customer_id');

        $con[] = ['id', 'in', $ids];
        $lists = self::paginateRaw($con);
        return $lists;
    }

//    /**
//     * 额外详情信息
//     */
//    public static function extraDetail(&$item, $uuid) {
//        $cacheKey = "CustomerService.extraDetail";
//        if(!$item){ return false;}
//        self::commExtraDetail($item, $uuid);        
//        //用户量数据统计
//        $userStatics = Cache::get( $cacheKey );
//        if(!$userStatics){
//            $userStatics = CustomerUserService::mainModel()->group('customer_id')->column('count(1) as userCount',"customer_id");
//            Cache::set($cacheKey,$userStatics,2);
//        }
//        $item->SCuser_id = Arrays::value($userStatics, $uuid,0);
//        return $item;
//    }

    public static function extraDetails($ids) {
        return self::commExtraDetails($ids, function($lists) use ($ids) {
                    //业务员数
                    // $userArr = CustomerUserService::groupBatchCount('customer_id', $ids);
                    //包车订单数
                    $conOrder[] = ['order_type', '=', 'bao'];
                    $conOrder[] = ['is_cancel', '=', '0'];
                    $baoOrderArr = OrderService::groupBatchCount('customer_id', $ids, $conOrder);
                    //剩余未收
                    $baoNeedPayArr = OrderService::groupBatchSum('customer_id', $ids, 'remainNeedPay', $conOrder);
                    // 单位的车辆数-外调车用
                    // $busCountArr = BusService::groupBatchCount('customer_id', $ids);
                    // 部门数量
                    // $deptCountArr = SystemCompanyDeptService::groupBatchCount('bind_customer_id', $ids);

                    foreach ($lists as &$v) {
                        // 20230317:车辆数
                        // $v['busCount'] = Arrays::value($busCountArr, $v['id'], 0);
                        //业务员人数
                        // $v['userCounts'] = Arrays::value($userArr, $v['id'], 0);
                        //包车订单数
                        $v['baoOrderCounts'] = Arrays::value($baoOrderArr, $v['id'], 0);
                        //包车未收款
                        $v['baoNeedPay'] = Arrays::value($baoNeedPayArr, $v['id'], 0);
                        // 部门数量
                        // $v['deptCount'] = Arrays::value($deptCountArr, $v['id'], 0);
                        // 有营业执照
                        $v['hasLicence'] = $v['licence'] ? 1 : 0;
                        // 有爱企查编号
                        $v['hasAiqicha'] = $v['aiqicha'] ? 1 : 0;
                        // 有官网站
                        $v['hasWebsite'] = $v['website'] ? 1 : 0;
                    }
                    return $lists;
                }, true);
    }

    /**
     * 20230605：外调车客户单位
     * @param type $con
     * @param type $order
     * @param type $perPage
     * @param type $having
     * @param type $field
     * @param type $withSum
     * @return type
     */
    public static function paginateForDiaoBus($con = [], $order = '', $perPage = 10, $having = '', $field = "*", $withSum = false) {
        $tableName = DbOperate::prefix() . 'bus';
        $service = DbOperate::getService($tableName);
        if (!class_exists($service)) {
            return [];
        }
        $ids = $service::column('distinct customer_id');
        $con[] = ['id', 'in', $ids];

        return self::paginateX($con, $order, $perPage, $having, $field, $withSum);
        // return self::commPaginate($con, $order, $perPage, $having, $field);
    }

    /**
     * 根据统一社会信用代码取信息
     * @param type $licenceNo   统一社会信用代码
     * @param type $con         查询条件
     * @return type
     */
    public static function getByLicenceNo($licenceNo, $con = []) {
        $con[] = ['licence', '=', $licenceNo];
        $id = self::mainModel()->where($con)->value('id');
        //拿取带详情的数据
        return $id ? self::getInstance($id)->info() : [];
    }

    /**
     * 客户信息保存，并返回id
     * @param type $userId  发布用户
     * @param type $data    商户信息
     * @param type $con
     * @return type
     */
    public static function customerSaveGetId($userId, $data = [], $con = []) {
        //当前系统统一社会信用代码唯一
        $tmInfo = self::getByLicenceNo($data['licence'], $con);
        if ($tmInfo) {
            $data['id'] = $tmInfo['id'];
        }
        $data['owner_id'] = $userId;
        //信息保存
        $mainId = self::customerSave($data);
        return $mainId;
    }

    /**
     * 客户保存
     */
    public static function customerSave($data) {
        $remain = [];
        if (isset($data['id']) && $data['id']) {
            $con[] = ['id', '=', $data['id']];
            $remain = self::find($con);
        }
        $mainId = '';
        if ($remain) {
            $mainId = $data['id'];
            //更新
            $res = self::getInstance($data['id'])->update($data);
        } else {
            //新增
            if (isset($data['id'])) {
                unset($data['id']);
            }

            $res = self::save($data);
            $mainId = $res['id'];
        }

        return $mainId;
    }

    /*     * ********** */

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
     * 内部编码
     */
    public function fCode() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 公司名称
     */
    public function fCustomerName() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 简称
     */
    public function fShortName() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 公司地址
     */
    public function fCustomerAddress() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 客户类型：有限公司；个体户
     */
    public function fCustomerType() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 收款方式(别人付给我)，1先付，2定金+尾款，3月结'
     */
    public function fIncomeType() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 付出方式(我付给别人)，1先付，2定金+尾款，3月结'
     */
    public function fOutcomeType() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 是否开票：0否，1是
     */
    public function fNeedInvoice() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 营业执照编号，统一社会信用代码
     */
    public function fLicence() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 营业执照图片
     */
    public function fLicencePic() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 联系人姓名
     */
    public function fFrName() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 联系人手机
     */
    public function fFrMobile() {
        return $this->getFFieldValue(__FUNCTION__);
    }

    /**
     * 关联tr_company表
     */
    public function fCustomerCompanyId() {
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

    
    /**
     * 20231208
     * 端口初始化时带公司初始化
     */
    public static function compCustomerInit($companyId){
        $compInfo   = SystemCompanyService::getInstance($companyId)->get();
        $cate       = Arrays::value($compInfo, 'cate');
        $level      = Arrays::value($compInfo, 'level');
        
        if($cate == 'bao'){
            // 初始添加个人包车
            $data['type_key']       = 'personal';
            $data['customer_name']  = '个人包车';
            $data['short_name']     = '个人包车';

            self::saveRam($data);
        }

        return $res;
    }
}
