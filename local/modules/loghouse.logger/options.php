<?php

use Bitrix\Main\Loader;
use Loghouse\Logger\Options;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Loghouse\Logger\Cli\TestLogCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Application as ConsoleApplication;

/** @global CMain $APPLICATION */
/** @global CUser $USER */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$module_id = 'loghouse.logger';

Loc::loadMessages(__FILE__);

if (!$USER->IsAdmin()) {
    $APPLICATION->ThrowException(Loc::getMessage('LOGHOUSE_LOGGER_ACCESS_DENIED'));

    return;
}

Loader::includeModule($module_id);

$testResult = null;
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_REQUEST['RunTest'])
    && check_bitrix_sessid()
) {
    $console = new ConsoleApplication();
    $console->setAutoExit(false);
    $console->addCommand(new TestLogCommand());
    $buffer   = new BufferedOutput();
    $exitCode = $console->run(new ArrayInput(['command' => 'logger:test']), $buffer);
    $testResult = [
        'exit'   => $exitCode,
        'output' => $buffer->fetch(),
    ];
}

$formatters = array_map(
    static fn(string $code): string => Loc::getMessage($code) ?? $code,
    Options::formatters()
);

$levelsRef = array_combine(
    array_keys(Options::levels()),
    array_map(static fn(string $v): string => strtoupper($v), array_keys(Options::levels()))
);

$rotations = array_map(
    static fn(string $code): string => Loc::getMessage($code) ?? $code,
    Options::rotations()
);

$tabs = [
    [
        'DIV'     => 'edit_main',
        'TAB'     => Loc::getMessage('LOGHOUSE_LOGGER_TAB_MAIN'),
        'TITLE'   => Loc::getMessage('LOGHOUSE_LOGGER_TAB_MAIN_TITLE'),
        'OPTIONS' => [
            Loc::getMessage('LOGHOUSE_LOGGER_SECTION_FORMAT'),
            ['formatter', Loc::getMessage('LOGHOUSE_LOGGER_OPT_FORMATTER'), Options::FORMATTER_TEXT, ['selectbox', $formatters]],
            ['channel', Loc::getMessage('LOGHOUSE_LOGGER_OPT_CHANNEL'), 'app', ['text', 30]],
            ['min_level', Loc::getMessage('LOGHOUSE_LOGGER_OPT_MIN_LEVEL'), 'debug', ['selectbox', $levelsRef]],
            Loc::getMessage('LOGHOUSE_LOGGER_SECTION_TARGETS'),
            ['target_file', Loc::getMessage('LOGHOUSE_LOGGER_OPT_TARGET_FILE'), 'Y', ['checkbox']],
            ['target_db', Loc::getMessage('LOGHOUSE_LOGGER_OPT_TARGET_DB'), 'N', ['checkbox']],
            ['target_telegram', Loc::getMessage('LOGHOUSE_LOGGER_OPT_TARGET_TG'), 'N', ['checkbox']],
        ],
    ],
    [
        'DIV'     => 'edit_file',
        'TAB'     => Loc::getMessage('LOGHOUSE_LOGGER_TAB_FILE'),
        'TITLE'   => Loc::getMessage('LOGHOUSE_LOGGER_TAB_FILE_TITLE'),
        'OPTIONS' => [
            ['file_dir', Loc::getMessage('LOGHOUSE_LOGGER_OPT_FILE_DIR'), '/logs', ['text', 60]],
            ['file_min_level', Loc::getMessage('LOGHOUSE_LOGGER_OPT_FILE_LEVEL'), 'debug', ['selectbox', $levelsRef]],
            ['file_rotation', Loc::getMessage('LOGHOUSE_LOGGER_OPT_FILE_ROTATION'), Options::ROTATION_DAY, ['selectbox', $rotations]],
            ['file_max_files', Loc::getMessage('LOGHOUSE_LOGGER_OPT_FILE_MAX_FILES'), '30', ['text', 10]],
        ],
    ],
    [
        'DIV'     => 'edit_db',
        'TAB'     => Loc::getMessage('LOGHOUSE_LOGGER_TAB_DB'),
        'TITLE'   => Loc::getMessage('LOGHOUSE_LOGGER_TAB_DB_TITLE'),
        'OPTIONS' => [
            ['db_min_level', Loc::getMessage('LOGHOUSE_LOGGER_OPT_DB_LEVEL'), 'info', ['selectbox', $levelsRef]],
            '__db_journal_link',
        ],
    ],
    [
        'DIV'     => 'edit_tg',
        'TAB'     => Loc::getMessage('LOGHOUSE_LOGGER_TAB_TG'),
        'TITLE'   => Loc::getMessage('LOGHOUSE_LOGGER_TAB_TG_TITLE'),
        'OPTIONS' => [
            ['telegram_token', Loc::getMessage('LOGHOUSE_LOGGER_OPT_TG_TOKEN'), '', ['text', 60]],
            ['telegram_chat_id', Loc::getMessage('LOGHOUSE_LOGGER_OPT_TG_CHAT_ID'), '', ['text', 30]],
            ['telegram_min_level', Loc::getMessage('LOGHOUSE_LOGGER_OPT_TG_LEVEL'), 'error', ['selectbox', $levelsRef]],
        ],
    ],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && (isset($_REQUEST['Update']) || isset($_REQUEST['Apply']) || isset($_REQUEST['RestoreDefaults']))
    && check_bitrix_sessid()
) {
    if (isset($_REQUEST['RestoreDefaults'])) {
        Option::delete($module_id);
    } else {
        foreach ($tabs as $tab) {
            foreach ($tab['OPTIONS'] as $option) {
                if (!is_array($option)) {
                    continue;
                }
                [$name, , , $type] = $option + [null, null, '', ['text']];
                $kind = $type[0] ?? 'text';
                if ($kind === 'checkbox') {
                    $value = isset($_REQUEST[$name]) && $_REQUEST[$name] === 'Y' ? 'Y' : 'N';
                } else {
                    $value = trim((string)($_REQUEST[$name] ?? ''));
                }
                Option::set($module_id, $name, $value);
            }
        }
    }

    // Защита от open redirect: принимаем только локальный путь (начинается с одиночного "/",
    // без схемы и без protocol-relative "//"), чтобы значение из запроса не увело на внешний хост.
    $isLocalUrl = static fn(string $url): bool => $url !== ''
        && $url[0] === '/'
        && !str_starts_with($url, '//')
        && !str_starts_with($url, '/\\')
        && !preg_match('~^/[a-z][a-z0-9+.-]*:~i', $url);

    $backUrl = (string)($_REQUEST['back_url_settings'] ?? '');
    if (!isset($_REQUEST['Apply']) && $backUrl !== '' && $isLocalUrl($backUrl)) {
        LocalRedirect($backUrl);
    }

    LocalRedirect(
        $APPLICATION->GetCurPage()
        . '?mid=' . urlencode($module_id)
        . '&lang=' . urlencode(LANGUAGE_ID)
        . (isset($_REQUEST['back_url_settings'])
            ? '&back_url_settings=' . urlencode((string)$_REQUEST['back_url_settings'])
            : '')
    );
}

$tabControl = new CAdminTabControl('tabControl', array_map(
    static fn(array $t): array => ['DIV' => $t['DIV'], 'TAB' => $t['TAB'], 'TITLE' => $t['TITLE']],
    $tabs
));

$tabControl->Begin();
?>
<?php if ($testResult !== null): ?>
    <div class="adm-info-message-wrap <?= $testResult['exit'] === 0 ? 'adm-info-message-green' : 'adm-info-message-red' ?>">
        <div class="adm-info-message">
            <div class="adm-info-message-title">
                <?= Loc::getMessage($testResult['exit'] === 0 ? 'LOGHOUSE_LOGGER_TEST_OK' : 'LOGHOUSE_LOGGER_TEST_FAIL') ?>
            </div>
            <pre style="white-space:pre-wrap;margin:6px 0 0;"><?= htmlspecialcharsbx($testResult['output']) ?></pre>
        </div>
    </div>
<?php endif; ?>
<form method="post" action="<?= $APPLICATION->GetCurPage() ?>?mid=<?= htmlspecialcharsbx($module_id) ?>&amp;lang=<?= LANGUAGE_ID ?>">
    <?= bitrix_sessid_post() ?>
    <?php foreach ($tabs as $tab): ?>
        <?php $tabControl->BeginNextTab(); ?>
        <?php foreach ($tab['OPTIONS'] as $option): ?>
            <?php if ($option === '__db_journal_link'): ?>
                <tr>
                    <td colspan="2">
                        <a href="/bitrix/admin/loghouse_logger_log_list.php?lang=<?= LANGUAGE_ID ?>" target="_blank">
                            <?= Loc::getMessage('LOGHOUSE_LOGGER_DB_JOURNAL_LINK') ?>
                        </a>
                    </td>
                </tr>
            <?php elseif (!is_array($option)): ?>
                <tr class="heading"><td colspan="2"><b><?= htmlspecialcharsbx((string)$option) ?></b></td></tr>
            <?php else: __AdmSettingsDrawRow($module_id, $option); ?>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endforeach; ?>
    <?php $tabControl->Buttons(); ?>
    <input type="submit" name="Update" value="<?= Loc::getMessage('LOGHOUSE_LOGGER_BTN_SAVE') ?>" class="adm-btn-save">
    <input type="submit" name="Apply" value="<?= Loc::getMessage('LOGHOUSE_LOGGER_BTN_APPLY') ?>">
    <input type="submit" name="RestoreDefaults" value="<?= Loc::getMessage('LOGHOUSE_LOGGER_BTN_DEFAULT') ?>"
        onclick="return confirm('?');">
    <input type="submit" name="RunTest" value="<?= Loc::getMessage('LOGHOUSE_LOGGER_BTN_TEST') ?>">
    <?php if (!empty($_REQUEST['back_url_settings'])): ?>
        <input type="hidden" name="back_url_settings" value="<?= htmlspecialcharsbx((string)$_REQUEST['back_url_settings']) ?>">
    <?php endif; ?>
</form>
<?php
$tabControl->End();
