<?php

namespace Bat\Kakao\Model;

use Magento\Framework\Model\AbstractModel;
use Bat\Kakao\Helper\Api;
use Bat\Kakao\Model\KakaoTemplateFactory;
use Magento\Setup\Exception;

class GetTemplate extends AbstractModel
{
    private const GET_TEMPLATE_API = 'biz/template/v1/search';

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var KakaoTemplateFactory
     */
    protected $kakaoTemplateFactory;

    /**
     * @param Api $api
     * @param KakaoTemplateFactory $kakaoTemplate
     */
    public function __construct(
        Api $api,
        KakaoTemplateFactory $kakaoTemplate
    ) {
        $this->api = $api;
        $this->kakaoTemplateFactory = $kakaoTemplate;
    }

    /**
     * Get Template by TemplateCode
     *
     * @param string $templateCode
     * @return array
     */
    public function getTemplate($templateCode)
    {
        $payload = $this->getSmsPayload($templateCode);
        return $this->api->getTemplate(self::GET_TEMPLATE_API, $payload);
    }

    /**
     * Prepare Get Template Payload
     *
     * @param string $templateCode
     * @return string
     */
    public function getSmsPayload($templateCode)
    {
        $config = $this->api->getConfig();
        $payload = [
            "usercode" => $config->getUsercode(),
            "deptcode" => $config->getDeptcode(),
            "senderKey" => $config->getYellowId(),
            "grouptype" => "S",
            "templateCode" => $templateCode
        ];

        return json_encode($payload);
    }

    /**
     * @param $templateCode
     * @return boolean
     * @throws \Exception
     */
    public function createUpdateTemplateInDb($templateCode)
    {
        try {
            $this->api->addLog('Template code: ' . $templateCode);
            $templateResponse = $this->getTemplate($templateCode);
            if ($templateResponse && isset($templateResponse['template']) && $templateResponse['template'] != '') {
                $templateJson = json_encode($templateResponse['template'], true);
                $template = $this->kakaoTemplateFactory->create();
                $dbTemplate = $this->getTemplateFromDb($templateCode);
                if ($dbTemplate) {
                    $template = $template->load($dbTemplate->getId());
                    $this->api->addLog('Template available in db');
                } else {
                    $template->setTemplateCode($templateResponse['template']['templateCode']);
                    $this->api->addLog('New template');
                }
                $template->setTemplateTitle($templateResponse['template']['templateName']);
                $template->setTemplateName($templateResponse['template']['templateName']);
                $template->setTemplateContent($templateResponse['template']['templateContent']);
                $template->setStatus($templateResponse['template']['status']);
                $template->setJsonTemplateContent($templateJson);
                $template->save();
            } else {
                $this->api->addLog('Template not found on server');
            }
        } catch (Exception $e) {
            $this->api->addLog('some error while sync');
        }
        return true;
    }

    /**
     * @param $templateCode
     * @return object|false
     */
    public function getTemplateFromDb($templateCode)
    {
        $template = $this->kakaoTemplateFactory->create()
            ->getCollection()
            ->addFieldToFilter("template_code", $templateCode);
        if ($template->getSize() > 0) {
            return $template->getFirstItem();
        }
        return false;
    }
}
