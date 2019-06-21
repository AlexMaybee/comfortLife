<?php

CModule::IncludeModule("CRM");
CModule::IncludeModule("tasks");
CModule::IncludeModule("im");

class RassrochkaRestInfo{

    const AllowedInCategories = [0,1]; //разрешено только в этих направлениях
    const IBLOCK_31 = 31; //ID списка элементов с рассрочками

    public function getDealDataOnOpeningDeal($dealId){
        $result = [
            'DEAL' => false,
            'ERROR' => false,
        ];

        $dealFilter = ['ID' => $dealId];
        $dealSelect = ['ID','TITLE','STAGE_ID','CATEGORY_ID','ASSIGNED_BY_ID',
            'UF_CRM_1550841222',
            'UF_CRM_1550227867','UF_CRM_1550227540',
            'UF_CRM_1550227609','UF_CRM_1550227631',
            'UF_CRM_1550227712','UF_CRM_1550227726',
            ]; //Поля опроса слева направо = сделка -> сверху вниз
        $dealDataResult = $this->getDealDataByFilter($dealFilter,$dealSelect);

        if(
            $dealDataResult
            && in_array($dealDataResult[0]['CATEGORY_ID'],self::AllowedInCategories)
            && $dealDataResult[0]['UF_CRM_1550841222'] == 90
        ) {

            $result['DEAL'] = $dealDataResult[0];

            //получаем все элементы расссрочки и считаем суммы в оплачено и Не оплачено
            $allElemDataFilter = ['PROPERTY_108' => $dealDataResult[0]['ID'],'IBLOCK_ID' => self::IBLOCK_31];
            $allElemDataSelect = ["ID","IBLOCK_ID","NAME","PROPERTY_*"];
            $allElemDataResult = $this->getListElementsAdnPropsByFilter($allElemDataFilter,$allElemDataSelect);
            if($allElemDataResult){

                //$result['ELEMS'] = $allElemDataResult;

                $result['DEAL']['RASSROCHKA_WHOLE_SUM'] = explode('|',$dealDataResult[0]['UF_CRM_1550227712'])[0]; //сумма рассрочки
                $result['DEAL']['PAYED'] = ''; //оплаченные
                $result['DEAL']['UNPAYED'] = ''; //НЕ оплаченные
                $result['DEAL']['PAY_EXPIRED'] = ''; //НЕ оплаченные и просроченные
                foreach ($allElemDataResult as $element){

                    $sumUah = explode('|',$element['PROPERTIES']['SUMA_PLATEJU_UAH']['VALUE']);

                    if($element['PROPERTIES']['STATUS']['VALUE_ENUM_ID'] == 79) {
                        if($sumUah[1] == 'UAH') $result['DEAL']['PAYED'] += $sumUah[0];
                    }
                    else{
                    //    $sumUah = explode('|',$element['PROPERTIES']['SUMA_PLATEJU_UAH']['VALUE']);
                        if($sumUah[1] == 'UAH') {
                            $result['DEAL']['UNPAYED'] += $sumUah[0];
                            if($element['PROPERTIES']['DATA_PLATEJU']['VALUE'] && (strtotime($element['PROPERTIES']['DATA_PLATEJU']['VALUE']) < strtotime('now')))
                                $result['DEAL']['PAY_EXPIRED'] += $sumUah[0];
                        }
                    }
                }
            }
        }


        $this->sentAnswer($result);
    }

    //ответ в консоль
    private function sentAnswer($answ){
        echo json_encode($answ);
    }

    //Получение данных сделки по фильтру
    private function getDealDataByFilter($filter,$select){
        $result = [];
        $db_list = CCrmDeal::GetListEx(array('ID' => 'DESC'), $filter, false, false, $select, array()); //получение пользовательских полей сделки по ID
        while ($dealsList = $db_list->Fetch()) {
            $result[] = $dealsList;
        }
        return $result;
    }

    //получение списка элементов по фильтру - свойства - каждое отдельным массивом
    private function getListElementsAdnPropsByFilter($arFilter,$arSelect){
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

}