<?php
namespace Bat\AdminUserRestriction\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\LocalizedException;
use Bat\AdminUserRestriction\Model\AdminUserRestriction;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Message\ManagerInterface;

/**
 * @class CatalogProductSaveAfter
 * Check expiry date while product save/update
 */
class ModelSaveBefore implements ObserverInterface
{
    /**
     * @var Session
     */
    protected $authSession;

    /**
     * @var Http
     */
    protected $request;

    /**
     * @var AdminUserRestriction
     */
     protected $adminUserRestriction;

    /**
     * @var ResponseFactory
     */
    protected $response;

    /**
     * @var RedirectInterface
     */
    protected $redirect;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @param Session $session
     * @param Http $request
     * @param AdminUserRestriction $adminUserRestriction
     * @param ResponseFactory $response
     * @param RedirectInterface $redirect
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        Session $session,
        Http $request,
        AdminUserRestriction $adminUserRestriction,
        ResponseFactory $response,
        RedirectInterface $redirect,
        ManagerInterface $messageManager
    ) {
        $this->authSession = $session;
        $this->request = $request;
        $this->adminUserRestriction = $adminUserRestriction;
        $this->response = $response;
        $this->redirect = $redirect;
        $this->messageManager = $messageManager;
    }

    /**
     * Controller Action
     *
     * @param EventObserver $observer
     * @return boolean
     * @throws LocalizedException
     */
    public function execute(EventObserver $observer)
    {
        
        if ($this->adminUserRestriction->isEnabled()) {
            $user = $this->authSession->getUser();
            if (!$user) {
                return true;
            }
            $restrictedAdminUser = $this->adminUserRestriction->getRestrictedAdminUser();
            if (in_array($user->getId(), $restrictedAdminUser)) {
                $restrictedModules = $this->adminUserRestriction->getModuleRestrictions();
                $module = $this->request->getModuleName();
                $actionName = $this->request->getActionName();
                $moduleAction = $module.':'.$actionName;
                if (in_array($moduleAction, $restrictedModules)) {
                    $customRedirectionUrl = $this->redirect->getRefererUrl();
                    $this->messageManager->addError(__("You don't have permission."));
                    $this->response->create()
                        ->setRedirect($customRedirectionUrl)
                        ->sendResponse();
                    exit(0);
                    return $this;
                }
            }
        }
        return true;
    }
}
