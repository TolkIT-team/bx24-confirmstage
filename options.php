<?php
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\UI\Extension;
use Bitrix\Crm\Model\Dynamic\TypeTable;

$src = $_SERVER['DOCUMENT_ROOT'] . '/local/modules/tolkit.confirmstage/install/tolkit_confirmstage_ajax.php';
$dstDir = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin/';
$dst = $dstDir . 'tolkit_confirmstage_ajax.php';

if (!file_exists($dst)) {

    if (!file_exists($src)) {
        echo "Ошибка: исходный файл не найден: {$src}";
        return;
    }

    if (!is_writable($dstDir)) {
        echo "Ошибка: нет прав на запись в {$dstDir}";
        return;
    }

    if (!copy($src, $dst)) {
        echo "Ошибка: не удалось скопировать файл tolkit_confirmstage_ajax.php.";
        return;
    }
}

$module_id = 'tolkit.confirmstage';
Loc::loadMessages(__FILE__);
Loader::includeModule('crm');
Extension::load('ui.buttons');
Extension::load('ui.forms');

if ($REQUEST_METHOD === 'POST' && check_bitrix_sessid()) {
    if (isset($_POST['CONFIG']) && is_array($_POST['CONFIG']) && count($_POST['CONFIG']) > 0) {
        Option::set($module_id, 'CONFIG', json_encode($_POST['CONFIG']));
    } else {
        \Bitrix\Main\Config\Option::delete($module_id, ['name' => 'CONFIG']);
    }
}

$config = json_decode(Option::get($module_id, 'CONFIG', '[]'), true);

$entities = [];
$standardEntities = [
    \CCrmOwnerType::Lead => 'Лид',
    \CCrmOwnerType::Deal => 'Сделка',
    \CCrmOwnerType::Contact => 'Контакт',
    \CCrmOwnerType::Company => 'Компания',
    \CCrmOwnerType::Quote => 'Предложение',
    \CCrmOwnerType::Invoice => 'Счёт',
];
foreach ($standardEntities as $typeId => $name) {
    $entities[] = [
        'ID' => $typeId,
        'NAME' => $name,
        'TYPE' => 'standard',
    ];
}
if (class_exists(TypeTable::class)) {
    $res = TypeTable::getList([
        'select' => ['ENTITY_TYPE_ID', 'TITLE'],
        'order' => ['TITLE' => 'ASC'],
    ]);
    while ($row = $res->fetch()) {
        $entities[] = [
            'ID' => $row['ENTITY_TYPE_ID'],
            'NAME' => $row['TITLE'],
            'TYPE' => 'smart',
        ];
    }
}
?>

<form method="post" id="configForm">
    <?= bitrix_sessid_post() ?>
    <div id="configContainer">
        <?php foreach ($config as $index => $group): ?>
            <div class="adm-detail-block" data-index="<?= $index ?>">
                <div class="adm-detail-content-item-block">
                    <table class="adm-detail-content-table edit-table">
                        <tr class="heading">
                            <td colspan="2">Группа #<?= $index + 1 ?></td>
                        </tr>
                        <tr>
                            <td width="40%" class="adm-detail-content-cell-l">
                                <b>Путь до раздела 	&nbsp;</b>
                                <span style="color:#888;"><?= $_SERVER['SERVER_NAME']; ?>/</span>
                            </td>
                            <td width="60%" class="adm-detail-content-cell-r">
                                <input type="text" name="CONFIG[<?= $index ?>][PATH]" size="60" class="adm-input" placeholder="example_page/example_section" value="<?= $group['PATH']?>">
                            </td>
                        </tr>
                        <tr>
                        <tr>
                            <td width="40%" class="adm-detail-content-cell-l"><b>CRM сущность:</b></td>
                            <td width="60%" class="adm-detail-content-cell-r">
                                <select name="CONFIG[<?= $index ?>][ENTITY]" class="entity-select">
                                    <option value="">-- выберите сущность --</option>
                                    <?php foreach ($entities as $entity):
                                        $uniqueId = $entity['TYPE'].'_'.$entity['ID'];
                                        $selected = ($group['ENTITY'] ?? '') === $uniqueId ? 'selected' : '';
                                    ?>
                                        <option value="<?= $uniqueId ?>" <?= $selected ?>>
                                            [<?= $entity['ID'] ?>] <?= htmlspecialcharsbx($entity['NAME']) ?><?= $entity['TYPE'] === 'smart' ? ' (смарт-процесс)' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td width="40%" class="adm-detail-content-cell-l"><b>Воронка:</b></td>
                            <td width="60%" class="adm-detail-content-cell-r">
                            <select disabled name="CONFIG[<?= $index ?>][PIPELINE_ID]" class="pipeline-select" data-entity="<?= $group['PIPELINE_ID'] ?? '' ?>">
                                    <option value="">-- выберите воронку --</option>
                                </select>
                            </td>
                        </tr>
                    </table>

                    <table class="adm-detail-content-table edit-table stages-table" style="margin-top:10px;">
                        <tr class="heading">
                            <td colspan="2">Настройки стадий</td>
                        </tr>
                        <?php if (!empty($group['STAGES'])): ?>
                            <?php foreach ($group['STAGES'] as $stageCode => $data): ?>
                                <tr>
                                    <td width="40%" class="adm-detail-content-cell-l">
                                        <b><?= htmlspecialcharsbx($data['NAME']) ?></b>
                                        <span style="color:#888;">(<?= htmlspecialcharsbx($stageCode) ?>)</span>
                                    </td>
                                    <td width="60%" class="adm-detail-content-cell-r">
                                        <input type="text" name="CONFIG[<?= $index ?>][STAGES][<?= htmlspecialcharsbx($stageCode) ?>][MESSAGE]" value="<?= htmlspecialcharsbx($data['MESSAGE'] ?? '') ?>" size="60" class="adm-input" placeholder="Текст подтверждения">
                                        <input type="hidden" name="CONFIG[<?= $index ?>][STAGES][<?= htmlspecialcharsbx($stageCode) ?>][ID]" value="<?= $data['ID'] ?>">
                                        <input type="hidden" name="CONFIG[<?= $index ?>][STAGES][<?= htmlspecialcharsbx($stageCode) ?>][CODE]" value="<?= htmlspecialcharsbx($stageCode) ?>">
                                        <input type="hidden" name="CONFIG[<?= $index ?>][STAGES][<?= htmlspecialcharsbx($stageCode) ?>][NAME]" value="<?= htmlspecialcharsbx($data['NAME']) ?>">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </table>

                    <div style="margin-top:10px;">
                        <button type="button" class="ui-btn ui-btn-danger-light remove-group">Удалить группу</button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div style="margin-top:15px;">
        <button type="button" class="ui-btn ui-btn-success" id="addGroup">+ Добавить группу</button>
    </div>
    <div style="margin-top:20px;">
        <input type="submit" value="Сохранить" class="adm-btn-save">
    </div>
</form>

<script>
BX.ready(function() {
    const entities = <?= \Bitrix\Main\Web\Json::encode($entities) ?>;

    document.getElementById('addGroup').addEventListener('click', function() {
        const container = document.getElementById('configContainer');
        const index = container.children.length;

        let options = '<option value="">-- выберите сущность --</option>';
        entities.forEach(e => {
            const type = e.TYPE === 'smart' ? ' (смарт-процесс)' : '';
            options += `<option value="${e.TYPE}_${e.ID}">[${e.ID}] ${BX.util.htmlspecialchars(e.NAME)}${type}</option>`;
        });

        const div = document.createElement('div');
        div.className = 'adm-detail-block';
        div.dataset.index = index;
        div.innerHTML = `
            <div class="adm-detail-content-item-block">
                <table class="adm-detail-content-table edit-table">
                    <tr class="heading"><td colspan="2">Группа #${index+1}</td></tr>
                    <tr>
                        <td width="40%" class="adm-detail-content-cell-l"><b>Путь до раздела</b></td>
                        <td width="60%" class="adm-detail-content-cell-r">
                            <input type="text" name="CONFIG[${index}][PATH]" size="60" class="adm-input" placeholder="<?= $_SERVER['SERVER_NAME']; ?>">
                        </td>
                    </tr>
                    <tr>
                        <td width="40%" class="adm-detail-content-cell-l"><b>CRM сущность:</b></td>
                        <td width="60%" class="adm-detail-content-cell-r">
                            <select name="CONFIG[${index}][ENTITY]" class="entity-select">
                                ${options}
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td width="40%" class="adm-detail-content-cell-l"><b>Воронка:</b></td>
                        <td width="60%" class="adm-detail-content-cell-r">
                            <select name="CONFIG[${index}][PIPELINE_ID]" class="pipeline-select">
                                <option value="">-- выберите воронку --</option>
                            </select>
                        </td>
                    </tr>
                </table>

                <table class="adm-detail-content-table edit-table stages-table" style="margin-top:10px;">
                    <tr class="heading"><td colspan="2">Настройки стадий</td></tr>
                </table>

                <div style="margin-top:10px;">
                    <button type="button" class="ui-btn ui-btn-danger-light remove-group">Удалить группу</button>
                </div>
            </div>
        `;
        container.appendChild(div);
    });

    document.body.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-group')) {
            const group = e.target.closest('.adm-detail-block');
            if (group) group.remove();
        }
    });

    function loadPipelines(group, isFirstLoad = false) {
        const entity = group.querySelector('.entity-select').value;
        if (!entity) return;

        const index = group.dataset.index;
        const pipelineSelect = group.querySelector('.pipeline-select');
        const stagesTable = group.querySelector('.stages-table');
        if (!isFirstLoad) {
            stagesTable.querySelectorAll('tr:not(.heading)').forEach(tr => tr.remove());
            pipelineSelect.innerHTML = `<option value="">Загрузка...</option>`;
        }

        BX.ajax.post('/bitrix/admin/tolkit_confirmstage_ajax.php', { sessid: BX.bitrix_sessid(), entity: entity }, function(result) {
            try {
                const pipelines = JSON.parse(result);
                pipelineSelect.innerHTML = '<option value="">-- выберите воронку --</option>';
                pipelines.forEach(p => {
                    const selected = pipelineSelect.dataset.entity == p.ID ? 'selected' : '';
                    pipelineSelect.innerHTML += `<option value="${p.ID}" ${selected}>${BX.util.htmlspecialchars(p.NAME)}</option>`;
                });

                if (!isFirstLoad) {
                    const currentPipeline = pipelineSelect.value || null;
                    if (currentPipeline) renderStages(pipelines.find(p => p.ID == currentPipeline).STAGES, index, stagesTable);
                } else {
                    pipelineSelect.removeAttribute('disabled');
                }

                pipelineSelect.addEventListener('change', function() {
                    const sel = pipelines.find(p => p.ID == this.value);
                    renderStages(sel?.STAGES || [], index, stagesTable);
                });
            } catch(e) {
                stagesTable.innerHTML = '<tr><td colspan="2" style="color:red;">Ошибка загрузки стадий</td></tr>';
            }
        });
    }

    function renderStages(stages, index, table) {
        table.querySelectorAll('tr:not(.heading)').forEach(tr => tr.remove());
        stages.forEach(stage => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td width="40%" class="adm-detail-content-cell-l">
                    <b>${BX.util.htmlspecialchars(stage.NAME)}</b>
                    <span style="color:#888;">(${BX.util.htmlspecialchars(stage.CODE)})</span>
                </td>
                <td width="60%" class="adm-detail-content-cell-r">
                    <input type="text" name="CONFIG[${index}][STAGES][${BX.util.htmlspecialchars(stage.CODE)}][MESSAGE]" size="60" class="adm-input" placeholder="Текст подтверждения">
                    <input type="hidden" name="CONFIG[${index}][STAGES][${BX.util.htmlspecialchars(stage.CODE)}][ID]" value="${stage.ID}">
                    <input type="hidden" name="CONFIG[${index}][STAGES][${BX.util.htmlspecialchars(stage.CODE)}][CODE]" value="${BX.util.htmlspecialchars(stage.CODE)}">
                    <input type="hidden" name="CONFIG[${index}][STAGES][${BX.util.htmlspecialchars(stage.CODE)}][NAME]" value="${BX.util.htmlspecialchars(stage.NAME)}">
                </td>
            `;
            table.appendChild(tr);
        });
    }

    document.querySelectorAll('.adm-detail-block').forEach(group => {
        const pipelineSelect = group.querySelector('.pipeline-select');
        pipelineSelect.dataset.selected = group.querySelector('select[name*="[PIPELINE_ID]"]')?.value || '';
        loadPipelines(group, true);
    });

    document.body.addEventListener('change', function(e) {
        if (e.target.classList.contains('entity-select')) {
            const group = e.target.closest('.adm-detail-block');
            group.querySelector('.pipeline-select').dataset.selected = '';
            loadPipelines(group);
        }
    });
});
</script>

