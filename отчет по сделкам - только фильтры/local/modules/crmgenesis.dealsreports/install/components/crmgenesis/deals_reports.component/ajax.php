<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
header('Content-type: application/json');

require_once 'class.php';

//категории для фильтра
if($_POST['ACTION'] == 'GIVE_ME_CATEGORIES_FOR_FILTER'){
    $obj = new CustomDealsReports;
    $obj->getCategoriesForFilter();
}

//список стадий по категории сделки
if($_POST['ACTION'] == 'GIVE_ME_STAGES_LIST_FOR_FILTER'){
    $obj = new CustomDealsReports;
    $obj->getStagesListForFilter($_POST['CATEGORY_ID']);
}

//список типов сделки (названия ЖК)
if($_POST['ACTION'] == 'GIVE_ME_DEAL_TYPES_LIST_FOR_FILTER'){
    $obj = new CustomDealsReports;
    $obj->getDealTypesFromRefDirectory();
}

//список типов оплат
if($_POST['ACTION'] == 'GIVE_ME_PAY_TYPE_LIST_FOR_FILTER'){
    $obj = new CustomDealsReports;
    $obj->getPayTypeListForFilter();
}

//список подарков
if($_POST['ACTION'] == 'GIVE_ME_PESENTS_LIST_FOR_FILTER'){
    $obj = new CustomDealsReports;
    $obj->getPresentsListForFilter();
}

//Список кол-ва платежей в 2 поля фильтров
if($_POST['ACTION'] == 'GIVE_ME_PAYMENT_NUMBERS_LIST_FOR_FILTER'){
    $obj = new CustomDealsReports;
    $obj->getPaymentNumbersListForFilter();
}