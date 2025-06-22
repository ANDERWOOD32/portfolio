<?php

// Модуль Яндекса не работает. В будущем переделать с использованием D7

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader;

Loader::includeModule("iblock");
Loader::includeModule("catalog");
Loader::includeModule("currency");

header("Content-Type: text/xml; charset=utf-8");

// Настройки
$IBLOCK_ID = 13;
$SITE_URL = "https://mg13z.ru";
$SHOP_NAME = "ТУЛФОВОРК";
$COMPANY_NAME = "ООО «ТУЛФОВОРК»";

// Получение категорий (разделов)
$categories = [];
$resSections = CIBlockSection::GetList(
    ["LEFT_MARGIN" => "ASC"],
    ["IBLOCK_ID" => $IBLOCK_ID, "ACTIVE" => "Y"],
    false,
    ["ID", "IBLOCK_SECTION_ID", "NAME"]
);
while ($section = $resSections->Fetch()) {
    $categories[$section["ID"]] = [
        "ID" => $section["ID"],
        "PARENT_ID" => $section["IBLOCK_SECTION_ID"],
        "NAME" => $section["NAME"]
    ];
}

// Получение товаров
$arSelect = [
    "ID", "IBLOCK_ID", "NAME", "DETAIL_PAGE_URL", "PREVIEW_PICTURE", "IBLOCK_SECTION_ID"
];
$arFilter = [
    "IBLOCK_ID" => $IBLOCK_ID,
    "ACTIVE" => "Y",
    "!PREVIEW_PICTURE" => false
];
$res = CIBlockElement::GetList([], $arFilter, false, false, $arSelect);

echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
?>
<!DOCTYPE yml_catalog SYSTEM "shops.dtd">
<yml_catalog date="<?= date("Y-m-d H:i") ?>">
<shop>
    <name><?= htmlspecialchars($SHOP_NAME) ?></name>
    <company><?= htmlspecialchars($COMPANY_NAME) ?></company>
    <url><?= htmlspecialchars($SITE_URL) ?></url>
    <currencies>
        <currency id="RUR" rate="1"/>
    </currencies>
    <categories>
<?php foreach ($categories as $cat): ?>
    <category id="<?= $cat['ID'] ?>"<?= $cat['PARENT_ID'] ? ' parentId="' . $cat['PARENT_ID'] . '"' : '' ?>>
        <?= htmlspecialchars($cat['NAME']) ?>
    </category>
<?php endforeach; ?>
    </categories>
    <offers>
<?php
while ($ob = $res->GetNextElement()) {
    $fields = $ob->GetFields();
    $props = $ob->GetProperties();
    $id = $fields["ID"];

    // Получение цены
    $arPrice = CPrice::GetBasePrice($id);
    if (!$arPrice || !$arPrice['PRICE']) {
        continue;
    }
    $price = round($arPrice['PRICE'], 2);

    $url = htmlspecialchars($SITE_URL . $fields["DETAIL_PAGE_URL"]);
    $name = htmlspecialchars($fields["NAME"]);
    $sectionId = $fields["IBLOCK_SECTION_ID"];
    if (!$sectionId) continue;

    // Картинки
    $pictures = [];
    if ($fields["PREVIEW_PICTURE"]) {
        $pictures[] = $SITE_URL . CFile::GetPath($fields["PREVIEW_PICTURE"]);
    }
    if (!empty($props["PICTURES"]["VALUE"])) {
        foreach ((array)$props["PICTURES"]["VALUE"] as $picId) {
            $pictures[] = $SITE_URL . CFile::GetPath($picId);
        }
    }

    // Параметры
    $paramList = [
        "ARTICLE" => "Артикул",
        "HIT" => "Хит",
        "NEW" => "Новинка",
        "RECOMMEND" => "Рекомендуем",
        "SIZES" => "Размеры",
        "rating" => "Рейтинг",
        "vote_sum" => "Сумма оценок",
        "P_D_HAR_TOP_OTOP" => "Диаметр вала",
        "P_LENGTH_HAR_OTOP" => "Длина",
        "P_RING_F_HAR_OTOP" => "Тип ответного кольца",
        "P_MATERIAL_ELAST_TOP_OTOP" => "Материал эластомера",
        "P_MATERIAL_PARA_TOP_OTOP" => "Материалы пары трения",
        "P_T_HAR_OTOP" => "Температура",
        "P_MANUFACTURER" => "Производитель",
        "P_IN_PUMPS_TOP" => "Насосы",
        "APPLICATION_TOP" => "Применение",
        "P_PRESSUERE_HAR" => "Давление",
        "P_OSEV_HAR" => "Допустимое осевое смещение",
        "P_STATE_STANDARD_HAR" => "ГОСТ",
        "P_SIZE_HAR" => "Габариты",
        "P_WEIGHT_HAR" => "Вес",
        "P_MATERIAL_DETALEY" => "Материал металлических деталей"
    ];
    ?>
    <offer id="<?= $id ?>" available="true">
        <url><?= $url ?></url>
        <price><?= $price ?></price>
        <currencyId>RUR</currencyId>
        <categoryId><?= $sectionId ?></categoryId>
        <?php foreach ($pictures as $pic): ?>
            <picture><?= htmlspecialchars($pic) ?></picture>
        <?php endforeach; ?>
        <name><?= $name ?></name>
        <?php if (!empty($props["P_MANUFACTURER"]["VALUE"])): ?>
            <vendor><?= htmlspecialchars($props["P_MANUFACTURER"]["VALUE"]) ?></vendor>
        <?php endif; ?>
        <?php
        foreach ($paramList as $code => $title) {
            $value = $props[$code]["VALUE"];
            if (!empty($value)) {
                if (is_array($value)) {
                    foreach ($value as $val) {
                        if (!empty($val)) {
                            echo "<param name=\"" . htmlspecialchars($title) . "\">" . htmlspecialchars($val) . "</param>\n";
                        }
                    }
                } else {
                    echo "<param name=\"" . htmlspecialchars($title) . "\">" . htmlspecialchars($value) . "</param>\n";
                }
            }
        }
        ?>
    </offer>
<?php } ?>
    </offers>
</shop>
</yml_catalog>