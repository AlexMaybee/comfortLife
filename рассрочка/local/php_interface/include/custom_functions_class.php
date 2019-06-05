<?php

CModule::IncludeModule('crm');
//CModule::IncludeModule('calendar');

//здесь функции protected, класс наследуется другими
class CustomFunctions{

    //функция логирования
    protected function logData($fileName,$data){
        $file = $_SERVER['DOCUMENT_ROOT'].'/local/php_interface/'.$fileName;
        file_put_contents($file, print_r($data,true), FILE_APPEND | LOCK_EX);
    }

    //получение данных 1-й сделки по фильтру
    function getOneDealData($arFilter,$arSelect){
        $db_list = CCrmDeal::GetListEx(Array("ID" => "ASC"), $arFilter, false, false, $arSelect, array()); //получение пользовательских полей сделки по ID
        if($ar_result = $db_list->GetNext()) return $ar_result;
        return false;
    }

    //получение списка элементов по фильтру
    protected function getListElementsByFilter($arFilter,$arSelect){
        $result = array();
        $resultList = CIBlockElement::GetList(array(), $arFilter, false, false, $arSelect);
        while ($list = $resultList->Fetch()) {
            $result[] = $list;
        }
        return $result;
    }


}