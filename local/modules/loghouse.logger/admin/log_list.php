<?php

use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime as BxDateTime;
use Bitrix\Main\UI\Filter\Options as FilterOptions;
use Loghouse\Logger\Orm\LogTable;

/** @global CMain $APPLICATION */
/** @global CUser $USER */

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

Loc::loadMessages(__FILE__);

if (!$USER->IsAdmin()) {
    $APPLICATION->AuthForm(Loc::getMessage('LOGHOUSE_LOGGER_LOG_ACCESS'));
}

if (!Loader::includeModule('loghouse.logger')) {
    ShowError('Module loghouse.logger is not installed.');
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';

    return;
}

$APPLICATION->SetTitle(Loc::getMessage('LOGHOUSE_LOGGER_LOG_TITLE'));

$request  = Context::getCurrent()->getRequest();
$tableId  = 'loghouse_logger_log_list';

if ($request->isPost() && $request->get('action_button_' . $tableId) === 'delete' && check_bitrix_sessid()) {
    $ids = array_map('intval', (array)$request->get('ID'));
    foreach ($ids as $id) {
        if ($id > 0) {
            LogTable::delete($id);
        }
    }
    LocalRedirect($APPLICATION->GetCurPage() . '?lang=' . LANGUAGE_ID);
}

if ($request->isPost() && $request->getPost('action') === 'clear_all' && check_bitrix_sessid() && $USER->IsAdmin()) {
    Application::getConnection()->queryExecute('TRUNCATE TABLE loghouse_logger_log');
    LocalRedirect($APPLICATION->GetCurPage() . '?lang=' . LANGUAGE_ID);
}

$likeEscape = static fn(string $value): string => addcslashes($value, '\\%_');

$sort = new CAdminUiSorting($tableId, 'ID', 'desc');
$list = new CAdminUiList($tableId, $sort);

$levelItems = [
    'DEBUG'     => 'DEBUG',
    'INFO'      => 'INFO',
    'NOTICE'    => 'NOTICE',
    'WARNING'   => 'WARNING',
    'ERROR'     => 'ERROR',
    'CRITICAL'  => 'CRITICAL',
    'ALERT'     => 'ALERT',
    'EMERGENCY' => 'EMERGENCY',
];

$filterFields = [
    [
        'id'      => 'ID',
        'name'    => Loc::getMessage('LOGHOUSE_LOGGER_LOG_FILTER_ID'),
        'type'    => 'number',
        'default' => false,
    ],
    [
        'id'      => 'CHANNEL',
        'name'    => Loc::getMessage('LOGHOUSE_LOGGER_LOG_FILTER_CH'),
        'default' => true,
    ],
    [
        'id'      => 'LEVEL_NAME',
        'name'    => Loc::getMessage('LOGHOUSE_LOGGER_LOG_FILTER_LVL'),
        'type'    => 'list',
        'items'   => $levelItems,
        'params'  => ['multiple' => 'Y'],
        'default' => true,
    ],
    [
        'id'      => 'MESSAGE',
        'name'    => Loc::getMessage('LOGHOUSE_LOGGER_LOG_FILTER_MSG'),
        'default' => true,
    ],
    [
        'id'      => 'CREATED_AT',
        'name'    => Loc::getMessage('LOGHOUSE_LOGGER_LOG_FILTER_DATE'),
        'type'    => 'date',
        'default' => true,
    ],
];

$list->AddHeaders([
    ['id' => 'ID', 'content' => Loc::getMessage('LOGHOUSE_LOGGER_LOG_COL_ID'), 'sort' => 'ID', 'default' => true],
    ['id' => 'CREATED_AT', 'content' => Loc::getMessage('LOGHOUSE_LOGGER_LOG_COL_DATE'), 'sort' => 'CREATED_AT', 'default' => true],
    ['id' => 'CHANNEL', 'content' => Loc::getMessage('LOGHOUSE_LOGGER_LOG_COL_CHANNEL'), 'sort' => 'CHANNEL', 'default' => true],
    ['id' => 'LEVEL_NAME', 'content' => Loc::getMessage('LOGHOUSE_LOGGER_LOG_COL_LEVEL'), 'sort' => 'LEVEL', 'default' => true],
    ['id' => 'MESSAGE', 'content' => Loc::getMessage('LOGHOUSE_LOGGER_LOG_COL_MESSAGE'), 'default' => true],
    ['id' => 'CONTEXT', 'content' => Loc::getMessage('LOGHOUSE_LOGGER_LOG_COL_CONTEXT'), 'default' => false],
    ['id' => 'EXTRA', 'content' => Loc::getMessage('LOGHOUSE_LOGGER_LOG_COL_EXTRA'), 'default' => false],
]);

$filterOptions = new FilterOptions($tableId);
$filterData    = $filterOptions->getFilter($filterFields);

$query = LogTable::query()->setSelect(['*']);

if (!empty($filterData['ID'])) {
    $query->where('ID', (int)$filterData['ID']);
}
if (!empty($filterData['CHANNEL'])) {
    $query->whereLike('CHANNEL', '%' . $likeEscape((string)$filterData['CHANNEL']) . '%');
}
if (!empty($filterData['LEVEL_NAME'])) {
    $levels = is_array($filterData['LEVEL_NAME']) ? $filterData['LEVEL_NAME'] : [$filterData['LEVEL_NAME']];
    $levels = array_values(array_filter(array_map('strval', $levels)));
    if ($levels) {
        $query->whereIn('LEVEL_NAME', $levels);
    }
}
if (!empty($filterData['MESSAGE'])) {
    $query->whereLike('MESSAGE', '%' . $likeEscape((string)$filterData['MESSAGE']) . '%');
}
if (!empty($filterData['CREATED_AT_from'])) {
    $query->where('CREATED_AT', '>=', new BxDateTime($filterData['CREATED_AT_from'], 'd.m.Y H:i:s'));
}
if (!empty($filterData['CREATED_AT_to'])) {
    $query->where('CREATED_AT', '<=', new BxDateTime($filterData['CREATED_AT_to'], 'd.m.Y H:i:s'));
}

global $by, $order;
$sortBy    = strtoupper((string)($by ?? 'ID'));
$sortOrder = strtoupper((string)($order ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
if (!LogTable::getEntity()->hasField($sortBy)) {
    $sortBy = 'ID';
}
$query->setOrder([$sortBy => $sortOrder]);

if ($list->isTotalCountRequest()) {
    $query->countTotal(true);
}

$nav = $list->getPageNavigation('pages-loghouse-logger-log');
$query->setOffset($nav->getOffset());
$query->setLimit($nav->getLimit() + 1);

$result = $query->exec();

if ($list->isTotalCountRequest()) {
    $list->sendTotalCountResponse($result->getCount());
}

$pageSize = $list->getNavSize();
$n        = 0;

while ($row = $result->fetch()) {
    $n++;
    if ($n > $pageSize) {
        break;
    }

    /** @var BxDateTime|null $created */
    $created = $row['CREATED_AT'] ?? null;
    $row['CREATED_AT'] = $created instanceof BxDateTime ? $created->format('d.m.Y H:i:s') : (string)$created;

    $rowObj = $list->AddRow($row['ID'], $row);
    $rowObj->AddViewField(
        'MESSAGE',
        '<div style="max-width:600px;white-space:pre-wrap;word-break:break-word;">'
        . htmlspecialcharsbx((string)$row['MESSAGE'])
        . '</div>'
    );
    $rowObj->AddViewField(
        'CONTEXT',
        '<pre style="max-width:600px;max-height:200px;overflow:auto;margin:0;">'
        . htmlspecialcharsbx((string)($row['CONTEXT'] ?? ''))
        . '</pre>'
    );
    $rowObj->AddViewField(
        'EXTRA',
        '<pre style="max-width:600px;max-height:200px;overflow:auto;margin:0;">'
        . htmlspecialcharsbx((string)($row['EXTRA'] ?? ''))
        . '</pre>'
    );

    $rowObj->AddActions([
        [
            'ICON'   => 'delete',
            'TEXT'   => Loc::getMessage('LOGHOUSE_LOGGER_LOG_DELETE'),
            'ACTION' => "if(confirm('" . CUtil::JSEscape(Loc::getMessage('LOGHOUSE_LOGGER_LOG_DELETE_CONF')) . "')) "
                . $list->ActionDoGroup((int)$row['ID'], 'delete'),
        ],
    ]);
}

$nav->setRecordCount($nav->getOffset() + $n);
$list->setNavigation($nav, Loc::getMessage('LOGHOUSE_LOGGER_LOG_NAV'), false);

$list->AddGroupActionTable([
    'delete' => Loc::getMessage('LOGHOUSE_LOGGER_LOG_DELETE'),
]);

$list->CheckListMode();

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

$clearAction = $APPLICATION->GetCurPage() . '?lang=' . LANGUAGE_ID;
?>
<div style="margin:10px 0;">
    <form method="post" action="<?= htmlspecialcharsbx($clearAction) ?>"
          onsubmit="return confirm('<?= CUtil::JSEscape(Loc::getMessage('LOGHOUSE_LOGGER_LOG_CLEAR_CONF')) ?>');">
        <?= bitrix_sessid_post() ?>
        <input type="hidden" name="action" value="clear_all">
        <button type="submit" class="adm-btn adm-btn-delete">
            <?= Loc::getMessage('LOGHOUSE_LOGGER_LOG_CLEAR') ?>
        </button>
    </form>
</div>
<?php

$list->DisplayFilter($filterFields);
$list->DisplayList();

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
