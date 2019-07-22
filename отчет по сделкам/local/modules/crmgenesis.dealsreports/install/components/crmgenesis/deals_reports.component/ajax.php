<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
header('Content-type: application/json');

require_once 'class.php';


if($_POST['ACTION'] == 'GIVE_ME_CATEGORIES_FOR_FILTER'){
    $obj = new CustomDealsReports;
    $obj->getCategoriesForFilter();
}