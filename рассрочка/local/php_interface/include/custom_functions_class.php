<?php
use Bitrix\Main\Web\HttpClient;


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
    protected function getOneDealData($arFilter,$arSelect){
        $db_list = CCrmDeal::GetListEx(Array("ID" => "ASC"), $arFilter, false, false, $arSelect, array()); //получение пользовательских полей сделки по ID
        if($ar_result = $db_list->GetNext()) return $ar_result;
        return false;
    }

    //получение списка элементов по фильтру - простой
    protected function getListElementsByFilter($arFilter,$arSelect){
        $result = array();
        $resultList = CIBlockElement::GetList(array(), $arFilter, false, false, $arSelect);
        while ($list = $resultList->Fetch()) {
            $result[] = $list;
        }
        return $result;
    }

    //получение списка элементов по фильтру - свойства - каждое отдельным массивом
    protected function getListElementsAdnPropsByFilter($arFilter,$arSelect){
        //пример получения всех свойств (не работает в обычном виде) -  ["ID", "IBLOCK_ID", "NAME","PROPERTY_*"]
        //без запроса в выборке "IBLOCK_ID" не будет работать!!!
        $resultList = CIBlockElement::GetList(array(), $arFilter, false, false,$arSelect);
        while($ob = $resultList->GetNextElement()){
            $result[] = [
                'FIELDS' => $ob->GetFields(),
                'PROPERTIES' => $ob->GetProperties(),
            ];
        }
        return $result;
    }

    //создание элемента списка
    protected function createNewListElement($fields){
        $res = [
            'result' => false,
            'error' => false,
        ];
        $elem = new CIBlockElement;
        $new_id = $elem->Add($fields);
        if($new_id) $res['result'] = $new_id;
        else $res['error'] = $elem->LAST_ERROR;
        return $res;
    }

    //получение значения справочников
    protected function getReferenceBook($filter){
        //array('ENTITY_ID' => 'CONTACT_TYPE', 'STATUS_ID' => $ID)

        $db_list = CCrmStatus::GetList([], $filter);
        $result = [];
        if ($ar_result = $db_list->GetNext()) $result = $ar_result;
        return $result;
    }

    //получаем значения select для вставки в коммент - передаем значение
    protected function convertSelectValIdToValue($value_number){
        $sel_val = CUserFieldEnum::GetList(array(), array(
            "ID" => $value_number,
        ));
        $arGender = $sel_val->GetNext();
        return $arGender['VALUE'];
    }

    //обновляет ВСЕ ПОЛЯ (Если не указать свойство, то его ранее сохр. знач. ЗАТРЕТ!)
    protected function updateListElementsAllFields($elemId,$newFields){
        $el = new CIBlockElement;
        $res = $el->Update($elemId, $newFields);
        return $res;
    }

    //Перезапись выбранных полей элемента списка (А не всех!!!)
    protected function updatePropertiesInListElement1($elemID,$iBlockId,$property_values){
        $elem = new CIBlockElement;
        $is_updated = $elem->SetPropertyValuesEx($elemID,$iBlockId,$property_values);
        return $is_updated;
    }

    //GET-запрос
    protected function makeGetRequest($urlAndParams){
        $httpClient = new HttpClient();
        $httpClient->setHeader('Content-Type', 'application/json', true);
        $result = $httpClient->get($urlAndParams);
        return json_decode($result);
    }

}