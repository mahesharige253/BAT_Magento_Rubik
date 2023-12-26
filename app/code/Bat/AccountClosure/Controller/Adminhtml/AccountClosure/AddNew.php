<?php
namespace Bat\AccountClosure\Controller\Adminhtml\AccountClosure;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Forward;
use Magento\Backend\Model\View\Result\ForwardFactory;

class AddNew extends Action
{
    /**
     * @var ForwardFactory
     */
    private $resultForwardFactory;

    /**
     * NewAction constructor.
     * @param Action\Context $context
     * @param ForwardFactory $resultForwardFactory
     */
    public function __construct(
        Action\Context $context,
        ForwardFactory $resultForwardFactory
    ) {
        $this->resultForwardFactory = $resultForwardFactory;
        parent::__construct($context);
    }

    /**
     * Is Allowed
     *
     * @inheritdoci
     */
    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('Bat_AccountClosure::accountclosure');
    }

    /**
     * Forward to edit
     *
     * @return void
     */
    public function execute()
    {
        /** @var Forward $resultForward */
        $resultForward = $this->resultForwardFactory->create();
        return $resultForward->forward('edit');
    }
}
