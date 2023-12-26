<?php
namespace Bat\AccountClosure\Block\Adminhtml\Accountclosure\Edit;

use Magento\Backend\Block\Widget\Form\Generic;

use Magento\Framework\Exception\LocalizedException;

class Form extends Generic
{
    /**
     * Prepare form
     *
     * @return Form
     * @throws LocalizedException
     */
    public function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'edit_form',
                    'action' => $this->getData('action'),
                    'method' => 'post',
                    'enctype' => 'multipart/form-data'
                ]
            ]
        );
        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
