<?php

namespace xjryanse\customer\service;

/**
 * 客户表
 */
class CustomerService {

    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;

    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\customer\\model\\Customer';

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

}
