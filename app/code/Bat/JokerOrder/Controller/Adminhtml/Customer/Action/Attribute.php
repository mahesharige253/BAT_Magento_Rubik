<?php
namespace Bat\JokerOrder\Controller\Adminhtml\Customer\Action;

use Magento\Backend\App\Action;

/**
 * Adminhtml customer action attribute update controller
 */
abstract class Attribute extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Bat_JokerOrder::jokerorder';

    /**
     * @var \Bat\JokerOrder\Helper\Customer\Edit\Action\Attribute
     */
    protected $attributeHelper;

    /**
     * @param Action\Context $context
     * @param \Bat\JokerOrder\Helper\Customer\Edit\Action\Attribute $attributeHelper
     */
    public function __construct(
        Action\Context $context,
        \Bat\JokerOrder\Helper\Customer\Edit\Action\Attribute $attributeHelper
    ) {
        parent::__construct($context);
        $this->attributeHelper = $attributeHelper;
    }

    /**
     * Validate selection of customers for mass update
     *
     * @return boolean
     */
    protected function _validateCustomers()
    {
        $error = false;
        $customerIds = $this->attributeHelper->getCustomerIds();
        if (!is_array($customerIds)) {
            $error = __('Please select customer for attributes update.');
        }

        if ($error) {
            $this->messageManager->addErrorMessage($error);
        }

        return !$error;
    }

    /**
     * Check Permission.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Bat_JokerOrder::jokerorder');
    }
}
