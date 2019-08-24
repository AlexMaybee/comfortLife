<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

CModule::IncludeModule("crm");

class CustomDealsReports extends CBitrixComponent{

    const ALLOWED_CATEGORIES = [0,1];
    //const ALLOWED_CATEGORIES = [6,7,9];
    const DEAL_TYPE_REF_DIR = 'DEAL_TYPE';
    const IS_RASSROCHKA_TYPE = 90;
    const IS_FULL_PAYED_TYPE = 91;

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
            //$result['result'] = array_merge([['ID' => '', 'VALUE' => 'Не обрано']],$optionsResult);
            $result['result'] = $optionsResult;
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

    //получение данных по фильтрам
    public function getInfoByZhk($post){
        $result = ['result' => false, 'error' => false];

        //массив сделок по фильтру (направление, дата с, дата по)
        $deals_filter = [
            'CATEGORY_ID' => $post['category'],
            ">=BEGINDATE" => date('d.m.Y', strtotime($post['dateFrom'])), //date('m.Y',strtotime('-1 month'))
            "<=BEGINDATE" => date('d.m.Y', strtotime($post['dateTo'])), //date('m.Y',strtotime('-1 month'))
        ];

        //Тип оплаты
        if($post['payType']) $deals_filter['UF_CRM_1550841222'] = $post['payType'];

        //Если выбрана галка "Учитывать закрытые сделки", добавляем в фильтр
        if($post['onlyOpenedDeals'] == 'true') $deals_filter['CLOSED'] = 'N'; //ВОЗВРАЩАЕТ TRUE/FALSE в ВИДЕ СТРОКИ

        //Стадии
        if($post['currentStageId']) $deals_filter['STAGE_ID'] = $post['currentStageId'];

        //Тип угоди (ЖК)
        if($post['dealType']) $deals_filter['TYPE_ID'] = $post['dealType'];

        //Подарунки
        if($post['presents']) $deals_filter['UF_CRM_1554977040'] = $post['presents'];

        //Вартість кв.м.
        //Від
        if(!empty($post['squarePriceFrom'])){
            //Рассрочка - ищем по полю "Вартість 1 кв. м. (залишок) ГРН."
            if($post['payType'] == self::IS_RASSROCHKA_TYPE)
                $deals_filter['>=UF_CRM_1550227752'] = $post['squarePriceFrom'];
            //Остальніе случаи - Вартість 1 кв. м. ГРН. (середня)
            else $deals_filter['>=UF_CRM_1550500088309'] = $post['squarePriceFrom'];
        }
        //По
        if(!empty($post['squarePriceTo'])){
            //Рассрочка - ищем по полю "Вартість 1 кв. м. (залишок) ГРН."
            if($post['payType'] == self::IS_RASSROCHKA_TYPE)
                $deals_filter['<=UF_CRM_1550227752'] = $post['squarePriceTo'];
            //Остальніе случаи - Вартість 1 кв. м. ГРН. (середня)
            else $deals_filter['<=UF_CRM_1550500088309'] = $post['squarePriceTo'];
        }

        //Перший внесок %
        //Від
        if(!empty($post['percentRedeemedFrom'])){
            //Рассрочка - ищем по полю "Перший внесок %"
            if($post['payType'] == self::IS_RASSROCHKA_TYPE)
                $deals_filter['>=UF_CRM_1550227684'] = $post['percentRedeemedFrom'];
            //Остальніе случаи - Перший внесок %
            //else $deals_filter['>=UF_CRM_1550227684'] = $post['percentRedeemedFrom'];
        }
        //По
        if(!empty($post['percentRedeemedTo'])){
            //Рассрочка - ищем по полю "Вартість 1 кв. м. (залишок) ГРН."
            if($post['payType'] == self::IS_RASSROCHKA_TYPE)
                $deals_filter['<=UF_CRM_1550227684'] = $post['percentRedeemedTo'];
            //Остальніе случаи - Вартість 1 кв. м. ГРН. (середня)
           // else $deals_filter['<=UF_CRM_1550227684'] = $post['percentRedeemedTo'];
        }

       /* //Кол-во платежей
        //До
        if(!empty($post['installmentNumberFrom'])){
            //Рассрочка - ищем по полю "Розстрочка - місяців"
            if($post['payType'] == self::IS_RASSROCHKA_TYPE)
                $deals_filter['>=UF_CRM_1550227956'] = $post['installmentNumberFrom'];

        }
        //По
        if(!empty($post['installmentNumberTo'])){
            //Рассрочка - ищем по полю "Розстрочка - місяців"
            if($post['payType'] == self::IS_RASSROCHKA_TYPE)
                $deals_filter['<=UF_CRM_1550227956'] = $post['installmentNumberTo'];
        }*/


        $deals_select = ['ID','TITLE','CATEGORY_ID','STAGE_ID','DATE_CREATE','CLOSEDATE','CLOSED',
            'UF_CRM_1550841222', //тип оплаті
            'TYPE_ID', //тип сделки (ЖК)
            'UF_CRM_1554977040', //Подарунки
            'UF_CRM_1550500088309', //Вартість 1 кв. м. ГРН. (середня)
            'UF_CRM_1550500214026', //Вартість 1 кв. м. $ (середня)
            'UF_CRM_1550227093', //Загальна площа кв. м.
            'UF_CRM_1550227867', //Сума договору ГРН.
            'UF_CRM_1550227540', //Сума договора $
            'UF_CRM_1550227956', //Розстрочка, місяців

            //для рассрочки
            'UF_CRM_1550227575', //Вартість 1 кв. м. ГРН. (перший внесок)
            'UF_CRM_1550227592', //Вартість 1 кв. м. $. (перший внесок)
            'UF_CRM_1550227752', //Вартість 1 кв. м. (залишок) ГРН.
            'UF_CRM_1550563272125', //Вартість 1 кв. м. (залишок) $.
            'UF_CRM_1550227695', //Викуплено кв. метрів
        ];
        $dealMassive = $this->getDealDataByFilter($deals_filter,$deals_select,$post['installmentNumberFrom'],$post['installmentNumberTo']);


        if(!$dealMassive) $result['error'] = 'Знайдено 0 угод!';
        else{
            $result['deals_massive'] = $dealMassive;

            $result['result'] = [];

            //извлекаем все подарки в массив (ид, название, кол-во)
            $presents = [];
            $presentsVariantsMassive = $this->getPropertyOptionsFromDeal(["USER_FIELD_NAME" => 'UF_CRM_1554977040']);
            if($presentsVariantsMassive)
                foreach ($presentsVariantsMassive as $present){
                    $presents[] = [
                        'ID' => $present['ID'],
                        'NAME' => $present['VALUE'],
                        'QUANTITY' => 0,
                    ];
                }


            //имена полей
            $thFields = [
                [
                    'ID' => '',
                    // 'NAME' => 'Порядковий номер',
                    'NAME' => 'П/н',
                    'FIELD_NAME' => 'NOMER_PO_PORYANKU',
                ],
                [
                    'ID' => 'TYPE_ID',
                    'NAME' => 'ТИП Угоди (ЖК)',
                    'FIELD_NAME' => 'TYPE_ID',
                ],
            ];

            //это общий массив данных 100% предоплата!!!
            if($post['payType'] == self::IS_FULL_PAYED_TYPE){

                $chosenFields = [
                  //  'TYPE_ID', //Название ЖК - этот метод не отдает
                    'UF_CRM_1550841222', // Тип оплати
                    'UF_CRM_1554977040', //Подарунки
                    'UF_CRM_1550500088309', //Вартість 1 кв. м. ГРН. (середня)
                    'UF_CRM_1550500214026', //Вартість 1 кв. м. $ (середня)
                    'UF_CRM_1550227093', //Загальна площа кв. м.
                    'UF_CRM_1550227867', //Сума договору ГРН.
                    'UF_CRM_1550227540', //Сума договора $

                ];
                foreach ($chosenFields as $value){
                    $fieldMassive = $this->getUserFieldName(['FIELD_NAME' => $value]);
                    if($fieldMassive)
                        if($fieldMassive['LIST_COLUMN_LABEL']['ua'])
                            $fieldName = $fieldMassive['LIST_COLUMN_LABEL']['ua'];
                        else{
                            if ($fieldMassive['LIST_COLUMN_LABEL']['ru'])
                                $fieldName = $fieldMassive['LIST_COLUMN_LABEL']['ru'];
                            else $fieldName = $fieldMassive['FIELD_NAME'];
                        }

                        $thFields[] = [
                            'FIELD_NAME' => $value,
                            'NAME' => $fieldName,
                            'ID' => $fieldMassive['ID'],
                           //'ALL' => $fieldMassive,
                        ];
                }

                $result['result']['TH_FIELDS'] = $thFields;
                $fildsData = $this->prePayment100($dealMassive,$presents);//ROWS_BY_ZHK
                if($fildsData){
                    $result['result']['TD_FIELDS'] = $fildsData['ROWS_BY_ZHK'];
                    $result['result']['WHOLE_ROW'] = $fildsData['WHOLE_ROW'];
                }

            }


            //это для рассрочки
            if($post['payType'] == self::IS_RASSROCHKA_TYPE){

                $chosenFields = [
                    //  'TYPE_ID', //Название ЖК - этот метод не отдает
                    'UF_CRM_1550841222', // Тип оплати
                    'UF_CRM_1554977040', //Подарунки

                    'UF_CRM_1550227575', //Вартість 1 кв. м. ГРН. (перший внесок)
                    'UF_CRM_1550227592', //Вартість 1 кв. м. $. (перший внесок)

                    'UF_CRM_1550227752', //Вартість 1 кв. м. (залишок) ГРН.
                    'UF_CRM_1550563272125', //Вартість 1 кв. м. (залишок) $.

                    'UF_CRM_1550500088309', //Вартість 1 кв. м. ГРН. (середня)
                    'UF_CRM_1550500214026', //Вартість 1 кв. м. $ (середня)
                    'UF_CRM_1550227093', //Загальна площа кв. м.

                    'UF_CRM_1550227695', //Викуплено кв. метрів

                    'UF_CRM_1550227867', //Сума договору ГРН.
                    'UF_CRM_1550227540', //Сума договора $
                ];
                foreach ($chosenFields as $value){
                    $fieldMassive = $this->getUserFieldName(['FIELD_NAME' => $value]);
                    if($fieldMassive)
                        if($fieldMassive['LIST_COLUMN_LABEL']['ua'])
                            $fieldName = $fieldMassive['LIST_COLUMN_LABEL']['ua'];
                        else{
                            if ($fieldMassive['LIST_COLUMN_LABEL']['ru'])
                                $fieldName = $fieldMassive['LIST_COLUMN_LABEL']['ru'];
                            else $fieldName = $fieldMassive['FIELD_NAME'];
                        }

                    $thFields[] = [
                        'FIELD_NAME' => $value,
                        'NAME' => $fieldName,
                        'ID' => $fieldMassive['ID'],
                        //'ALL' => $fieldMassive,
                    ];
                }

                $result['result']['TH_FIELDS'] = $thFields;
                $fildsData = $this->rassrochkaPayment($dealMassive,$presents);//ROWS_BY_ZHK
                if($fildsData){
                    $result['result']['TD_FIELDS'] = $fildsData['ROWS_BY_ZHK'];
                    $result['result']['WHOLE_ROW'] = $fildsData['WHOLE_ROW'];
                }

            }

        }




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

    //получение сделок специалиста по фильтру и указанным к выдаче полям
    private function getDealDataByFilter($arFilter,$arSelect,$payOutNumberFrom = false,$payOutNumberTo = false){
        $deals = [];
        $db_list = CCrmDeal::GetListEx(Array("ID" => "ASC"), $arFilter, false, false, $arSelect, array()); //получение пользовательских полей сделки по ID
        while($ar_result = $db_list->GetNext()){

            if($payOutNumberFrom || $payOutNumberTo){
                $paymentsNumberMassive = $this->getPropertyOptionsFromDeal(["ID" => $ar_result['UF_CRM_1550227956']]);
                if($paymentsNumberMassive){
                    if($payOutNumberFrom){
                        if($payOutNumberFrom > $paymentsNumberMassive[0]['VALUE']) continue;
                    }
                    if($payOutNumberTo){
                        if($payOutNumberTo < $paymentsNumberMassive[0]['VALUE']) continue;
                    }
                }
            }

            //формируем ссылку для открытия во фрейме сделки
            $ar_result['HREF'] = '/crm/deal/details/'.$ar_result['ID'].'/';
            $deals[] = $ar_result;
        }
        return $deals;
    }

    //получение имени поля
    private function getUserFieldName($filter){
        $res = false;
        $rsEnum = CUserTypeEntity::GetList(['ID' => 'DESC'], $filter);
        if($arEnum = $rsEnum->GetNext())
            $res = CUserTypeEntity::GetByID($arEnum['ID']);
        return $res;
    }

    //метод для 100% предоплаті - поля свои
    private function prePayment100($dealMassive,$presentsMassive){
        $result = [];

        $data = [];
        foreach ($dealMassive as $index => $dealFields){

            // это запрос значения поля Тип оплати по ID значения
            $payTypeMassive = $this->getPropertyOptionsFromDeal(["ID" => $dealFields['UF_CRM_1550841222']]);
            ($payTypeMassive) ? $payType = $payTypeMassive[0]['VALUE'] : $payType = '';

            $zhkMassive = $this->getReferenceBook(['ENTITY_ID' => self::DEAL_TYPE_REF_DIR, 'STATUS_ID' => $dealFields['TYPE_ID']]);
            if($zhkMassive){
                $data[$dealFields['TYPE_ID']] = [
                    'ZHK_NAME' => $zhkMassive[0]['NAME'],
                    'ZHK_ID' => $dealFields['TYPE_ID'],
                    'PAY_TYPE' => $payType,
                    'PRESENTS' => $presentsMassive,
                    'AVARGE_1M_SQU_UAH_ARR' => [],
                    'AVARGE_1M_SQU_UAH' => 0,
                    'AVARGE_1M_SQU_USD_ARR' => [],
                    'AVARGE_1M_SQU_USD' => 0,
                    'WHOLE_SQU_M' => 0,
                    'CONTRACT_SUM_UAH' => 0,
                    'CONTRACT_SUM_USD' => 0,
                    'DEALS_MASSIVE' => [],
                ];
            }
        }

        //цикл для рассчета цифр
        foreach ($dealMassive as $i => $dealFields){

            //в єту переменную фигачим уже нужніе поля по каждой сделке (без валюті) с ключем по сделке
            $customDeals = [];

            $avarge1mSquUah = explode('|',$dealFields['UF_CRM_1550500088309']);
            $avarge1mSquUSD = explode('|',$dealFields['UF_CRM_1550500214026']);
            $contractSumUah = explode('|',$dealFields['UF_CRM_1550227867']);
            $contractSumUsd = explode('|',$dealFields['UF_CRM_1550227540']);

         //   $avarge1mUah = [];
           // $avarge1mUsd = [];

            foreach ($data as $zhkId => $zhkFields){
                if($dealFields['TYPE_ID'] == $zhkId){

                    $customDeals[$dealFields['ID']]['TITLE'] = $dealFields['TITLE'];
                    $customDeals[$dealFields['ID']]['ID'] = $dealFields['ID'];
                    $customDeals[$dealFields['ID']]['PAY_TYPE'] = $payType;

                    if($avarge1mSquUah[1] == 'UAH'){
                        $customDeals[$dealFields['ID']]['M_SQU_UAH'] = $avarge1mSquUah[0];
                        $data[$zhkId]['AVARGE_1M_SQU_UAH_ARR'][] = $avarge1mSquUah[0];
                    }

                    if($avarge1mSquUSD[1] == 'USD'){
                        // $data[$zhkId]['AVARGE_1M_SQU_USD'] += $avarge1mSquUSD[0];
                     //   array_push($avarge1mUsd, $avarge1mSquUSD[0]);
                        $customDeals[$dealFields['ID']]['M_SQU_USD'] = $avarge1mSquUSD[0];
                        $data[$zhkId]['AVARGE_1M_SQU_USD_ARR'][] = $avarge1mSquUSD[0];
                    }

                    if($dealFields['UF_CRM_1550227093']) {
                        $customDeals[$dealFields['ID']]['SQU_M'] = $dealFields['UF_CRM_1550227093'];
                        $data[$zhkId]['WHOLE_SQU_M'] += $dealFields['UF_CRM_1550227093'];
                    }
                    if($contractSumUah[1] == 'UAH') {
                        $customDeals[$dealFields['ID']]['CONTRACT_SUM_UAH'] = round($contractSumUah[0],2);
                        $data[$zhkId]['CONTRACT_SUM_UAH'] += round($contractSumUah[0],2);
                    }
                    if($contractSumUsd[1] == 'USD') {
                        $customDeals[$dealFields['ID']]['CONTRACT_SUM_USD'] = round($contractSumUsd[0],2);
                        $data[$zhkId]['CONTRACT_SUM_USD'] += round($contractSumUsd[0],2);
                    }

                    //подарки
                    foreach ($zhkFields['PRESENTS'] as $k => $presentFields){
                        if(in_array($presentFields['ID'], $dealFields['UF_CRM_1554977040'])){
                            $customDeals[$dealFields['ID']]['PRESENTS'][] = [
                                'NAME' => $presentFields['NAME'],
                                'ID' => $presentFields['ID'],
                            ];
                            $data[$zhkId]['PRESENTS'][$k]['QUANTITY'] += 1;
                        }
                    }

                    //засовываме сделки в массив ЖК
                    $data[$zhkId]['DEALS_MASSIVE'][] = $customDeals[$dealFields['ID']];
                }
            }

            //массив ИТОГО
            $wholeValues = [
                'ZHK_NUMBERS' => count($data),
                'WHOLE_AVARGE_1M_SQU_UAH_ARR' => [],
                'WHOLE_AVARGE_1M_SQU_UAH' => 0,
                'WHOLE_AVARGE_1M_SQU_USD_ARR' => [],
                'WHOLE_AVARGE_1M_SQU_USD' => 0,
                'WHOLE_WHOLE_SQU_M' => 0,
                'WHOLE_CONTRACT_SUM_UAH' => 0,
                'WHOLE_CONTRACT_SUM_USD' => 0,
            ];

            //считаем средние значения по м. кв. в грн. и у.е. каждый ЖК
            foreach ($data as $zhkId => $zhkFields){
                $data[$zhkId]['AVARGE_1M_SQU_UAH'] = round(array_sum($zhkFields['AVARGE_1M_SQU_UAH_ARR']) / count($zhkFields['AVARGE_1M_SQU_UAH_ARR']),2);
                $data[$zhkId]['AVARGE_1M_SQU_USD'] = round(array_sum($zhkFields['AVARGE_1M_SQU_USD_ARR']) / count($zhkFields['AVARGE_1M_SQU_USD_ARR']),2);
                $data[$zhkId]['WHOLE_SQU_M'] = round($data[$zhkId]['WHOLE_SQU_M'],2);
                $data[$zhkId]['CONTRACT_SUM_UAH'] = round($data[$zhkId]['CONTRACT_SUM_UAH'],2);
                $data[$zhkId]['CONTRACT_SUM_USD'] = round($data[$zhkId]['CONTRACT_SUM_USD'],2);

                $wholeValues['WHOLE_AVARGE_1M_SQU_UAH_ARR'][] = $data[$zhkId]['AVARGE_1M_SQU_UAH'];
                $wholeValues['WHOLE_AVARGE_1M_SQU_USD_ARR'][] = $data[$zhkId]['AVARGE_1M_SQU_USD'];


                $wholeValues['WHOLE_WHOLE_SQU_M'] += round($data[$zhkId]['WHOLE_SQU_M'],2);
                $wholeValues['WHOLE_CONTRACT_SUM_UAH'] += $data[$zhkId]['CONTRACT_SUM_UAH'];
                $wholeValues['WHOLE_CONTRACT_SUM_USD'] += $data[$zhkId]['CONTRACT_SUM_USD'];

            }

            $wholeValues['WHOLE_AVARGE_1M_SQU_UAH'] = round(array_sum($wholeValues['WHOLE_AVARGE_1M_SQU_UAH_ARR']) / count($wholeValues['WHOLE_AVARGE_1M_SQU_UAH_ARR']),2);
            $wholeValues['WHOLE_AVARGE_1M_SQU_USD'] = round(array_sum($wholeValues['WHOLE_AVARGE_1M_SQU_USD_ARR']) / count($wholeValues['WHOLE_AVARGE_1M_SQU_USD_ARR']),2);


            $wholeValues['WHOLE_CONTRACT_SUM_UAH'] = round($wholeValues['WHOLE_CONTRACT_SUM_UAH'],2);
            $wholeValues['WHOLE_CONTRACT_SUM_USD'] = round($wholeValues['WHOLE_CONTRACT_SUM_USD'],2);

            //извекаем значения без ключей
            $result = [
                'ROWS_BY_ZHK' => array_values($data),
                'WHOLE_ROW' => $wholeValues,
            ];
        }
        return $result;
    }

    //метод для рассрочки - поля свои
    private function rassrochkaPayment($dealMassive,$presentsMassive){
        $result = false;
        $data = [];
        foreach ($dealMassive as $index => $dealFields){

            // это запрос значения поля Тип оплати по ID значения
            $payTypeMassive = $this->getPropertyOptionsFromDeal(["ID" => $dealFields['UF_CRM_1550841222']]);
            ($payTypeMassive) ? $payType = $payTypeMassive[0]['VALUE'] : $payType = '';

            $zhkMassive = $this->getReferenceBook(['ENTITY_ID' => self::DEAL_TYPE_REF_DIR, 'STATUS_ID' => $dealFields['TYPE_ID']]);
            if($zhkMassive){
                $data[$dealFields['TYPE_ID']] = [
                    'ZHK_NAME' => $zhkMassive[0]['NAME'],
                    'ZHK_ID' => $dealFields['TYPE_ID'],
                    'PAY_TYPE' => $payType,
                    'PRESENTS' => $presentsMassive,

                    //єти новіе
                    'AVARGE_1M_SQU_FIRST_PAYMENT_UAH_ARR' => [],
                    'AVARGE_1M_SQU_FIRST_PAYMENT_UAH' => 0,
                    'AVARGE_1M_SQU_FIRST_PAYMENT_USD_ARR' => [],
                    'AVARGE_1M_SQU_FIRST_PAYMENT_USD' => 0,

                    //ети новіе
                    'AVARGE_1M_SQU_REST_UAH_ARR' => [],
                    'AVARGE_1M_SQU_REST_UAH' => 0,
                    'AVARGE_1M_SQU_REST_USD_ARR' => [],
                    'AVARGE_1M_SQU_REST_USD' => 0,


                    'AVARGE_1M_SQU_UAH_ARR' => [],
                    'AVARGE_1M_SQU_UAH' => 0,
                    'AVARGE_1M_SQU_USD_ARR' => [],
                    'AVARGE_1M_SQU_USD' => 0,
                    'WHOLE_SQU_M' => 0,

                    //єто тоже новій
                    'WHOLE_SQU_M_REDEEMED' => 0,


                    'CONTRACT_SUM_UAH' => 0,
                    'CONTRACT_SUM_USD' => 0,
                    'DEALS_MASSIVE' => [],
                ];
            }
        }

        //цикл для рассчета цифр
        foreach ($dealMassive as $i => $dealFields) {

            //в єту переменную фигачим уже нужніе поля по каждой сделке (без валюті) с ключем по сделке
            $customDeals = [];

            $avarge1mSquFirstPaymentUah = explode('|', $dealFields['UF_CRM_1550227575']);
            $avarge1mSquFirstPaymentUsd = explode('|', $dealFields['UF_CRM_1550227592']);

            $avarge1mSquRestUah = explode('|', $dealFields['UF_CRM_1550227752']);
            $avarge1mSquRestUsd = explode('|', $dealFields['UF_CRM_1550563272125']);

            $avarge1mSquUah = explode('|', $dealFields['UF_CRM_1550500088309']);
            $avarge1mSquUSD = explode('|', $dealFields['UF_CRM_1550500214026']);
            $contractSumUah = explode('|', $dealFields['UF_CRM_1550227867']);
            $contractSumUsd = explode('|', $dealFields['UF_CRM_1550227540']);

            foreach ($data as $zhkId => $zhkFields){
                if($dealFields['TYPE_ID'] == $zhkId){

                    $customDeals[$dealFields['ID']]['TITLE'] = $dealFields['TITLE'];
                    $customDeals[$dealFields['ID']]['ID'] = $dealFields['ID'];
                    $customDeals[$dealFields['ID']]['PAY_TYPE'] = $payType;

                    if($avarge1mSquFirstPaymentUah[1] == 'UAH'){
                        $customDeals[$dealFields['ID']]['M_SQU_FIRST_PAYMENT_UAH'] = $avarge1mSquFirstPaymentUah[0];
                        $data[$zhkId]['AVARGE_1M_SQU_FIRST_PAYMENT_UAH_ARR'][] = $avarge1mSquFirstPaymentUah[0];
                    }


                    if($avarge1mSquFirstPaymentUsd[1] == 'USD'){
                        $customDeals[$dealFields['ID']]['M_SQU_FIRST_PAYMENT_USD'] = $avarge1mSquFirstPaymentUsd[0];
                        $data[$zhkId]['AVARGE_1M_SQU_FIRST_PAYMENT_USD_ARR'][] = $avarge1mSquFirstPaymentUsd[0];
                    }


                    if($avarge1mSquRestUah[1] == 'UAH'){
                        $customDeals[$dealFields['ID']]['M_SQU_REST_UAH'] = $avarge1mSquRestUah[0];
                        $data[$zhkId]['AVARGE_1M_SQU_REST_UAH_ARR'][] = $avarge1mSquRestUah[0];
                    }


                    if($avarge1mSquRestUsd[1] == 'USD') {
                        $customDeals[$dealFields['ID']]['M_SQU_REST_USD'] = $avarge1mSquRestUsd[0];
                        $data[$zhkId]['AVARGE_1M_SQU_REST_USD_ARR'][] = $avarge1mSquRestUsd[0];
                    }




                    if($avarge1mSquUah[1] == 'UAH') {
                        $customDeals[$dealFields['ID']]['M_SQU_UAH'] = $avarge1mSquUah[0];
                        $data[$zhkId]['AVARGE_1M_SQU_UAH_ARR'][] = $avarge1mSquUah[0];
                    }

                    if($avarge1mSquUSD[1] == 'USD') {
                        $customDeals[$dealFields['ID']]['M_SQU_USD'] = $avarge1mSquUSD[0];
                        $data[$zhkId]['AVARGE_1M_SQU_USD_ARR'][] = $avarge1mSquUSD[0];
                    }



                    if($dealFields['UF_CRM_1550227093']) {
                        $customDeals[$dealFields['ID']]['SQU_M'] = $dealFields['UF_CRM_1550227093'];
                        $data[$zhkId]['WHOLE_SQU_M'] += $dealFields['UF_CRM_1550227093'];
                    }



                    if($dealFields['UF_CRM_1550227695']) {
                        $customDeals[$dealFields['ID']]['SQU_M_REDEEMED'] = $dealFields['UF_CRM_1550227695'];
                        $data[$zhkId]['WHOLE_SQU_M_REDEEMED'] += $dealFields['UF_CRM_1550227695'];
                    }



                    if($contractSumUah[1] == 'UAH') {
                        $customDeals[$dealFields['ID']]['CONTRACT_SUM_UAH'] = round($contractSumUah[0],2);
                        $data[$zhkId]['CONTRACT_SUM_UAH'] += round($contractSumUah[0],2);
                    }
                    if($contractSumUsd[1] == 'USD') {
                        $customDeals[$dealFields['ID']]['CONTRACT_SUM_USD'] = round($contractSumUsd[0],2);
                        $data[$zhkId]['CONTRACT_SUM_USD'] += round($contractSumUsd[0],2);
                    }

                    //подарки
                    foreach ($zhkFields['PRESENTS'] as $k => $presentFields){
                        if(in_array($presentFields['ID'], $dealFields['UF_CRM_1554977040'])){
                            $customDeals[$dealFields['ID']]['PRESENTS'][] = [
                                'NAME' => $presentFields['NAME'],
                                'ID' => $presentFields['ID'],
                            ];
                            $data[$zhkId]['PRESENTS'][$k]['QUANTITY'] += 1;
                        }
                    }

                    //засовываме сделки в массив ЖК
                    $data[$zhkId]['DEALS_MASSIVE'][] = $customDeals[$dealFields['ID']];
                }
            }

            //массив ИТОГО
            $wholeValues = [
                'ZHK_NUMBERS' => count($data),

                'WHOLE_AVARGE_1M_SQU_FIRST_PAYMENT_UAH_ARR' => [],
                'WHOLE_AVARGE_1M_SQU_FIRST_PAYMENT_UAH' => 0,
                'WHOLE_AVARGE_1M_SQU_FIRST_PAYMENT_USD_ARR' => [],
                'WHOLE_AVARGE_1M_SQU_FIRST_PAYMENT_USD' => 0,

                //ети новіе
                'WHOLE_AVARGE_1M_SQU_REST_UAH_ARR' => [],
                'WHOLE_AVARGE_1M_SQU_REST_UAH' => 0,
                'WHOLE_AVARGE_1M_SQU_REST_USD_ARR' => [],
                'WHOLE_AVARGE_1M_SQU_REST_USD' => 0,

                'WHOLE_AVARGE_1M_SQU_UAH_ARR' => [],
                'WHOLE_AVARGE_1M_SQU_UAH' => 0,
                'WHOLE_AVARGE_1M_SQU_USD_ARR' => [],
                'WHOLE_AVARGE_1M_SQU_USD' => 0,
                'WHOLE_WHOLE_SQU_M' => 0,

                //ето новое
                'WHOLE_WHOLE_SQU_M_REDEEMED' => 0,

                'WHOLE_CONTRACT_SUM_UAH' => 0,
                'WHOLE_CONTRACT_SUM_USD' => 0,
            ];

            //считаем средние значения по м. кв. в грн. и у.е. каждый ЖК
            foreach ($data as $zhkId => $zhkFields){
                $data[$zhkId]['AVARGE_1M_SQU_FIRST_PAYMENT_UAH'] = round(array_sum($zhkFields['AVARGE_1M_SQU_FIRST_PAYMENT_UAH_ARR']) / count($zhkFields['AVARGE_1M_SQU_FIRST_PAYMENT_UAH_ARR']),2);
                $data[$zhkId]['AVARGE_1M_SQU_FIRST_PAYMENT_USD'] = round(array_sum($zhkFields['AVARGE_1M_SQU_FIRST_PAYMENT_USD_ARR']) / count($zhkFields['AVARGE_1M_SQU_FIRST_PAYMENT_USD_ARR']),2);

                $data[$zhkId]['AVARGE_1M_SQU_REST_UAH'] = round(array_sum($zhkFields['AVARGE_1M_SQU_REST_UAH_ARR']) / count($zhkFields['AVARGE_1M_SQU_REST_UAH_ARR']),2);
                $data[$zhkId]['AVARGE_1M_SQU_REST_USD'] = round(array_sum($zhkFields['AVARGE_1M_SQU_REST_USD_ARR']) / count($zhkFields['AVARGE_1M_SQU_REST_USD_ARR']),2);


                $data[$zhkId]['AVARGE_1M_SQU_UAH'] = round(array_sum($zhkFields['AVARGE_1M_SQU_UAH_ARR']) / count($zhkFields['AVARGE_1M_SQU_UAH_ARR']),2);
                $data[$zhkId]['AVARGE_1M_SQU_USD'] = round(array_sum($zhkFields['AVARGE_1M_SQU_USD_ARR']) / count($zhkFields['AVARGE_1M_SQU_USD_ARR']),2);
                $data[$zhkId]['WHOLE_SQU_M'] = round($data[$zhkId]['WHOLE_SQU_M'],2);

                $data[$zhkId]['WHOLE_SQU_M_REDEEMED'] = round($data[$zhkId]['WHOLE_SQU_M_REDEEMED'],2);

                $data[$zhkId]['CONTRACT_SUM_UAH'] = round($data[$zhkId]['CONTRACT_SUM_UAH'],2);
                $data[$zhkId]['CONTRACT_SUM_USD'] = round($data[$zhkId]['CONTRACT_SUM_USD'],2);


                $wholeValues['WHOLE_AVARGE_1M_SQU_FIRST_PAYMENT_UAH_ARR'][] = $data[$zhkId]['AVARGE_1M_SQU_FIRST_PAYMENT_UAH'];
                $wholeValues['WHOLE_AVARGE_1M_SQU_FIRST_PAYMENT_USD_ARR'][] = $data[$zhkId]['AVARGE_1M_SQU_FIRST_PAYMENT_USD'];

                $wholeValues['WHOLE_AVARGE_1M_SQU_REST_UAH_ARR'][] = $data[$zhkId]['AVARGE_1M_SQU_REST_UAH'];
                $wholeValues['WHOLE_AVARGE_1M_SQU_REST_USD_ARR'][] = $data[$zhkId]['AVARGE_1M_SQU_REST_USD'];


                $wholeValues['WHOLE_AVARGE_1M_SQU_UAH_ARR'][] = $data[$zhkId]['AVARGE_1M_SQU_UAH'];
                $wholeValues['WHOLE_AVARGE_1M_SQU_USD_ARR'][] = $data[$zhkId]['AVARGE_1M_SQU_USD'];


                $wholeValues['WHOLE_WHOLE_SQU_M'] += round($data[$zhkId]['WHOLE_SQU_M'],2);


                $wholeValues['WHOLE_WHOLE_SQU_M_REDEEMED'] += round($data[$zhkId]['WHOLE_SQU_M_REDEEMED'],2);


                $wholeValues['WHOLE_CONTRACT_SUM_UAH'] += $data[$zhkId]['CONTRACT_SUM_UAH'];
                $wholeValues['WHOLE_CONTRACT_SUM_USD'] += $data[$zhkId]['CONTRACT_SUM_USD'];

            }


            $wholeValues['WHOLE_AVARGE_1M_SQU_FIRST_PAYMENT_UAH'] = round(array_sum($wholeValues['WHOLE_AVARGE_1M_SQU_FIRST_PAYMENT_UAH_ARR']) / count($wholeValues['WHOLE_AVARGE_1M_SQU_FIRST_PAYMENT_UAH_ARR']),2);
            $wholeValues['WHOLE_AVARGE_1M_SQU_FIRST_PAYMENT_USD'] = round(array_sum($wholeValues['WHOLE_AVARGE_1M_SQU_FIRST_PAYMENT_USD_ARR']) / count($wholeValues['WHOLE_AVARGE_1M_SQU_FIRST_PAYMENT_USD_ARR']),2);


            $wholeValues['WHOLE_AVARGE_1M_SQU_REST_UAH'] = round(array_sum($wholeValues['WHOLE_AVARGE_1M_SQU_REST_UAH_ARR']) / count($wholeValues['WHOLE_AVARGE_1M_SQU_REST_UAH_ARR']),2);
            $wholeValues['WHOLE_AVARGE_1M_SQU_REST_USD'] = round(array_sum($wholeValues['WHOLE_AVARGE_1M_SQU_REST_USD_ARR']) / count($wholeValues['WHOLE_AVARGE_1M_SQU_REST_USD_ARR']),2);

            $wholeValues['WHOLE_AVARGE_1M_SQU_UAH'] = round(array_sum($wholeValues['WHOLE_AVARGE_1M_SQU_UAH_ARR']) / count($wholeValues['WHOLE_AVARGE_1M_SQU_UAH_ARR']),2);
            $wholeValues['WHOLE_AVARGE_1M_SQU_USD'] = round(array_sum($wholeValues['WHOLE_AVARGE_1M_SQU_USD_ARR']) / count($wholeValues['WHOLE_AVARGE_1M_SQU_USD_ARR']),2);

            $wholeValues['WHOLE_CONTRACT_SUM_UAH'] = round($wholeValues['WHOLE_CONTRACT_SUM_UAH'],2);
            $wholeValues['WHOLE_CONTRACT_SUM_USD'] = round($wholeValues['WHOLE_CONTRACT_SUM_USD'],2);
            $wholeValues['WHOLE_WHOLE_SQU_M'] = round($wholeValues['WHOLE_WHOLE_SQU_M'],2);
            $wholeValues['WHOLE_WHOLE_SQU_M_REDEEMED'] = round($wholeValues['WHOLE_WHOLE_SQU_M_REDEEMED'],2);


            //извекаем значения без ключей
            $result = [
                'ROWS_BY_ZHK' => array_values($data),
                'WHOLE_ROW' => $wholeValues,
            ];

        }


        return $result;
    }

    //24.08.2019 Получение сделко по конкретному ЖК, те же поля, только отобр. по каждой сделке
    public function getDealsByCurrentZhk($post){
        $result = ['result' => false, 'error' => false];

        //массив сделок по фильтру (направление, дата с, дата по)
        $deals_filter = [
            'CATEGORY_ID' => $post['category'],
            ">=BEGINDATE" => date('d.m.Y', strtotime($post['dateFrom'])), //date('m.Y',strtotime('-1 month'))
            "<=BEGINDATE" => date('d.m.Y', strtotime($post['dateTo'])), //date('m.Y',strtotime('-1 month'))
        ];

        //Тип оплаты
        if($post['payType']) $deals_filter['UF_CRM_1550841222'] = $post['payType'];

        //Если выбрана галка "Учитывать закрытые сделки", добавляем в фильтр
        if($post['onlyOpenedDeals'] == 'true') $deals_filter['CLOSED'] = 'N'; //ВОЗВРАЩАЕТ TRUE/FALSE в ВИДЕ СТРОКИ

        //Стадии
        if($post['currentStageId']) $deals_filter['STAGE_ID'] = $post['currentStageId'];

        //Тип угоди (ЖК) - Вставляем ИД конкретного ЖК
        if($post['dealType']) $deals_filter['TYPE_ID'] = $post['currentZHKid'];

        //Подарунки
        if($post['presents']) $deals_filter['UF_CRM_1554977040'] = $post['presents'];

        //Вартість кв.м.
        //Від
        if(!empty($post['squarePriceFrom'])){
            //Рассрочка - ищем по полю "Вартість 1 кв. м. (залишок) ГРН."
            if($post['payType'] == self::IS_RASSROCHKA_TYPE)
                $deals_filter['>=UF_CRM_1550227752'] = $post['squarePriceFrom'];
            //Остальніе случаи - Вартість 1 кв. м. ГРН. (середня)
            else $deals_filter['>=UF_CRM_1550500088309'] = $post['squarePriceFrom'];
        }
        //По
        if(!empty($post['squarePriceTo'])){
            //Рассрочка - ищем по полю "Вартість 1 кв. м. (залишок) ГРН."
            if($post['payType'] == self::IS_RASSROCHKA_TYPE)
                $deals_filter['<=UF_CRM_1550227752'] = $post['squarePriceTo'];
            //Остальніе случаи - Вартість 1 кв. м. ГРН. (середня)
            else $deals_filter['<=UF_CRM_1550500088309'] = $post['squarePriceTo'];
        }

        //Перший внесок %
        //Від
        if(!empty($post['percentRedeemedFrom'])){
            //Рассрочка - ищем по полю "Перший внесок %"
            if($post['payType'] == self::IS_RASSROCHKA_TYPE)
                $deals_filter['>=UF_CRM_1550227684'] = $post['percentRedeemedFrom'];
        }
        //По
        if(!empty($post['percentRedeemedTo'])){
            //Рассрочка - ищем по полю "Вартість 1 кв. м. (залишок) ГРН."
            if($post['payType'] == self::IS_RASSROCHKA_TYPE)
                $deals_filter['<=UF_CRM_1550227684'] = $post['percentRedeemedTo'];
        }


        $deals_select = ['ID','TITLE','CATEGORY_ID','STAGE_ID','DATE_CREATE','CLOSEDATE','CLOSED',
            'UF_CRM_1550841222', //тип оплаті
            'TYPE_ID', //тип сделки (ЖК)
            'UF_CRM_1554977040', //Подарунки
            'UF_CRM_1550500088309', //Вартість 1 кв. м. ГРН. (середня)
            'UF_CRM_1550500214026', //Вартість 1 кв. м. $ (середня)
            'UF_CRM_1550227093', //Загальна площа кв. м.
            'UF_CRM_1550227867', //Сума договору ГРН.
            'UF_CRM_1550227540', //Сума договора $
            'UF_CRM_1550227956', //Розстрочка, місяців

            //для рассрочки
            'UF_CRM_1550227575', //Вартість 1 кв. м. ГРН. (перший внесок)
            'UF_CRM_1550227592', //Вартість 1 кв. м. $. (перший внесок)
            'UF_CRM_1550227752', //Вартість 1 кв. м. (залишок) ГРН.
            'UF_CRM_1550563272125', //Вартість 1 кв. м. (залишок) $.
            'UF_CRM_1550227695', //Викуплено кв. метрів
        ];
        $dealMassive = $this->getDealDataByFilter($deals_filter,$deals_select,$post['installmentNumberFrom'],$post['installmentNumberTo']);
        $result['result'] = $dealMassive;

        $this->sentAnswer($result);
    }


}