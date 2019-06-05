<?php

CJSCore::Init(array("jquery"));


//подключает все, что в папке include
require_once($_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/include/custom_functions_class.php"); //в нем все главные функции protected, класс наследуется остальными для получения методов
require_once($_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/include/rassrochka_deals.php"); //в нем все главные функции работы со сделками и рассрочкой, класс наследуется остальными для получения методов
//подключает все, что в папке include