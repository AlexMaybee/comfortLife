<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
use \Bitrix\Main;
use \Bitrix\Main\Loader;
use Bitrix\Main\UserTable;

$APPLICATION->SetTitle("Звіт по угодах");
CJSCore::Init();
?>

<section class="module-container">
<?
$APPLICATION->IncludeComponent(
    "crmgenesis:deals_reports.component",
    "first",
    Array(
    ),
    false
);
?>
</section>
