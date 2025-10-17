<?php
use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\Category\LeadCategory;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
if (!check_bitrix_sessid()) die();

Loader::includeModule('crm');

$request = Application::getInstance()->getContext()->getRequest();
list($type, $id) = explode('_', trim((string)$request->getPost('entity')));

$stages = [];

$factory = Container::getInstance()->getFactory($id);
if ($factory) {

    try {
        $categories = $factory->getCategories();
    } catch(\Throwable $e) {
        $categories = null;
    }

    if (!empty($categories)) {
        foreach ($categories as $category) {
            $categoryId = $category->getId();
            $categoryName = $category->getName() ?: ('Воронка #' . $categoryId);
            $categoryStages = [];

            $stagesList = $factory->getStages($categoryId);

            foreach ($stagesList as $stage) {
                $categoryStages[] = [
                    'ID' => $stage->getId(),
                    'CODE' => $stage->getStatusId(),
                    'NAME' => $stage->getName(),
                ];
            }

            $stages[] = [
                'ID' => $categoryId,
                'NAME' => $categoryName,
                'STAGES' => $categoryStages,
            ];
        }
    } else {
        $stagesList = $factory->getStages();
        $categoryStages = [];

        foreach ($stagesList as $stage) {
            $categoryStages[] = [
                'ID' => $stage->getId(),
                'CODE' => $stage->getStatusId(),
                'NAME' => $stage->getName(),
            ];
        }

        $stages[] = [
            'ID' => 0,
            'NAME' => $factory->getEntityDescription(),
            'STAGES' => $categoryStages,
        ];
    }
}


header('Content-Type: application/json; charset=utf-8');
echo json_encode($stages);

