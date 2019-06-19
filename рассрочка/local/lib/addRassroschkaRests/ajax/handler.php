<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
header('Content-type: application/json');

require_once ($_SERVER['DOCUMENT_ROOT'].'/local/lib/addRassroschkaRests/ajax/class.php');



$obj = new RassrochkaRestInfo;

//Запрос данных при открытии сделки для отображения/не отображения кнопки опроса
if($_POST['ACTION'] === 'GIVE_ME_DEAL_DATA') $obj->getDealDataOnOpeningDeal($_POST['DEAL_ID']);