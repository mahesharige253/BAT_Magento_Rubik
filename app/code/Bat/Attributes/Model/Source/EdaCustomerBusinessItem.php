<?php

namespace Bat\Attributes\Model\Source;

class EdaCustomerBusinessItem extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * @inheritdoc
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = [
                ['label' => __('Select Business Licence Item'), 'value' => ''],
                ['label' => __('담배'), 'value' => '0001'],
                ['label' => __('식품잡화'), 'value' => '0002'],
                ['label' => __('일용잡화'), 'value' => '0003'],
                ['label' => __('편의점'), 'value' => '0004'],
                ['label' => __('할인점'), 'value' => '0005'],
                ['label' => __('연쇄점'), 'value' => '0006'],
                ['label' => __('슈퍼'), 'value' => '0007'],
                ['label' => __('음식,숙박'), 'value' => '0008'],
                ['label' => __('한식'), 'value' => '0009'],
                ['label' => __('일식'), 'value' => '0010'],
                ['label' => __('중식'), 'value' => '0011'],
                ['label' => __('양식'), 'value' => '0012'],
                ['label' => __('인쇄,문구'), 'value' => '0013'],
                ['label' => __('팬시,문구'), 'value' => '0014'],
                ['label' => __('책대여'), 'value' => '0015'],
                ['label' => __('테이프대여'), 'value' => '0016'],
                ['label' => __('치킨'), 'value' => '0017'],
                ['label' => __('제과'), 'value' => '0018'],
                ['label' => __('의약품'), 'value' => '0019'],
                ['label' => __('악세사리'), 'value' => '0020'],
                ['label' => __('화장품'), 'value' => '0021'],
                ['label' => __('곡물류'), 'value' => '0022'],
                ['label' => __('청과'), 'value' => '0023'],
                ['label' => __('이동통신'), 'value' => '0024'],
                ['label' => __('철물,건재'), 'value' => '0025'],
                ['label' => __('경정비'), 'value' => '0026'],
                ['label' => __('건강식품'), 'value' => '0027'],
                ['label' => __('정육'), 'value' => '0028'],
                ['label' => __('유아용품'), 'value' => '0029'],
                ['label' => __('기계공구'), 'value' => '0030'],
                ['label' => __('현상인화'), 'value' => '0031'],
                ['label' => __('공인중개사'), 'value' => '0032'],
                ['label' => __('내의'), 'value' => '0033'],
                ['label' => __('낚시'), 'value' => '0034'],
                ['label' => __('관광호텔'), 'value' => '0035'],
                ['label' => __('관광기념품'), 'value' => '0036'],
                ['label' => __('귀금속'), 'value' => '0037'],
                ['label' => __('의류'), 'value' => '0038'],
                ['label' => __('구두'), 'value' => '0039'],
                ['label' => __('의류'), 'value' => '0040'],
                ['label' => __('다방'), 'value' => '0041'],
                ['label' => __('주점'), 'value' => '0042'],
                ['label' => __('전기'), 'value' => '0043'],
                ['label' => __('전자'), 'value' => '0044'],
                ['label' => __('가판'), 'value' => '0045'],
                ['label' => __('간판'), 'value' => '0046'],
                ['label' => __('수입담배'), 'value' => '0047'],
                ['label' => __('상품연쇄화 산업'), 'value' => '0048'],
                ['label' => __('위탁급식'), 'value' => '0049'],
                ['label' => __('컴퓨터및주변기기'), 'value' => '0050'],
                ['label' => __('통신판매업'), 'value' => '0051'],
                ['label' => __('기타'), 'value' => '9999'],
            ];
        }
        return $this->_options;
    }
}
