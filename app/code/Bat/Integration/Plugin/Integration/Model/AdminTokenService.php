<?php

namespace Bat\Integration\Plugin\Integration\Model;

use Bat\Integration\Helper\Data;
use Magento\Framework\Webapi\Rest\Request;

/**
 * @class AdminTokenService
 * Plugin for decrypt admin credentials
 */
class AdminTokenService
{
    /**
     * @var Request
     */
    private Request $request;

    /**
     * @var Data
     */
    private Data $data;

    /**
     * @param Request $request
     * @param Data $data
     */
    public function __construct(
        Request $request,
        Data $data
    ) {
        $this->request = $request;
        $this->data = $data;
    }

    /**
     * Plugin for decrypting admin credentials
     *
     * @param \Magento\Integration\Model\AdminTokenService $subject
     * @param string $username
     * @param string $password
     * @return array
     */
    public function beforeCreateAdminAccessToken(
        \Magento\Integration\Model\AdminTokenService $subject,
        $username,
        $password
    ) {
        $channelId = $this->request->getHeader('x-channel-id');
        if ($channelId == 'decrypt') {
            $username = $this->data->decryptData($username);
            $password = $this->data->decryptData($password);
        }
        return [$username, $password];
    }
}
