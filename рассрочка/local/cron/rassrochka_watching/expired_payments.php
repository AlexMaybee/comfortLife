<?php
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");


require_once($_SERVER["DOCUMENT_ROOT"] . "/local/cron/rassrochka_watching/functions_class.php"); //в нем все главные функции protected, класс наследуется остальными для получения методов';

$classStart = new RassrochkaWatcher();

//запуск ночь первого числа каждого месяца
$expiredPaymentsSearch = $classStart->watchExpiredPayments();
$classStart->logging('tasks_expired_payments.log',$expiredPaymentsSearch);

