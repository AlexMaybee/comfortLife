<?php
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");
/*CModule::IncludeModule("CRM");
CModule::IncludeModule("tasks");
CModule::IncludeModule("iblock");*/

require_once($_SERVER["DOCUMENT_ROOT"] . "/local/cron/rassrochka_watching/functions_class.php"); //в нем все главные функции protected, класс наследуется остальными для получения методов';

$classStart = new RassrochkaWatcher();

//запуск ночь первого числа каждого месяца
//$smsMailingForExpiredPayments = $classStart->smsMailingForExpiredPayments();


echo '<pre>';
print_r($smsMailingForExpiredPayments);
//$classStart->logging('sms_expired_payments.log',$smsMailingForExpiredPayments);


