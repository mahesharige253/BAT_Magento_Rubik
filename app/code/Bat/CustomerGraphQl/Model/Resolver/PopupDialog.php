<?php
namespace Bat\CustomerGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\App\Config\ScopeConfigInterface;

class PopupDialog implements ResolverInterface
{
    /**
     * @var ScopeConfigInterface
     */
    private $_scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
    ) {
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (empty($args['input']) || !is_array($args['input'])) {
            throw new GraphQlInputException(__('"input" value should be specified'));
        }

        $popupScreens = ['newDeviceLogin','forgotPassword','checkStatus'];
        if (!isset($args['input']['popupName'])) {
            throw new GraphQlInputException(__('Popup name value should be specified'));
        } elseif (isset($args['input']['popupName']) && (!in_array($args['input']['popupName'], $popupScreens))) {
            throw new GraphQlInputException(__('Please enter correct Popup Name'));
        }

        if ($args['input']['popupName'] == 'newDeviceLogin') {
                $title = $this->_scopeConfig->getValue("bat_customer/popup/new_device_popup_title");
                $description = $this->_scopeConfig->getValue("bat_customer/popup/new_device_popup_description");
        } elseif ($args['input']['popupName'] == 'forgotPassword') {
                $title = $this->_scopeConfig->getValue("bat_customer/popup/forgot_password_popup_title");
                $description = $this->_scopeConfig->getValue("bat_customer/popup/forgot_password_popup_description");
        } elseif ($args['input']['popupName'] == 'checkStatus') {
                $title = $this->_scopeConfig->getValue("bat_customer/popup/check_status_popup_title");
                $description = $this->_scopeConfig->getValue("bat_customer/popup/check_status_popup_description");
        }
        $data = ['popupName' => $args['input']['popupName'], 'title' => $title, 'description' => $description];

        return $data;
    }
}
