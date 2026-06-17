<?php

namespace TidioLiveChat\Admin;

if (!defined('WPINC')) {
    die('File loaded directly. Exiting.');
}

use TidioLiveChat\Admin\Notice\DismissibleNoticeService;
use TidioLiveChat\Admin\Notice\Exception\NoticeNameIsNotAllowedException;
use TidioLiveChat\IntegrationState;
use TidioLiveChat\Translation\ErrorTranslator;
use TidioLiveChat\Utils\QueryParameters;

class AdminNotice
{
    /** @var ErrorTranslator */
    private $errorTranslator;
    /** @var DismissibleNoticeService */
    private $dismissibleNoticeService;
    /** @var IntegrationState */
    private $integrationState;

    /**
     * @param ErrorTranslator $errorTranslator
     * @param DismissibleNoticeService $dismissibleNoticeService
     * @param IntegrationState $integrationState
     */
    public function __construct($errorTranslator, $dismissibleNoticeService, $integrationState)
    {
        $this->errorTranslator = $errorTranslator;
        $this->dismissibleNoticeService = $dismissibleNoticeService;
        $this->integrationState = $integrationState;
    }

    public function load()
    {
        add_action('admin_notices', [$this, 'addAdminErrorNotice']);
        add_action('admin_notices', [$this, 'addLyroAIChatbotNotice']);
    }

    public function addAdminErrorNotice()
    {
        if (!QueryParameters::has('error')) {
            return;
        }

        $errorCode = QueryParameters::get('error');
        $errorMessage = $this->errorTranslator->translate($errorCode);
        echo sprintf('<div class="notice notice-error is-dismissible"><p>%s</p></div>', $errorMessage);
    }

    public function addLyroAIChatbotNotice()
    {
        if (!$this->integrationState->isPluginIntegrated()) {
            return;
        }

        $this->displayDismissibleNotice(
            __DIR__ . '/Notice/Views/LyroAIChatbotNotice.php',
            DismissibleNoticeService::LYRO_AI_CHATBOT_NOTICE
        );
    }

    /**
     * @param string $templatePath
     * @param string $noticeName
     * @return void
     */
    private function displayDismissibleNotice($templatePath, $noticeName)
    {
        try {
            $this->dismissibleNoticeService->displayNotice($templatePath, $noticeName);
        } catch (NoticeNameIsNotAllowedException $exception) {
            // do not display notice if notice name is invalid
        }
    }
}
