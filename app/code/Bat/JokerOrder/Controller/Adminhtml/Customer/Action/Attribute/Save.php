<?php
namespace Bat\JokerOrder\Controller\Adminhtml\Customer\Action\Attribute;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Bat\JokerOrder\Helper\Customer\Edit\Action\Attribute;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Customer\Model\Customer;
use Magento\Eav\Api\AttributeRepositoryInterface;

class Save extends Action
{
    /**
     * Joker order customer name
     */
    public const TOPIC_NAME_NPI_JOKERORDER = "jokerorder.customer.npiupdate";

    /**
     * @var PageFactory
     */
    protected $resultPageFactory = false;

    /**
     * @var Attribute
     */
    protected $attributeHelper;

    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var PublisherInterface
     */
    protected $publisher;

    /**
     * @var JsonHelper
     */
    protected $jsonHelper;

    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * Save constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Attribute $attributeHelper
     * @param CustomerFactory $customerFactory
     * @param PublisherInterface $publisher
     * @param JsonHelper $jsonHelper
     * @param AttributeRepositoryInterface $attributeRepository
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Attribute $attributeHelper,
        CustomerFactory $customerFactory,
        PublisherInterface $publisher,
        JsonHelper $jsonHelper,
        AttributeRepositoryInterface $attributeRepository
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->attributeHelper = $attributeHelper;
        $this->customerFactory = $customerFactory;
        $this->publisher = $publisher;
        $this->jsonHelper = $jsonHelper;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * Joker Order data save
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|
     * \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $attributesData = $this->getRequest()->getPostValue();
        $customerIds = $this->attributeHelper->getCustomerIds();
        if ($attributesData['joker_order_type'] == 'ecall') {

            try {
                if ($attributesData['start_date'] > $attributesData['end_date']) {
                    $this->messageManager->addErrorMessage(__('Please select start date is less than end date.'));
                    return $this->resultRedirectFactory->create()
                            ->setPath('jokerorder/customer_action_attribute/ecall');
                }
                foreach ($customerIds as $customerId) {
                    $customer = $this->customerFactory->create()->load($customerId);
                    $customerDataModel = $customer->getDataModel();
                    $customerDataModel->setCustomAttribute(
                        'joker_order_ecall_start_date',
                        $attributesData['start_date']
                    );
                    $customerDataModel->setCustomAttribute(
                        'joker_order_ecall_end_date',
                        $attributesData['end_date']
                    );
                    $customer->updateData($customerDataModel);
                    $customer->save();
                }

                $this->messageManager->addSuccessMessage(__('Joker order(e-call) updated'));
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage(
                    $e,
                    __('Something went wrong while updating the customer(s) attributes.')
                );
            }
        } else {

            try {
                if ($attributesData['start_date'] > $attributesData['end_date']) {
                    $this->messageManager->addErrorMessage(
                        __('Please select start date is less than end date.')
                    );
                    return $this->resultRedirectFactory->create()->setPath('jokerorder/customer_action_attribute/npi');
                }
                $attributeData = [];
                $attributeStartDate = $this->attributeRepository->get(Customer::ENTITY, 'joker_order_npi_start_date');
                $attributeStartDateId = $attributeStartDate->getAttributeId();
                $attributeEndDate = $this->attributeRepository->get(Customer::ENTITY, 'joker_order_npi_end_date');
                $attributeEndDateId = $attributeEndDate->getAttributeId();
                foreach ($customerIds as $customerId) {
                    $attributeData = ['customer_id' => $customerId, 'attributes' =>
                                        [$attributeStartDateId => $attributesData['start_date'],
                                        $attributeEndDateId => $attributesData['end_date']]];
                    $this->publisher->publish(
                        self::TOPIC_NAME_NPI_JOKERORDER,
                        $this->jsonHelper->jsonEncode($attributeData)
                    );
                }

                $this->messageManager->addSuccessMessage(__('Joker order(NPI) is added to queue'));
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage(
                    $e,
                    __('Something went wrong while updating the customer(s) attributes.')
                );
            }
        }
        
        return $this->resultRedirectFactory->create()->setPath('customer');
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
