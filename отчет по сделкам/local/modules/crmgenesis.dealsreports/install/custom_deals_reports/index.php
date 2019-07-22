<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
use \Bitrix\Main;
use \Bitrix\Main\Loader;
use Bitrix\Main\UserTable;

$APPLICATION->SetTitle("Страница отчетов по сделкам");
CJSCore::Init();


echo "<h1 class='test1'>Отчеты по сделкам</h1><br>";


echo '<br><br><hr><br>';

$APPLICATION->IncludeComponent(
    "crmgenesis:deals_reports.component",
    "first",
    Array(
    ),
    false
);