<?php



class RassrochkaWatcher{

    const IBLOCK_311 = 31; //ID списка элементов с рассрочками
    const NOT_PAYED = 80;
    const Rassrochka_Watchers_Group = 17;
    const DeadLine_Date = 20; //дедлайн для задачи с просрочкой - 20е число месяца
    const DeadLine_Time = '18:00:00'; //дедлайн для задачи с просрочкой, время

    public function __construct(){

        //$filter = ['IBLOCK_ID' => self::IBLOCK_311, 'PROPERTY_110' => self::NOT_PAYED, '<PROPERTY_113' => date('Y-m-d')];

        //ЗАМЕНИТЬ ЭТОТ ФИЛЬТР НА ВЕРХНИЙ ПОСЛЕ ОКОНЧАНИЯ ИМИ РАССТАНОВКИ ПЛАТЕЖЕЙ
        $filter = ['IBLOCK_ID' => self::IBLOCK_311, 'PROPERTY_110' => self::NOT_PAYED, 'PROPERTY_108' => 1675, '<PROPERTY_113' => date('Y-m-d')];

        $select = ["ID","IBLOCK_ID","NAME","PROPERTY_*"];
        $elementResult = $this->getListElementsAndPropsByFilter($filter,$select);
        if($elementResult){

            //получение ID всех смотрящих за просрочкой
            $watchersIds = $this->getUsersFromGroup(self::Rassrochka_Watchers_Group);
            if($watchersIds){
                //доп. параметры
                $deadLine = date(self::DeadLine_Date.'.m.Y '.self::DeadLine_Time);
                $responsible = array_shift($watchersIds);
                $otherResponsibles = $watchersIds;

                foreach ($elementResult as $element){

                    //привязка к сделке + контакты + компании (если есть)
                    $contacts = [
                        'D_'.$element['PROPERTIES']['UGODA']['VALUE'],
                    ];
                    if($element['PROPERTIES']['KLIYENT']['VALUE'])
                        foreach ($element['PROPERTIES']['KLIYENT']['VALUE'] as $client){
                            $contacts[] = $client;
                        }

                    $newTaskFields = [
                        "TITLE" => "Звязатись з клієнтом та вияснити причину прострочки платежу за договором ".$element['FIELDS']['NAME'],
                        "DESCRIPTION" => 'Звязатись з клієнтом та вияснити причину прострочки платежу по '.'<a href="/crm/deal/details/'.$element['PROPERTIES']['UGODA']['VALUE'].'/">угоді</a>',
                        "RESPONSIBLE_ID" => $responsible,//Ответственные
                        "ACCOMPLICES" => $otherResponsibles,//Ответственные
                  //      "CREATED_BY" => 1, //от имени нашего аккаунта
                        "PRIORITY" => 2, // 2 соответствует высокому приоритету
                        "UF_CRM_TASK" => $contacts,
                        "DEADLINE" => $deadLine,
                        "UF_AUTO_318646232817" => $element['FIELDS']['ID'] // Запись в него ID элемента рассрочки
                    ];

                    //Создаем задачу!
                    $taskCreateResult = $this->createTask($newTaskFields);
                }
            }

           // echo '<pre>';
            //echo 'Дата окончания: '.$deadLine.'<br>';
           // print_r($elementResult);

          //  echo 'Всего неоплачено платежей: '.count($elementResult).'<br>';
           // print_r($watchersIds);
            //print_r($newTaskFields);
            //print_r($taskCreateResult);

           // foreach ($watchersIds as $id) echo $id.'<br>';
        }
    }

    //получение списка элементов по фильтру - свойства - каждое отдельным массивом
    private function getListElementsAndPropsByFilter($arFilter,$arSelect){
        $result = [];
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

    //получение id сотрудников группы по ее ID
    private function getUsersFromGroup($group_id){
        $filter = ['GROUPS_ID' => $group_id];
        $rsUsers = CUser::GetList(($by="ID"),($order="asc"),$filter);
        while($arItem = $rsUsers->GetNext())
        {
            $users[] = $arItem['ID'];
        }
        return $users;
    }

    //создание задачи
    private function createTask($fields){
        $result = [
            'RESULT' => false,
            'ERROR' => false,
        ];

        $obTask = new CTasks;
        $taskID = $obTask->Add($fields); // ID новой задачи

        if($taskID > 0){
            $result['RESULT'] = $taskID;
        }
        else{
            global $APPLICATION;
            if($e = $APPLICATION->GetException()){
                $result['RESULT'] = $e->GetString();
            }
        }
        return $result;
    }

}