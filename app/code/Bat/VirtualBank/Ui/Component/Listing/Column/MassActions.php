<?php
declare(strict_types=1);

namespace Bat\VirtualBank\Ui\Component\Listing\Column;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;

/**
 * @class MassActions
 * ACL Rule for mass actions
 */
class MassActions extends \Magento\Ui\Component\MassAction
{
    protected const ACL_RESOURCE = 'Bat_VirtualBank::delete';

    /**
     * @var AuthorizationInterface
     */
    protected AuthorizationInterface $authorization;

    /**
     * @param ContextInterface $context
     * @param AuthorizationInterface $authorization
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        AuthorizationInterface $authorization,
        array $components = [],
        array $data = []
    ) {
        $this->authorization = $authorization;
        parent::__construct($context, $components, $data);
    }

    /**
     * @inheritDoc
     */
    public function prepare(): void
    {
        if (!$this->authorization->isAllowed(self::ACL_RESOURCE)) {
            foreach ($this->getChildComponents() as $actionComponent) {
                $componentConfig = $actionComponent->getConfiguration();
                $componentConfig['actionDisable'] = true;
                $actionComponent->setData('config', $componentConfig);
            }

            $config = $this->getConfiguration();
            $config['componentDisabled'] = true;
            $this->setData('config', $config);
        }
        parent::prepare();
    }
}
