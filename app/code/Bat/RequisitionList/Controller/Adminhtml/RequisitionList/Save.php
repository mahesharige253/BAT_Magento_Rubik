<?php
namespace Bat\RequisitionList\Controller\Adminhtml\RequisitionList;

use Bat\RequisitionList\Model\RequisitionListAdminFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory;
use Bat\RequisitionList\Model\RequisitionListItemAdminFactory;
use Bat\RequisitionList\Helper\Data;
use Bat\GetCartGraphQl\Helper\Data as QuantityHelper;
use Bat\RequisitionList\Model\ResourceModel\RequisitionListItemAdmin as RequisitionListItemResourceModel;
use Bat\RequisitionList\Ui\Component\Listing\Column\RlType;

/**
 * @class Save
 * Save RequisitionList Details
 */
class Save extends Action
{
    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @var RequisitionListAdminFactory
     */
    private $requisitionListAdminFactory;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var QuantityHelper
     */
     protected $quantityHelper;

     /**
      * @var RequisitionListItemAdminFactory
      */
      protected $requisitionListItemAdminFactory;

    /**
     * @var RequisitionListItemResourceModel
     */
    protected $requisitionListItemResourceModel;

    /**
     * @var RlType
     */
    protected $rlType;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param RequisitionListAdminFactory $requisitionListAdminFactory
     * @param RequisitionListItemAdminFactory $requisitionListItemAdminFactory
     * @param Data $helper
     * @param QuantityHelper $quantityHelper
     * @param RequisitionListItemResourceModel $requisitionListItemResourceModel
     * @param RlType $rlType
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        RequisitionListAdminFactory $requisitionListAdminFactory,
        RequisitionListItemAdminFactory $requisitionListItemAdminFactory,
        Data $helper,
        QuantityHelper $quantityHelper,
        RequisitionListItemResourceModel $requisitionListItemResourceModel,
        RlType $rlType
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->requisitionListAdminFactory = $requisitionListAdminFactory;
        $this->requisitionListItemAdminFactory = $requisitionListItemAdminFactory;
        $this->helper = $helper;
        $this->quantityHelper = $quantityHelper;
        $this->requisitionListItemResourceModel = $requisitionListItemResourceModel;
        $this->rlType = $rlType;
        parent::__construct($context);
    }

    /**
     * Create New RequisitionList Page
     *
     * @return Redirect
     */
    public function execute()
    {
        try {
            $resultRedirect = $this->resultRedirectFactory->create();
            $newRequisitionList = true;
            $requisitionListId = 0;
            $data = $this->getRequest()->getPostValue();

            if ($this->validateRlData($data) != '') {
                $this->messageManager->addErrorMessage(
                    __($this->validateRlData($data).' key is not valid.')
                );
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['entity_id' => $requisitionListId, '_current' => true]);
                }
                return $resultRedirect->setPath('*/*/index', ['_current' => true]);
            }
            $adminRequisitionListModel = $this->requisitionListAdminFactory->create();
            $allowRequisitionlistAdmin = $this->helper->getRequisitionlistAdmin();
            $allRlTypes = ["normal", "seasonal", "bestseller"];
            $rlType = [];
            $rlTypeData = '';
            if (isset($data['rl_type'])) {
                $rlType = $this->getRlType($data['rl_type']);
                $rlTypeData = $data['rl_type'];
            }
            $rlTypeStatus = false;
                       
            if ($rlTypeData == 'seasonal' && isset($data['seasonal_percentage']) && !$data['seasonal_percentage']) {
                $this->messageManager->addErrorMessage(
                    __('Seasonal Percentage field is required.')
                );
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['entity_id' => $requisitionListId, '_current' => true]);
                }
                return $resultRedirect->setPath('*/*/index', ['_current' => true]);
            }
            
            if (array_key_exists('entity_id', $data)) {
                $requisitionListId = $data['entity_id'];
                $adminRequisitionListModel = $adminRequisitionListModel->load($data['entity_id']);
                $newRequisitionList = false;
                if (in_array($rlTypeData, $allRlTypes) && empty($rlType)) {
                    $rlTypeStatus = false;
                } elseif (in_array($rlTypeData, $allRlTypes) && isset($rlType[0])
                    && $rlType[0]['entity_id'] != $data['entity_id']) {
                    $rlTypeStatus = true;
                } elseif (isset($rlType[0]) && $rlType[0]['entity_id'] == $data['entity_id']) {
                    $rlTypeStatus = false;
                } else {
                    $requisitionList = $this->requisitionListItemAdminFactory->create()->getCollection();
                    $requisitionItem = $requisitionList->addFieldToFilter('requisition_list_id', $data['entity_id']);
                    if (empty($data['products']) && $requisitionItem->getSize() == 0) {
                        $this->messageManager->addErrorMessage(
                            __('Please select product.')
                        );
                        if ($this->getRequest()->getParam('back')) {
                            return $resultRedirect->setPath('*/*/edit', ['entity_id' => $requisitionListId, '_current' => true]);
                        }
                        return $resultRedirect->setPath('*/*/index', ['_current' => true]);
                    }
                }
            } else {
                if ($allowRequisitionlistAdmin <= count($adminRequisitionListModel->getCollection())) {
                    $this->messageManager->addErrorMessage(
                        __('Requisition list admin are allowed:'.$allowRequisitionlistAdmin.' or less than.')
                    );
                    if ($this->getRequest()->getParam('back')) {
                        return $resultRedirect->setPath('*/*/edit', ['entity_id' => $requisitionListId, '_current' => true]);
                    }
                    return $resultRedirect->setPath('*/*/index', ['_current' => true]);
                }

                if (!empty($rlType) && !$rlTypeData) {
                    if ($rlType['0']['rl_type'] = $rlTypeData) {
                        $rlTypeStatus = true;
                    }
                }

                if (empty($data['products']) && !in_array($rlTypeData, $allRlTypes)) {
                    $this->messageManager->addErrorMessage(
                        __('Please select product.')
                    );
                    if ($this->getRequest()->getParam('back')) {
                        return $resultRedirect->setPath('*/*/edit', ['entity_id' => $requisitionListId, '_current' => true]);
                    }
                    return $resultRedirect->setPath('*/*/index', ['_current' => true]);
                }

            }

            if ($rlTypeStatus) {
                $rlTypeLabel = $this->getRlTypeLabel($rlTypeData);
                $this->messageManager->addErrorMessage(__($rlTypeLabel.' requisition list admin is alreday exist.'));
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['entity_id' => $requisitionListId, '_current' => true]);
                }
                return $resultRedirect->setPath('*/*/index', ['_current' => true]);
            }

            if (isset($data['products']) && $data['products'] !='') {
                $productIds = explode('&', $data['products']);
                $selectedQtys = $this->getSelectedItem($productIds, $data['qty']);
                $selectedSkus = $this->getSelectedItem($productIds, $data['sku']);
                $validateQtys = $this->validateQty($selectedQtys);
                if ($validateQtys) {
                    if ($validateQtys == 2) {
                        $this->messageManager->addErrorMessage(
                            __('Selected product with quantity is not allowed empty or 0.')
                            );
                    } elseif ($validateQtys == 3) {
                        $this->messageManager->addErrorMessage(
                            __('Selected product with allowed quantities multiple of 10.')
                            );
                    }
                    if ($this->getRequest()->getParam('back')) {
                        return $resultRedirect->setPath('*/*/edit', ['entity_id' => $requisitionListId, '_current' => true]);
                    }
                    return $resultRedirect->setPath('*/*/index', ['_current' => true]);
                }
                if ($this->allowQty($selectedQtys)) {
                    $this->messageManager->addErrorMessage(
                        __('Allow product quantity minimum: '
                            .$this->quantityHelper->getMinimumCartQty().' and maximum: '
                            .$this->quantityHelper->getMaximumCartQty().'.')
                    );
                    if ($this->getRequest()->getParam('back')) {
                        return $resultRedirect->setPath('*/*/edit', ['entity_id' => $requisitionListId, '_current' => true]);
                    }
                    return $resultRedirect->setPath('*/*/index', ['_current' => true]);
                }
            }
            
            if (isset($data['name'])) {
                $adminRequisitionListModel->setData('name', $data['name']);
            } else {
                $this->messageManager->addErrorMessage(
                    __('Name field is required.')
                );
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['entity_id' => $requisitionListId, '_current' => true]);
                }
                return $resultRedirect->setPath('*/*/index', ['_current' => true]);
            }
            if ($this->validateData($data['name']) == 1 && strlen($data['name']) < 40) {
                $adminRequisitionListModel->setData('name', $data['name']);
            } else {
                $this->messageManager->addErrorMessage(
                    __('Please enter a valid Rl name.')
                );
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['entity_id' => $requisitionListId, '_current' => true]);
                }
                return $resultRedirect->setPath('*/*/index', ['_current' => true]);
            }
            if (isset($data['status'])) {
                $adminRequisitionListModel->setData('status', $data['status']);
            }
            if (isset($data['description'])) {
                $adminRequisitionListModel->setData('description', $data['description']);
            }
            if (!empty($rlTypeData)) {
                $adminRequisitionListModel->setData('rl_type', $rlTypeData);
            }
            
            if (isset($data['seasonal_percentage'])) {
                $adminRequisitionListModel->setData('seasonal_percentage', $data['seasonal_percentage']);
            }
            $adminRequisitionListModel->save();
            $requisitionListId = $adminRequisitionListModel->getEntityId();

            if (isset($data['products']) && $data['products'] !='') {
                if (!empty($requisitionListId) && !in_array($rlTypeData, $allRlTypes)) {
                    $ids = [];
                    $uncheck = false;
                    if (!empty($selectedQtys)) {
                        foreach ($selectedQtys as $key => $qty) {
                            $ids[] = $key;
                            $requisitionListItemModel = $this->requisitionListItemAdminFactory->create();
                            if ($itemId = $this->getItemId($requisitionListId, $key)) {
                                $requisitionListItemModel->load($itemId);
                                $uncheck = true;
                            }
                            $requisitionListItemModel->setData('requisition_list_id', $requisitionListId);
                            $requisitionListItemModel->setData('sku', $selectedSkus[$key]);
                            $requisitionListItemModel->setData('product_id', $key);
                            $requisitionListItemModel->setData('qty', $qty);
                            $requisitionListItemModel->save();
                        }
                    }
                    $this->unassignItem($requisitionListId, $ids);
                }
            }

            if ($newRequisitionList) {
                $this->messageManager->addSuccessMessage(__('The RequisitionList has been successfully created'));
            } else {
                $this->messageManager->addSuccessMessage(__('The RequisitionList details has been updated.'));
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Error while trying to save data'.$e->getMessage()));
        }
        if ($this->getRequest()->getParam('back')) {
            return $resultRedirect->setPath('*/*/edit', ['entity_id' => $requisitionListId, '_current' => true]);
        }
        return $resultRedirect->setPath('*/*/index', ['_current' => true]);
    }

     /**
      * Get Item Id
      *
      * @param int $requisitionListId
      * @param int $productId
      * @return int|null
      */
    protected function getItemId($requisitionListId, $productId)
    {
        $itemModel = $this->requisitionListItemAdminFactory->create();
        $itemId = $itemModel->getRequisitionListItemId($requisitionListId, $productId);
        if (!empty($itemId)) {
            return $itemId[0];
        }
    }

    /**
     * Unassigned Item
     *
     * @param int $requisitionListId
     * @param int $ids
     * @return
     */
    protected function unassignItem($requisitionListId, $ids)
    {
        $itemModel = $this->requisitionListItemAdminFactory->create();
        $unassignItem = $itemModel->getProductsByEntityId($requisitionListId, $ids);
        if (!empty($unassignItem) && !empty($ids)) {
            foreach ($unassignItem as $item) {
                $model = $this->requisitionListItemAdminFactory->create();
                $this->requisitionListItemResourceModel->load($model, $item);
                $this->requisitionListItemResourceModel->delete($model);
            }
        }
    }

    /**
     * Get Selected Item
     *
     * @param array $selectedItem
     * @param array $data
     * @return array
     */
    protected function getSelectedItem($selectedItem, $data)
    {
        if (!empty($selectedItem) && !empty($data)) {
            $filteredArray = array_intersect_key($data, array_flip($selectedItem));
            return $filteredArray;
        }
    }

    /**
     * Get RL type item
     *
     * @param int $rlTypeData
     * @return int
     */
    public function getRlType($rlTypeData)
    {
        $adminRequisitionListModel = $this->requisitionListAdminFactory->create();
        $adminRequisitionListModel = $adminRequisitionListModel->getRlTypeData($rlTypeData);
        return $adminRequisitionListModel;
    }

    /**
     * Validate Qty
     *
     * @param array $selectedQtys
     * @return int
     */
    public function validateQty($selectedQtys)
    {
        if (!empty($selectedQtys)) {
            foreach ($selectedQtys as $key => $qty) {
                if (empty($qty) || $qty == 0) {
                    return 2;
                } elseif ($qty % 10 != 0){
                    return 3;
                }
            }
        }
        return 0;
    }

    /**
     * Allow Qty
     *
     * @param array $selectedQtys
     * @return boolean
     */
    public function allowQty($selectedQtys)
    {
        $quantity = 0;
        if (!empty($selectedQtys)) {
            foreach ($selectedQtys as $key => $qty) {
                $quantity = $quantity + $qty;
            }
            $quantityHelper = $this->quantityHelper;
            if ($quantity < $quantityHelper->getMinimumCartQty()) {
                return true;
            }
            if ($quantity > $quantityHelper->getMaximumCartQty()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get RL type label
     *
     * @param int $rlValue
     * @return string
     */
    public function getRlTypeLabel($rlValue)
    {
        $rlTypes = $this->rlType->getOptionArray();
        foreach ($rlTypes as $key => $value) {
            if ($key == $rlValue) {
                return $value;
            }
        }
    }

    /**
     * Validation RL
     *
     * @param array $data
     * @return string
     */
    public function validateRlData($data)
    {
        $invalidData = [];
        foreach ($data as $key => $value) {
            $invalidData[] = $key;
        }
        $error = '';
        if (!in_array('status', $invalidData)) {
            $error .= 'status,';
        }
        if (!in_array('name', $invalidData)) {
            $error .= 'name,';
        }
        if (!in_array('rl_type', $invalidData)) {
            $error .= 'rl_type,';
        }
        return substr($error, 0, -1);
    }

      /**
       * Server side validation

       * @param string $rlName
       * @return boolean
       */
    private function validateData($rlName)
    {
        if (!preg_match('/^[A-Za-z0-9가-힣\s]*$/', $rlName)) {
            return false; // Validation failed
        }
        return true;
    }
}
