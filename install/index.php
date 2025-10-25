<?php
use Bitrix\Main\ModuleManager;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Tolkit\ConfirmStage\EventHandlers;

class tolkit_confirmstage extends CModule
{
    public function __construct()
    {
        $this->MODULE_ID = 'tolkit.confirmstage';
        $this->MODULE_NAME = 'Подтверждение смены стадии в CRM';
        $this->MODULE_DESCRIPTION = 'Добавляет окно подтверждения при изменении стадии сделки, лида и смарт-процессов.';
        $this->PARTNER_NAME = 'tolkit';
        $this->PARTNER_URI = 'https://tolkit.top';

        $arModuleVersion = [];
        include __DIR__ . '/version.php';
        $this->MODULE_VERSION = $arModuleVersion['VERSION'] ?? '1.0.0';
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'] ?? date('Y-m-d');
    }

    public function DoInstall(): void
    {
        ModuleManager::registerModule($this->MODULE_ID);
        Loader::registerAutoLoadClasses('tolkit.confirmstage', [
            'Tolkit\\ConfirmStage\\EventHandlers' => 'lib/eventhandlers.php',
        ]);

        $eventManager = EventManager::getInstance();
        $eventManager->registerEventHandler(
            'main',
            'onProlog',
            $this->MODULE_ID,
            Tolkit\ConfirmStage\EventHandlers::class,
            'onProlog'
        );
        //EventManager::getInstance()->addEventHandler('main', 'OnProlog', [Tolkit\ConfirmStage\EventHandlers::class, 'onProlog']);
    }

    public function DoUninstall(): void
    {
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }
}

