<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

CModule::IncludeModule("crm");

class CustomDealsReports extends CBitrixComponent{

    const ALLOWED_CATEGORIES = [0,1];
    //const ALLOWED_CATEGORIES = [6,7,9];
    const DEAL_TYPE_REF_DIR = 'DEAL_TYPE';

    public function test(){
        return 'Это тест, Уасся!';
    }

    //значения для фильтра направления
    public function getCategoriesForFilter(){
        $result = ['result' => 'Test', 'error' => false];
        $massive = [];
        $categoryIds = \Bitrix\Crm\Category\DealCategory::getAllIDs();
        foreach ($categoryIds as $categoryId){

            if(in_array($categoryId, self::ALLOWED_CATEGORIES)){
                //проверяем, чтобы в направлении были сделки
                $hasDeals = $this->checkDealsInCategoryById($categoryId);
                if($hasDeals){
                    //получаем стадии для селекта

                    $massive[] = [
                        'ID' => $categoryId,
                        'NAME' => $this->getCategoryNameById($categoryId),
                        //'STAGES' => $stagesMassive,
                    ];
                }
            }

        }
        ($massive) ? $result['result'] = $massive : $result['error'] = 'Помилка підчас запиту напрямків угод!';

        $this->sentAnswer($result);
    }


    //получение списка категорий для фильтров
    public function getStagesListForFilter($categoryId){
        $result = ['result' => false, 'error' => false];

        $result = false;

        $stages = $this->getCategoryStages($categoryId);
        $stagesMassive = [
            /*[
                'ID' => 0,
                'NAME' => 'Не выбрано',
            ],*/
        ];
        foreach ($stages as $id => $name){
            $stagesMassive[] = [
                'ID' => $id,
                'NAME' => $name,
            ];
        }

        ($stagesMassive) ? $result['result'] = $stagesMassive : $result['error'] = 'Виникла помилка підчас запиту стадій угод!';


         $this->sentAnswer($result);
    }

    //список типов сделок (ЖК) из справочника в фильтр
    public function getDealTypesFromRefDirectory(){
        $result = ['result' => false, 'error' => false];
        $refFilter = ['ENTITY_ID' => self::DEAL_TYPE_REF_DIR];
        $refResult = $this->getReferenceBook($refFilter);
        if($refResult) $result['result'] = $refResult;
        else $result['error'] = 'Помилка підчас запиту типів угоди!';

        $this->sentAnswer($result);
    }

    //список типов оплат из сделки
    public function getPayTypeListForFilter(){
        $result = ['result' => false, 'error' => false];
        $fildFilter = ["USER_FIELD_NAME" => 'UF_CRM_1550841222']; // это запрос поля Тип оплати
        $optionsResult = $this->getPropertyOptionsFromDeal($fildFilter);
        if($optionsResult)
            $result['result'] = array_merge([['ID' => '', 'VALUE' => 'Не обрано']],$optionsResult);
        else $result['error'] = 'Помилка підчас запиту типів оплат!';

        $this->sentAnswer($result);
    }


    //список подарков в фильтр
    public function getPresentsListForFilter(){
        $result = ['result' => false, 'error' => false];
        $fildFilter = ["USER_FIELD_NAME" => 'UF_CRM_1554977040'];
        $optionsResult = $this->getPropertyOptionsFromDeal($fildFilter);
        ($optionsResult) ? $result['result'] = $optionsResult : $result['error'] = 'Помилка підчас запиту подарунків!';

        $this->sentAnswer($result);
    }

    //список платежей погашения в 2 поля фильтров
    public function getPaymentNumbersListForFilter(){
        $result = ['result' => false, 'error' => false];
        $fildFilter = ["USER_FIELD_NAME" => 'UF_CRM_1550227956'];
        $optionsResult = $this->getPropertyOptionsFromDeal($fildFilter);
        if($optionsResult)
            $result['result'] = array_merge([['ID' => '', 'VALUE' => 'Не обрано']],$optionsResult);
        else $result['error'] = 'Помилка підчас запиту кількості платежів!';

        $this->sentAnswer($result);
    }

    //ответ в консоль
    private function sentAnswer($answ){
        echo json_encode($answ);
    }

    //для проверки наличия сделок в направлении
    private function checkDealsInCategoryById($category_id){
        $result = \Bitrix\Crm\Category\DealCategory::hasDependencies($category_id);
        return $result;
    }

    private function getCategoryNameById($category_id){
        return $name = \Bitrix\Crm\Category\DealCategory::getName($category_id);
    }

    //ломается порядок вывода во vue, хотя php дает правильтный порядок, но без id
    private function getCategoryStages($category_id){
        $stages = \Bitrix\Crm\Category\DealCategory::getStageList($category_id);
        return $stages;
    }

    //получение значения справочников
    private function getReferenceBook($filter){
        //array('ENTITY_ID' => 'CONTACT_TYPE', 'STATUS_ID' => $ID)
        $result = [];
        $db_list = CCrmStatus::GetList([], $filter);
        while ($ar_result = $db_list->GetNext()){

            //убираем из названий єкранирующие єлементі
            $ar_result['NAME'] = HTMLToTxt($ar_result['NAME']);
            $result[] = $ar_result;
        }
        return $result;
    }

    //получение всех значений из пользовательсокго поля типа список сделки
    private function getPropertyOptionsFromDeal($filter){
        //пример фильтра
       // $filter = ["USER_FIELD_NAME" => 'UF_CRM_1550841222'];

        $res = [];
        $rsEnum = CUserFieldEnum::GetList(array(), $filter);
        while($arEnum = $rsEnum->GetNext()){
            $res[] = [
                'ID' => $arEnum['ID'],
                'VALUE' => $arEnum['VALUE'],
            ];
        }
        return $res;
    }


}