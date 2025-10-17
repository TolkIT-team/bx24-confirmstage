<?php
namespace Tolkit\ConfirmStage;

use Bitrix\Main\Config\Option;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Application;

class EventHandlers
{
    public static function onProlog()
    {
        global $APPLICATION;

        $config = json_decode(Option::get('tolkit.confirmstage', 'CONFIG', '[]'), true);
        $curConf = [];
        foreach ($config as $index => $data) {
            if (strpos($APPLICATION->GetCurPage(), $data['PATH']) === false) continue;
            $curConf += $data['STAGES'];
        }

        if (empty($curConf)) {
            return;
        }

        $APPLICATION->AddHeadString(
            '<script>window.ConfirmStageConfig = ' . \CUtil::PhpToJSObject($curConf) . ';</script>',
            true
        );
        $APPLICATION->AddHeadScript('/local/modules/tolkit.confirmstage/js/confirm.js');
        Extension::load(['ui.dialogs.messagebox', 'ui.notification']);
    }
}

