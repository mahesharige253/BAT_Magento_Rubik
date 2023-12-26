<?php

namespace Bat\AdminUserRestriction\Model;

use Bat\RequisitionList\Helper\Data;

class AdminUserRestriction
{
    /**
     * Base path
     */
    public const BASE_PATH = 'bat_adminuserrestriction/adminuserrestriction/';

    /**
     * Admin user restriction enabled path
     */
    public const ADMINUSERRESTRICTION_ENABLED = 'enabled';

    /**
     * Module Restrictions path
     */
    public const MODULE_RESTRICTIONS = 'module_restrictions';

    /**
     * Restricted admin user
     */
    public const RESTRICTED_ADMIN_USER = 'Restricted_admin_user';

    /**
     * @var Data
     */
    protected Data $helper;

    /**
     * @param Data $helper
     */
    public function __construct(Data $helper)
    {
        $this->helper = $helper;
    }

    /**
     * Is Enabled
     *
     * @return boolean
     */
    public function isEnabled()
    {
        $isenabled = $this->helper->getConfig(self::BASE_PATH.self::ADMINUSERRESTRICTION_ENABLED);
        return $isenabled;
    }

    /**
     * Get Module Restrictions
     *
     * @return array
     */
    public function getModuleRestrictions()
    {
        $moduleRestrictions = $this->helper->getConfig(self::BASE_PATH.self::MODULE_RESTRICTIONS);
        if ($moduleRestrictions !='') {
            $moduleRestrictions = explode('|', $moduleRestrictions);
            return $moduleRestrictions;
        } else {
            return [];
        }
    }

    /**
     * Get Restricted Admin User
     *
     * @return array
     */
    public function getRestrictedAdminUser()
    {
        $restrictedAdminUser = $this->helper->getConfig(self::BASE_PATH.self::RESTRICTED_ADMIN_USER);
        if ($restrictedAdminUser !='') {
            $restrictedAdminUser = explode(',', $restrictedAdminUser);
            return $restrictedAdminUser;
        } else {
            return [];
        }
    }
}
