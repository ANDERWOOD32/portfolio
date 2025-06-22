<?php
$_SERVER['DOCUMENT_ROOT'] = '/home/h905155964/ptfilter.ru/docs';
define('BX_BUFFER_USED', true);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('NO_AGENT_STATISTIC', true);
define('STOP_STATISTICS', true);
define('SITE_ID', 's1');

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

// Обычный кеш
BXClearCache(true);

// Управляемый кеш
if (class_exists('CCacheManager')) {
    global $CACHE_MANAGER;
    $CACHE_MANAGER->CleanAll();
}

// Stack-кеш
if (class_exists('CStackCacheManager')) {
    global $stackCacheManager;
    $stackCacheManager->CleanAll();
}

// Статический HTML кеш
if (class_exists('\Bitrix\Main\Data\StaticHtmlCache')) {
    \Bitrix\Main\Data\StaticHtmlCache::getInstance()->deleteAll();
}

// Очистка директорий вручную
function deleteDirectory($dir) {
    if (!file_exists($dir)) return;
    $items = array_diff(scandir($dir), ['.', '..']);
    foreach ($items as $item) {
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            deleteDirectory($path);
        } else {
            @unlink($path);
        }
    }
    @rmdir($dir);
}

// Очистка кеша файловой системы
$dirsToClear = [
    $_SERVER['DOCUMENT_ROOT'] . '/bitrix/cache',
    $_SERVER['DOCUMENT_ROOT'] . '/bitrix/managed_cache',
    $_SERVER['DOCUMENT_ROOT'] . '/upload/tmp',
];

foreach ($dirsToClear as $dir) {
    deleteDirectory($dir);
}

// Удаление служебного кеша (опционально)
@unlink($_SERVER['DOCUMENT_ROOT'] . '/bitrix/.bx_cache');

echo "Все типы кеша очищены.\n";