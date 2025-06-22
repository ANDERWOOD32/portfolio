<?php
$_SERVER['DOCUMENT_ROOT'] = '/var/www/u2716862/data/www/moscow.krugozor-clinic.ru';
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader;
use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\PropertyTable;

// Подключаем модули
Loader::includeModule("iblock");

// Конфигурация
$iblockId = 7; // ID инфоблока "sitemoscow"
$clinicIblockId = 9; // ID инфоблока "integrations" для адресов клиник
$outputFile = $_SERVER["DOCUMENT_ROOT"] . "/msk_feed.xml"; // Путь к файлу YML
$siteUrl = "https://moscow.krugozor-clinic.ru";

// Функция для получения адресов клиник
function getClinicAddresses($clinicIds, $clinicIblockId)
{
    $addresses = [];

    // Если передан один ID, преобразуем в массив
    $clinicIds = is_array($clinicIds) ? $clinicIds : [$clinicIds];

    if (empty($clinicIds)) {
        return $addresses;
    }

    // Выполняем запрос
    $result = \CIBlockElement::GetList(
        [],
        [
            'IBLOCK_ID' => $clinicIblockId,
            'ID' => $clinicIds,
            'ACTIVE' => 'Y'
        ],
        false,
        false,
        ['ID', 'PROPERTY_ADDRESS']
    );

    while ($clinic = $result->GetNext()) {
        if (!empty($clinic['PROPERTY_ADDRESS_VALUE'])) {
            $addresses[] = $clinic['PROPERTY_ADDRESS_VALUE'];
        }
    }

    return $addresses;
}
// Функция для форматирования YML
function createYmlFile($elements, $outputFile, $siteUrl, $clinicIblockId)
{
    // Открываем файл для записи
    $xml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?><yml_catalog/>");
    $xml->addAttribute('date', date('Y-m-d H:i'));

    // Добавляем корневой элемент <shop>
    $shop = $xml->addChild('shop');
    $shop->addChild('name', 'КРУГОЗОР');
    $shop->addChild('company', 'Клиника "КРУГОЗОР" в Москве');
    $shop->addChild('url', $siteUrl);
    $shop->addChild('email', 'info@krugozor-moscow.ru');
    $shop->addChild('picture', 'https://moscow.krugozor-clinic.ru/upload/uf/060/b9adsy1ei74c77i6m7pkuzetwxekkwmw.png');
    $shop->addChild('platform', 'BSM/Yandex/Market');
    $shop->addChild('version', '2.11.3');

    // Добавляем валюты
    $currencies = $shop->addChild('currencies');
    $currency = $currencies->addChild('currency');
    $currency->addAttribute('id', 'RUR');
    $currency->addAttribute('rate', '1');

    // Добавляем категории
    $categories = $shop->addChild('categories');
    $category = $categories->addChild('category', 'Врач');
    $category->addAttribute('id', '1');

    // Добавляем наборы
    $sets = $shop->addChild('sets');
    $set = $sets->addChild('set');
    $set->addAttribute('id', 'oftalmolog');
    $set->addChild('name', 'Врач-офтальмолог');
    $set->addChild('url', $siteUrl . '/vrachi/');

    // Добавляем секцию offers
    $offers = $shop->addChild('offers');
    $addedOffers = []; // Для отслеживания уникальных ID

    foreach ($elements as $element) {

        // Уникальный ID предложения
        $offerId = 'vrach' . $element['ID'];

        // Проверка на дубли
        if (in_array($offerId, $addedOffers)) {
            continue; // Пропускаем, если ID уже добавлен
        }

        $offer = $offers->addChild('offer');
        $offer->addAttribute('id', 'vrach' . $element['ID']);
        $offer->addAttribute('group_id', $element['ID']);

        $offer->addChild('name', htmlspecialchars($element['NAME']));
        $offer->addChild('url', $siteUrl . '/vrachi/' . $element['CODE'] . '/');

        $price = $offer->addChild('price', htmlspecialchars($element['PRICE']));
        $price->addAttribute('from', 'true');

        $offer->addChild('oldprice', htmlspecialchars($element['PRICE'] + 1000));
        $offer->addChild('currencyId', 'RUR');
        $offer->addChild('sales_notes', 'Прием');
        $offer->addChild('set-ids', 'oftalmolog');

        $offer->addChild('picture', $siteUrl . htmlspecialchars(CFile::GetPath($element['PREVIEW_PICTURE'])));
        $offer->addChild('description', htmlspecialchars($element['SPEC']));
        $offer->addChild('categoryId', '1');

        $nameParts = explode(' ', $element['NAME']);
        $offer->addChild('param', htmlspecialchars($nameParts[0]))->addAttribute('name', 'Фамилия');
        $offer->addChild('param', htmlspecialchars($nameParts[1] ?? '')) ->addAttribute('name', 'Имя');
        $offer->addChild('param', htmlspecialchars($nameParts[2] ?? '')) ->addAttribute('name', 'Отчество');

        // Извлекаем только цифры из стажа работы
        $experience = preg_replace('/\D/', '', $element['EXP']);
        $offer->addChild('param', htmlspecialchars($experience))->addAttribute('name', 'Годы опыта');

        $offer->addChild('param', 'г. Москва')->addAttribute('name', 'Город');

        // Добавляем адрес клиники
        $clinicAddresses = getClinicAddresses($element['CLINIC'], $clinicIblockId);
        if (!empty($clinicAddresses)) {
            $offer->addChild('param', htmlspecialchars($clinicAddresses[0]))->addAttribute('name', 'Адрес клиники');
        }

        $offer->addChild('param', 'Клиника КРУГОЗОР')->addAttribute('name', 'Название клиники');

        // Сохраняем ID в массив
        $addedOffers[] = $offerId;
    }

    // Записываем XML в файл
    $xml->asXML($outputFile);
}

// Получаем активные элементы инфоблока
$elements = [];

$result = \CIBlockElement::GetList(
    [],
    [
        'IBLOCK_ID' => $iblockId,
        'ACTIVE' => 'Y'
    ],
    false,
    false,
    [
        'ID',
        'NAME',
        'CODE',
        'PREVIEW_PICTURE',
        'PROPERTY_PRICE',
        'PROPERTY_EXP',
        'PROPERTY_RANK',
        'PROPERTY_SPEC',
        'PROPERTY_CLINIC'
    ]
);

while ($element = $result->GetNext()) {
    $elements[] = [
        'ID' => $element['ID'],
        'NAME' => $element['NAME'],
        'CODE' => $element['CODE'],
        'PREVIEW_PICTURE' => $element['PREVIEW_PICTURE'],
        'PRICE' => $element['PROPERTY_PRICE_VALUE'],
        'EXP' => $element['PROPERTY_EXP_VALUE'],
        'RANK' => $element['PROPERTY_RANK_VALUE'],
        'SPEC' => $element['PROPERTY_SPEC_VALUE'],
        'CLINIC' => $element['PROPERTY_CLINIC_VALUE'],
    ];
}

if (!empty($elements)) {
    // Генерируем YML-файл
    createYmlFile($elements, $outputFile, $siteUrl, $clinicIblockId);
    echo "YML-файл успешно обновлен: " . $outputFile;
} else {
    echo "Нет активных элементов для генерации YML-файла.";
}

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");
?>
