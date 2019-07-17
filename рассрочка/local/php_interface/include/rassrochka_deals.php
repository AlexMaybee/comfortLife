<?

//содание элемента списка
AddEventHandler("iblock", "OnBeforeIBlockElementAdd", Array("RassrochkaDealEvents", "beforeIBlockElementAddFunction"));

//изменение элемента списка
AddEventHandler("iblock", "OnBeforeIBlockElementUpdate", Array("RassrochkaDealEvents", "beforeIBlockElementUpdateFunction"));


//Событие при обновленнии сделки - переход на конкретные стадии 2-х направлений "договора" Киев и Ирпень
AddEventHandler("crm", "OnBeforeCrmDealUpdate", Array("RassrochkaDealEvents", "createRassrochkaListElementsForDeal"));



//sms test
//AddEventHandler("crm", "OnActivityAdd", Array("RassrochkaDealEvents", "testEventLog"));//в массиве только измененные поля


class RassrochkaDealEvents extends CustomFunctions{

    const IBLOCK_31 = 31; //ID списка элементов с рассрочками
    const IBLOCK_33 = 33; //ID списка элементов с курсами
    const ALLOVED_ON_STAGES = ['EXECUTING','C1:EXECUTING']; //Стадии, на которіх срабатівают собітия создания єл. рассрочки, !!!заменить на EXECUTING и C1:EXECUTING!!!
    const ALLOVED_ON_CATEGORIES = ['0','1']; //Стадии, на которіх срабатівают собітия создания єл. рассрочки, !!!заменить на EXECUTING и C1:EXECUTING!!!
    const KURS_DIFFERENCE = 1.06; //результат деления курса НБУ на курс из договора, при єто значении уже применяется перерасчет

    //test SMS LOG
    public function testEventLog(&$arFields){
         self::logData('EventSmsEdit.log',[$arFields]);
    }



    public function createRassrochkaListElementsForDeal(&$arFields){
        $dealData = 0;
        $errors = [];

        if(isset($arFields['STAGE_ID']) && in_array($arFields['STAGE_ID'], self::ALLOVED_ON_STAGES) && $arFields['ID'] > 0){

            //сначала проверяем, чтобы элементы рассрочки уже не были созданы!
            $checkElemFilter = ['IBLOCK_ID' => self::IBLOCK_31, 'PROPERTY_108' => $arFields['ID']];
            $checkElemSelect = ['ID'];
            $checkElemResult = self::getListElementsByFilter($checkElemFilter,$checkElemSelect);
            if(!$checkElemResult){

                //получаем данные сделки
                $dealDataFilter = ['ID' => $arFields['ID']];
                $dealDataSelect = ['ID','CATEGORY_ID','STAGE_ID','CONTACT_ID','COMPANY_ID','TYPE_ID',
                    'UF_CRM_1550240174','UF_CRM_1550841222',
                    'UF_CRM_1550227867','UF_CRM_1550227540', //сумма договора грн + у.е.
                    'UF_CRM_1550227609','UF_CRM_1550227631', //первый взнос грн + у.е.
                    'UF_CRM_1550227712','UF_CRM_1550227726', //сумма рассрочки грн + у.е.
                    'UF_CRM_1550227879','UF_CRM_1550227956', //дата начала рассрочки + кол-во месяцев/платежей
                ];
                $dealData = self::getOneDealData($dealDataFilter,$dealDataSelect);

                if(!$dealData) $errors[] = 'Не існує угод з ID = '.$arFields['ID'];
                else{

                    if((isset($arFields['UF_CRM_1550841222']) && $arFields['UF_CRM_1550841222'] == 90) || $dealData['UF_CRM_1550841222'] == 90){

                        //Проверка ошибок - заполнение полей при конкретных условиях

                        //1. Контакт или Компания
                        if(
                            (
                                (isset($arFields['CONTACT_IDS']) && !$arFields['CONTACT_IDS'] && !$dealData['CONTACT_ID']) || (!isset($arFields['CONTACT_IDS']) && !$dealData['CONTACT_ID'])
                            )
                            &&
                            (
                                (isset($arFields['COMPANY_ID']) && !$arFields['COMPANY_ID'] && !$dealData['COMPANY_ID']) || (!isset($arFields['COMPANY_ID']) && !$dealData['COMPANY_ID'])
                            )
                        ) $errors[] = 'Оберіть Контакт (клієнта) або Компанію!';

                      //  if(isset($arFields['CONTACT_IDS']) && !$arFields['CONTACT_IDS'] && empty($dealData['CONTACT_ID'])) $errors[] = 'Сработал контакт при переходе - Запрет!';

                        //2. Номер договора - Стоит проверка от Битрикса, єто страховка
                        if(
                            (isset($arFields['UF_CRM_1550240174']) && !$arFields['UF_CRM_1550240174'] && !$dealData['UF_CRM_1550240174'])
                        ||
                            (!isset($arFields['UF_CRM_1550240174']) && !$dealData['UF_CRM_1550240174'])
                        ) $errors[] = 'Внесіть номер договору!';

                        //3. ЖК - нельзя просто не вібрать!
                        if((isset($arFields['TYPE_ID']) && !$arFields['TYPE_ID'] && !$dealData['TYPE_ID']) || (!isset($arFields['TYPE_ID']) && !$dealData['TYPE_ID']))
                            $errors[] = 'Оберіть Житловий комплекс!!';

                        //4. Сумма по договору в грн и в у.е.
                        if(
                            (
                                (isset($arFields['UF_CRM_1550227867']) && !$arFields['UF_CRM_1550227867'] && !$dealData['UF_CRM_1550227867']) || (!isset($arFields['UF_CRM_1550227867']) && !$dealData['UF_CRM_1550227867'])
                            )
                            ||
                            (
                                (isset($arFields['UF_CRM_1550227540']) && !$arFields['UF_CRM_1550227540'] && !$dealData['UF_CRM_1550227540']) || (!isset($arFields['UF_CRM_1550227540']) && !$dealData['UF_CRM_1550227540'])
                            )
                        ) $errors[] = 'Не внесено суму договору у ГРН. або у $';


                        //5.  Первій взнос - НЕ НУЖНО??? - Уточнить!!!
                        if(
                            (
                                (isset($arFields['UF_CRM_1550227609']) && !$arFields['UF_CRM_1550227609'] && !$dealData['UF_CRM_1550227609']) || (!isset($arFields['UF_CRM_1550227609']) && !$dealData['UF_CRM_1550227609'])
                            )
                            ||
                            (
                                (isset($arFields['UF_CRM_1550227631']) && !$arFields['UF_CRM_1550227631'] && !$dealData['UF_CRM_1550227631']) || (!isset($arFields['UF_CRM_1550227631']) && !$dealData['UF_CRM_1550227631'])
                            )
                        ) $errors[] = 'Не вказано суму першого внеску у ГРН. або у $';

                        //6. Сумма рассрочки
                        if(
                            (
                                (isset($arFields['UF_CRM_1550227712']) && !$arFields['UF_CRM_1550227712'] && !$dealData['UF_CRM_1550227712']) || (!isset($arFields['UF_CRM_1550227712']) && !$dealData['UF_CRM_1550227712'])
                            )
                            ||
                            (
                                (isset($arFields['UF_CRM_1550227726']) && !$arFields['UF_CRM_1550227726'] && !$dealData['UF_CRM_1550227726']) || (!isset($arFields['UF_CRM_1550227726']) && !$dealData['UF_CRM_1550227726'])
                            )
                        ) $errors[] = 'Не вказано суму розстрочки у ГРН. або у $';

                        //7. Дата начала рассрочки
                        if((isset($arFields['UF_CRM_1550227879']) && !$arFields['UF_CRM_1550227879'] && !$dealData['UF_CRM_1550227879']) || (!isset($arFields['UF_CRM_1550227879']) && !$dealData['UF_CRM_1550227879']))
                            $errors[] = 'Оберіть дату початку рострочки!';

                        //8. Кол-во месяцев (платежей) рассрочки
                        if((isset($arFields['UF_CRM_1550227956']) && !$arFields['UF_CRM_1550227956'] && !$dealData['UF_CRM_1550227956']) || (!isset($arFields['UF_CRM_1550227956']) && !$dealData['UF_CRM_1550227956']))
                            $errors[] = 'Оберіть кількість місяців (платежів) рострочки!';


                        //Если нет ошибок, переходим к созданию элементов списка = кол-ву платежей
                        if(!$errors){
                           // self::logData('SuccessDealUpdate.log',[$arFields]);

                            $newDataMassive = [
                                'DEAL_ID' => $arFields['ID'],
                                'CONTACTS' => [],
                                'CONTRACT' => '',
                                'RASSROCHKA_SUM_UAH' => $dealData['UF_CRM_1550227712'],
                             //   'RASSROCHKA_SUM_USD' => $dealData['UF_CRM_1550227726'],
                                'START_DATE' => $dealData['UF_CRM_1550227879'],
                                'ZHK_NAME' => '',
                                'PAYMENTS_NUM' => '',
                            ];
                            $contract_name = '';
                            if(isset($arFields['UF_CRM_1550240174']) && $arFields['UF_CRM_1550240174'] && !$dealData['UF_CRM_1550240174']) $newDataMassive['CONTRACT'] = $arFields['UF_CRM_1550240174'];
                            if($dealData['UF_CRM_1550240174']) $newDataMassive['CONTRACT'] = $dealData['UF_CRM_1550240174'];

                            if($dealData['CONTACT_ID'] && $dealData['CONTACT_ID'] != $arFields['CONTACT_ID']) $newDataMassive['CONTACTS'][] = 'C_'.$dealData['CONTACT_ID'];
                            if(isset($arFields['CONTACT_ID']) && $arFields['CONTACT_ID']) $newDataMassive['CONTACTS'][] = 'C_'.$arFields['CONTACT_ID'];
                            if(isset($arFields['CONTACT_IDS']) && $arFields['CONTACT_IDS'])
                                foreach ($arFields['CONTACT_IDS'] as $contact) {
                                    if(in_array($contact,[$arFields['CONTACT_ID'],$dealData['CONTACT_ID']])) continue;
                                    else $newDataMassive['CONTACTS'][] = 'C_'.$contact;
                                }

                            if(isset($arFields['COMPANY_ID']) && $arFields['COMPANY_ID']) $newDataMassive['CONTACTS'][] = 'CO_'.$arFields['COMPANY_ID'];
                            if(!isset($arFields['COMPANY_ID']) && $dealData['COMPANY_ID']) $newDataMassive['CONTACTS'][] = 'CO_'.$dealData['COMPANY_ID'];

                            //сумма рассрочки, UAH
                          //  if($dealData['UF_CRM_1550227712']) $newDataMassive['RASSROCHKA_SUM_UAH'] = $dealData['UF_CRM_1550227712'];
                            if(isset($arFields['UF_CRM_1550227712']) && $arFields['UF_CRM_1550227712'])
                                $newDataMassive['RASSROCHKA_SUM_UAH'] = $arFields['UF_CRM_1550227712'];

                            //сумма рассрочки, USD
                          //  if($dealData['UF_CRM_1550227726']) $newDataMassive['RASSROCHKA_SUM_USD'] = $dealData['UF_CRM_1550227726'];
//                            if(isset($arFields['UF_CRM_1550227726']) && $arFields['UF_CRM_1550227726'])
//                                $newDataMassive['RASSROCHKA_SUM_USD'] = $arFields['UF_CRM_1550227726'];

                            //дата старта
                            if(isset($arFields['UF_CRM_1550227879']) && $arFields['UF_CRM_1550227879']) $newDataMassive['START_DATE'] = $arFields['UF_CRM_1550227879'];

                            //название ЖК, перевод из числа и формат из HTML в текст
                            $zhkName = $dealData['TYPE_ID'];
                            if(isset($arFields['TYPE_ID']) && $arFields['TYPE_ID']) $zhkName = $arFields['TYPE_ID'];
                            if($zhkName){
                                $refFilter = ['ENTITY_ID' => 'DEAL_TYPE', 'STATUS_ID' => $zhkName];
                                $refResult = self::getReferenceBook($refFilter);
                                if($refResult) $newDataMassive['ZHK_NAME'] = HTMLToTxt($refResult['NAME']); //перевод из HTML в текст
                            }

                            //кол-во платежей (месяцев), перевод из числа
                            $paymentsNumId = '';
                            if($dealData['UF_CRM_1550227956']) $paymentsNumId = $dealData['UF_CRM_1550227956'];
                            if(isset($arFields['UF_CRM_1550227956']) && $arFields['UF_CRM_1550227956']) $paymentsNumId = $arFields['UF_CRM_1550227956'];
                            if($paymentsNumId) $newDataMassive['PAYMENTS_NUM'] = self::convertSelectValIdToValue($paymentsNumId);


                            //запуск создания єл-в расрочки !!!только если кол-во платежей > 0
                            if($newDataMassive['PAYMENTS_NUM'] > 0) {
                                $rassrochkaElemsCreateResult = self::createRassrochkaElements($newDataMassive);
                                if($rassrochkaElemsCreateResult) {

                                    //вывод ошибок при создании нов. элементов списков
                                    foreach ($rassrochkaElemsCreateResult as $res){
                                        if(!$res['result']) $errors[] = $res['error'];
                                    }

                                }
                            }
                        }
                    }
                }
            }

            //self::logData('BeforeDealUpdate.log',[$newDataMassive,$rassrochkaElemsCreateResult,$errors]);
        }

        //чисто для теста
      //  else self::logData('Alt_BeforeDealUpdate.log',[$arFields]);

        if($errors){
            $err = '';
            foreach ($errors as $error){
                $err .= $error."\n";
            }
            $arFields['RESULT_MESSAGE'] = $err;
            return false;
        }
        else return true;


    }

    //26.06 пересчет при создании элемента со статусом "сплачено"
    public function beforeIBlockElementAddFunction(&$arFields){
        $errors = [];
        $test = 0;
        $biggerPayment = false;
        $equalPayment = false;
        $unPayedElemsArr = []; //текущий месяц для расстановки платежей и сумм
        $siblingElementsResult = [];
        $sumUahArr = false;
        $sumUsdArr = false;
        $payDate = false;
        $deal_id = false;
        $dealData = [];

       // self::logData('Errors.log',[$arFields]);


        if($arFields['IBLOCK_ID'] == self::IBLOCK_31){

            //поле "Клиент" - не должно біть пустім
            if($arFields['PROPERTY_VALUES']['107']){
                foreach ($arFields['PROPERTY_VALUES']['107'] as $value){
                    if(!$value) $errors[] = 'Поле "Клієнт" обов\'язкове зо заповнення!';
                }
            }

            //поле "Житловий комплекс" - не должно біть пустім
            if($arFields['PROPERTY_VALUES']['109']){
                foreach ($arFields['PROPERTY_VALUES']['109'] as $value){
                    if(!$value['VALUE']) $errors[] = 'Поле "Житловий комплекс" обов\'язкове зо заповнення!';
                }
            }


            //поле Сума платежу, UAH" - не должно біть пустім - ПЕРЕДЕЛАТЬ НА ЧИСЛО!!!
            if($arFields['PROPERTY_VALUES']['111']){
                foreach ($arFields['PROPERTY_VALUES']['111'] as $value){
                    if(!$value['VALUE']) $errors[] = 'Внесіть суму у поле "Сума платежу, UAH"!';
                    else{
                        $sumUahArr = explode('|',$value['VALUE']);
                        if($sumUahArr[1] != 'UAH') $errors[] = 'Сума у полі "Сума платежу, UAH" має бути у гривнях!';
                    }
                }
            }

            //поле "Сума платежу, USD" - не должно біть пустім
            if(!$arFields['PROPERTY_VALUES']['122']){
              //  $errors[] = 'Заповніть суму у полі "Сума платежу, USD"!';
            }
            else{
                foreach ($arFields['PROPERTY_VALUES']['122'] as $value){
                    //Делать перерасчет по курсу в зависимости от...
                    if(!$value) $errors[] = 'Сума у полі "Сума платежу, USD" має бути більше 0!';
                }
            }

            //поле "Угода" - не должно біть пустім
            if($arFields['PROPERTY_VALUES']['108']){
                foreach ($arFields['PROPERTY_VALUES']['108'] as $value){
                    if(!$value['VALUE']) $errors[] = 'Поле "Угода" обов\'язкове зо заповнення!';
                    else $deal_id = $value['VALUE'];
                }
                if($deal_id){
                    $dealDataFilter = ['ID' => $deal_id];
                    $dealDataSelect = ['ID', 'CATEGORY_ID', 'STAGE_ID', 'UF_CRM_1550841222', //рассрочка
                        'UF_CRM_1550227712', 'UF_CRM_1550227726', //сумма рассрочки грн + у.е.
                        'UF_CRM_1550227879', 'UF_CRM_1550227956', //дата начала рассрочки + кол-во месяцев/платежей
                        'UF_CRM_1550227830', //курс НБУ
                        'UF_CRM_1550227609','UF_CRM_1550227631', //первые взносы грн. и usdl
                        'UF_CRM_1550227830', //курс доллара в сделке
                    ];
                    $dealData = self::getOneDealData($dealDataFilter, $dealDataSelect);
                    if($dealData){
                        if(!in_array($dealData['CATEGORY_ID'],self::ALLOVED_ON_CATEGORIES)) $errors[] = 'Угода не належить до напрямків, на яких дозволено розстрочку!';
                        elseif($dealData['UF_CRM_1550841222'] != 90) $errors[] = 'В угоді у полі "Тип оплати" НЕ вибрано "розстрочка"!';
                        elseif(!$dealData['UF_CRM_1550227712']) $errors[] = 'В угоді не вказано суму розстрочки у ГРН!';
                        elseif(!$dealData['UF_CRM_1550227726']) $errors[] = 'В угоді не вказано суму розстрочки у USD!';
                        else{
                            $siblingElementsFilter = [
                                'IBLOCK_ID' => self::IBLOCK_31,
                                'PROPERTY_108' => $dealData['ID'], ///PROPERY_108 - ID сделки
                            ];
                            $siblingElementsSelect = ['ID','NAME','IBLOCK_ID','PROPERTY_*',"TIMESTAMP_X"];
                            $siblingElementsResult = self::getListElementsAdnPropsByFilter($siblingElementsFilter,$siblingElementsSelect);

                            if(!$siblingElementsResult){
                                //рассрочек нет, т.е. берем сумму всей рассрочки и сравниваем с суммой текущего платеже
                                //если сумма платежа < суммы рассрочки, то  выдаем ошибку
                                $rassrochkaSumArr = explode('|',$dealData['UF_CRM_1550227712']);

                                /*if($sumUahArr[0] && ($sumUahArr[0] < $rassrochkaSumArr[0])){
                                    $errors[] = 'Сума єдиного платежу ('.$sumUahArr[0].' грн.) не може бути менше суми розтрочки ('.$rassrochkaSumArr[0].' грн.) !';
                                }*/

                            }
                        }

                    }
                }

            }


            //поле "Дата платежу" - не должно біть пустім - ЧИСЛО!!!
            if($arFields['PROPERTY_VALUES']['113']){
                foreach ($arFields['PROPERTY_VALUES']['113'] as $value){
                    if(!$value['VALUE']) $errors[] = 'Поле "Дата платежу" обов\'язкове зо заповнення!';
                    else $payDate = $value['VALUE'];
                }
            }

            //поле "Статус" - не должно біть пустім
            if(!$arFields['PROPERTY_VALUES']['110']){
                $errors[] = 'Оберіть значення у полі "Статус"!';
            }
            else{

                //выдает ошибку при гупповом создании элементов при смене стадии сделки
                /*if($payDate){
                    if(strtotime('today') < strtotime($payDate)){
                        $errors[] = 'Вказана дата '.$payDate.' у полі "Дата платежу" ще не настала!';
                    }
                }*/
            }


            //вывод ошибок
            if($errors){
                $text = '';
                foreach ($errors as $error){
                    $text .= $error."\n";
                }

                global $APPLICATION;
                $APPLICATION->throwException($text);
                return false;
            }
            else{
                //если нет ошибок и статус == оплачено -> пересчет неоплаченніх платежей
                if($arFields['PROPERTY_VALUES']['110'] == 79){
                    //запрет создания єлемента с будущей датой
                    if($payDate){
                        $kursUsdRes = self::getKursFromListOrSite($payDate);
                        if ($kursUsdRes)
                            $arFields['PROPERTY_VALUES']['115'] = $kursUsdRes;
                    }


                    if($dealData){
                        //запрос всех єлементов рассрочки по сделке


                        if($siblingElementsResult){

                            if($sumUahArr){

                                //17.07.2019 Пересчет суммы грн. по курсу НБУ на вібранній день в отдельное поле USD
                                if($kursUsdRes){
                                    $arFields['PROPERTY_VALUES']['122'] = round($sumUahArr[0] / $kursUsdRes,2);

                                    // - Делим курс нбу на курс нбу в сделке, если сумма >= 1.06, тогда отмечаем пересчет в 2х полях!
                                    if($dealData['UF_CRM_1550227830'] && $kursUsdRes){
                                        if(($kursUsdRes / $dealData['UF_CRM_1550227830']) >= self::KURS_DIFFERENCE){
                                            $arFields['PROPERTY_VALUES']['124'] = 6642; //Да
                                            $arFields['PROPERTY_VALUES']['125'] =
                                                'Різниця поточного курсу НБУ ('.$kursUsdRes.') та на момент оформлення договору ('
                                                .$dealData['UF_CRM_1550227830'].') дорівнює '.(($kursUsdRes / $dealData['UF_CRM_1550227830'] - 1) * 100).' %';
                                        }
                                        else{
                                            $arFields['PROPERTY_VALUES']['124'] = 6643; //Нет
                                            $arFields['PROPERTY_VALUES']['125'] = '';
                                        }
                                    }

                                }

                                $rassrochkaSumArr = explode('|',$dealData['UF_CRM_1550227712']);

                                $payedWholeSum = $sumUahArr[0];
                                $unPayedWholeSum = $rassrochkaSumArr[0] - $sumUahArr[0];
                                //считаем оплаченные и неоплаченные элементы
                                foreach ($siblingElementsResult as $element){
                                    $pSumUahArr = explode('|',$element['PROPERTIES']['SUMA_PLATEJU_UAH']['VALUE']);
                                    if($pSumUahArr[1] == 'UAH'){
                                        if($element['PROPERTIES']['STATUS']['VALUE_ENUM_ID'] == 79){
                                            $payedWholeSum += $pSumUahArr[0];
                                            $unPayedWholeSum -= $pSumUahArr[0];
                                        }
                                        else{
                                            $unPayedElemsArr[] = $element['FIELDS']['ID'];
                                        }
                                    }
                                }


                                if(count($unPayedElemsArr) > 0){
                                    $remainder = $unPayedWholeSum % count($unPayedElemsArr);
                                    //делим платежи как я делал єто при создании єлементов!
                                    if($remainder > 0){
                                        $biggerPayment = (($unPayedWholeSum - $remainder) / count($unPayedElemsArr) + $remainder).'|'.$sumUahArr[1];
                                        $equalPayment = ($unPayedWholeSum - $remainder) / count($unPayedElemsArr).'|'.$sumUahArr[1];
                                    }
                                    //или все делим на равніе части
                                    else $equalPayment = ($unPayedWholeSum / count($unPayedElemsArr)).'|'.$sumUahArr[1];

                                    //обновляем поля сумм неоплаченных элементов
                                    foreach ($unPayedElemsArr as $unpayedElem){
                                        if($biggerPayment){
                                            if($unPayedElemsArr[0] == $unpayedElem) {
                                                $unPayedWholeSum > 0 ? $updElementsFields['111'] = $biggerPayment : $updElementsFields['111'] = '0.00|'.$sumUahArr[1];
                                            }
                                            else{
                                                //  $updElementsFields['111'] = $equalPayment;
                                                $unPayedWholeSum > 0 ? $updElementsFields['111'] = $equalPayment : $updElementsFields['111'] = '0.00|'.$sumUahArr[1];
                                            }
                                        }
                                        elseif (!$biggerPayment && $equalPayment) {
                                            //$updElementsFields['111'] = $equalPayment;
                                            $unPayedWholeSum > 0 ? $updElementsFields['111'] = $equalPayment : $updElementsFields['111'] = '0.00|'.$sumUahArr[1];
                                        }
                                        $updateUpnayAll[] = $updElementsFields;
                                        /*обновляем суммы всех неоплаченных эл-в рассрочки*/
                                        $updateUpnayedElemsRes[] = self::updatePropertiesInListElement1($unpayedElem,self::IBLOCK_31,$updElementsFields);

                                    }

                                }




                            }

                        }

                    }


                }
            }

            //self::logData('2606ListElemBeforeCreate.log',[$siblingElementsResult,$kursUsdRes,$arFields]);
          //  self::logData('2607ListElemBeforeCreate.log',['Сумма рассрочки: '.$rassrochkaSumArr[0],'Оплачено: '.$payedWholeSum,'Не оплачено: '.$unPayedWholeSum,$unPayedElemsArr]);
          //  self::logData('2608ListElemBeforeCreate.log',[$updateUpnayAll,'Больший платеж: '.$biggerPayment,'Равній: '.$equalPayment,$updateUpnayedElemsRes]);
          //  self::logData('2608ListElemBeforeCreate.log',[$sumUahArr,$rassrochkaSumArr,$dealData]);

        }
    }


    public function beforeIBlockElementUpdateFunction(&$arFields){
        $errors = [];
        $biggerPayment = false;
        $equalPayment = false;
        $unPayedElemsArr = []; //текущий месяц для расстановки платежей и сумм
        $payDate = false;
        $kursUsdRes = 0; //курс доллара НБУ на конкретную дату


        if($arFields['IBLOCK_ID'] == self::IBLOCK_31){
            //1. Получаем массив элементов, кот. прикреплены к этой же сделке
            if($arFields['ID']> 0){
                //получаем старые данные элемента для сравнения
                $oldElemDataFilter = ['ID' => $arFields['ID'],'IBLOCK_ID' => self::IBLOCK_31];
                $oldElemDataSelect = ["ID","IBLOCK_ID","NAME","PROPERTY_*"];
                $oldElemDataResult = self::getListElementsAdnPropsByFilter($oldElemDataFilter,$oldElemDataSelect);
                if($oldElemDataResult){

                    //запрашиваем все данные всех элементов, которые привязаны к той же сделке
                    $siblingElementsFilter = [
                        'IBLOCK_ID' => self::IBLOCK_31,
                        'PROPERTY_108' => $oldElemDataResult[0]['PROPERTIES']['UGODA']['VALUE'], ///PROPERY_108 - ID сделки
                        '!ID' => $oldElemDataResult[0]['FIELDS']['ID']
                    ];
                    $siblingElementsSelect = ['ID','NAME','IBLOCK_ID','PROPERTY_*',"TIMESTAMP_X"];
                    $siblingElementsResult = self::getListElementsAdnPropsByFilter($siblingElementsFilter,$siblingElementsSelect);

                    if($siblingElementsResult){
                        //массив для обновления полей родственных элементов списка
                        global $USER;

                        $updElementsFields = [];

                    //теперь сравниваем измененные поля в элементе и добавляем в $updElementsFields

                        //привязка к контактам/компаниям
                       // if($arFields['PROPERTY_VALUES']['107'] != $oldElemDataResult[0]['PROPERTIES']['KLIYENT']['VALUE']) $updElementsFields['107'] = $arFields['PROPERTY_VALUES']['107'];
                        //24.06 Запрет смены конткатов - НЕ СОГЛАСНОВАНО!
                        if($arFields['PROPERTY_VALUES']['107'] != $oldElemDataResult[0]['PROPERTIES']['KLIYENT']['VALUE']) $errors[] = 'Не можна змінити контакт в існуючому елементі!';

                        //привязка к сделке
                        if($arFields['PROPERTY_VALUES']['108']){
                            $deal_id = '';
                            foreach ($arFields['PROPERTY_VALUES']['108'] as $value) $deal_id = $value['VALUE'];
                       //     if($deal_id && ($deal_id != $oldElemDataResult[0]['PROPERTIES']['UGODA']['VALUE'])) $updElementsFields['108'] = $deal_id;
                            if($deal_id && ($deal_id != $oldElemDataResult[0]['PROPERTIES']['UGODA']['VALUE'])) $errors[] = 'Не можна змінити угоду в існуючому елементі!';
                        }

                        //Название ЖК
                        if($arFields['PROPERTY_VALUES']['109']){
                            $zhkName = '';
                            foreach ($arFields['PROPERTY_VALUES']['109'] as $value) $zhkName = $value['VALUE'];
                            //if($zhkName && ($zhkName != HTMLToTxt($oldElemDataResult[0]['PROPERTIES']['ZHYTLOVYY_KOMPLEKS']['VALUE']))) $updElementsFields['109'] = $zhkName;
                            if($zhkName && ($zhkName != HTMLToTxt($oldElemDataResult[0]['PROPERTIES']['ZHYTLOVYY_KOMPLEKS']['VALUE']))) $errors[] = 'Не можна змінити ЖК в існуючому елементі!';
                        }

                        if($arFields['PROPERTY_VALUES']['113']){
                            foreach ($arFields['PROPERTY_VALUES']['113'] as $value1){
                                if(empty($value1['VALUE'])) $errors[] = 'Оберіть дату прийому платежу!';
                                else $payDate = $value1['VALUE'];
                            }
                        }

                        //сравнение новой суммы и сохраненной ранее
                        if($arFields['PROPERTY_VALUES']['110'] == 79){ //Если в $arFields вібрано "Сплачено"
                            //Сравнение нового статуса с сохраненным ранее, было "Не оплачено", а смена идет на "Оплачено"

                            //сохраняем курс $ на дату платежа
                            if($payDate){ //ПРОВЕРИТЬ РАБОТУ ПРИ ИЗМЕНЕНИИ!!!
                                $kursUsdRes = self::getKursFromListOrSite($payDate);
                                if ($kursUsdRes) $arFields['PROPERTY_VALUES']['115'] = $kursUsdRes;
                            }


                            //от сравнения статусов ушел - Сумма будет проверяться всегда, если статус == оплачено
                            // if($arFields['PROPERTY_VALUES']['110'] != $oldElemDataResult[0]['PROPERTIES']['STATUS']['VALUE_ENUM_ID']){
                          //  $newPaymentSum = ['СТАТУСЫ_NEW' => $arFields['PROPERTY_VALUES']['110']. ' - '.$oldElemDataResult[0]['PROPERTIES']['STATUS']['VALUE_ENUM_ID']];

                            //сравниваем сумму сохраненную и новую
                            $newUahSum = '';
                            foreach ($arFields['PROPERTY_VALUES']['111'] as $value) $newUahSum = $value['VALUE'];

                            if($newUahSum){
                                $newPaymentSumArr = explode('|',$newUahSum);
                                if($newPaymentSumArr[1] != 'UAH') $errors[] = 'Сума платежу у грн. має бути з валютою UAH! ';
                                elseif (!$newPaymentSumArr[0]) $errors[] = 'Не вказано суму платежу або вона дорівнює 0!';
                                else{

                                    //17.07.2019 Пересчет суммы грн. по курсу НБУ на вібранній день в отдельное поле USD
                                    if($kursUsdRes){
                                        $arFields['PROPERTY_VALUES']['122'] = round($newPaymentSumArr[0] / $kursUsdRes,2);
                                    }

                                    //получение данніх сделки
                                    if($arFields['PROPERTY_VALUES']['108']) {
                                        $deal_id = false;
                                        foreach ($arFields['PROPERTY_VALUES']['108'] as $value) $deal_id = $value['VALUE'];
                                        if (!$deal_id) $errors[] = 'Оберіть угоду!';
                                        else {
                                            $dealDataFilter = ['ID' => $deal_id];
                                            $dealDataSelect = ['ID', 'CATEGORY_ID', 'STAGE_ID', 'UF_CRM_1550841222', //рассрочка
                                                'UF_CRM_1550227712', 'UF_CRM_1550227726', //сумма рассрочки грн + у.е.
                                                'UF_CRM_1550227879', 'UF_CRM_1550227956', //дата начала рассрочки + кол-во месяцев/платежей
                                                'UF_CRM_1550227830', //курс НБУ в сделке
                                            ];
                                            $dealData = self::getOneDealData($dealDataFilter, $dealDataSelect);
                                            if(!$dealData) $errors[] = 'Не знайдено угоду за ID = '.$deal_id.' з поля '.$oldElemDataResult[0]['PROPERTIES']['UGODA']['NAME'];
                                            else{

                                                //17.07.2019, этап 2 -
                                                // - Делим курс нбу на курс нбу в сделке, если сумма >= 1.06, тогда отмечаем пересчет в 2х полях!
                                                if($dealData['UF_CRM_1550227830'] && $kursUsdRes){
                                                    if(($kursUsdRes / $dealData['UF_CRM_1550227830']) >= self::KURS_DIFFERENCE){
                                                        $arFields['PROPERTY_VALUES']['124'] = 6642; //Да
                                                        $arFields['PROPERTY_VALUES']['125'] =
                                                            'Різниця поточного курсу НБУ ('.$kursUsdRes.') та на момент оформлення договору ('
                                                            .$dealData['UF_CRM_1550227830'].') дорівнює '.(($kursUsdRes / $dealData['UF_CRM_1550227830'] - 1) * 100).' %';
                                                    }
                                                    else{
                                                        $arFields['PROPERTY_VALUES']['124'] = 6643; //Нет
                                                        $arFields['PROPERTY_VALUES']['125'] = '';
                                                    }
                                                }


                                                if(!in_array($dealData['CATEGORY_ID'],self::ALLOVED_ON_CATEGORIES)) $errors[] = 'Угода не належить до напрямків, на яких дозволено розстрочку!';
                                                else{
                                                    if($dealData['UF_CRM_1550841222'] != 90) $errors[] = 'В угоді у полі "Тип оплати" НЕ вибрано "розстрочка"!';
                                                    else {
                                                        //Если что, поле с USD = UF_CRM_1550227540
                                                        if(!$dealData['UF_CRM_1550227712']) $errors[] = 'В угоді не вказано суму розстрочки!';
                                                        else{
                                                            //теперь ставниваем валюты!
                                                            $dealSumUahArr = explode('|',$dealData['UF_CRM_1550227712']);

                                                            //проверка суммы сделки
                                                            if(!$dealSumUahArr[0]) $errors[] = 'Сума угоди не вказана або дорівнює 0!';
                                                            elseif($dealSumUahArr[1] != 'UAH') $errors[] = 'Валюта суми рострочки в угоді має бути в UAH!';
                                                            else{
                                                                if($newPaymentSumArr[0] > $dealSumUahArr[0])
                                                                    $errors[] = 'Сума поточного платежу '.$newPaymentSumArr[0].' грн. перевищує загальну суму розтрочки '.$dealSumUahArr[0].' грн. !';
                                                                else {
                                                                    //теперь нужно вычесть из суммы рассчроки сделки сумму текущего платежа и во всех НЕ оплаченных
                                                                    //Вычисляем суммы платежей
                                                                    $unPayedRassrochaWholeSum = ($dealSumUahArr[0] - $newPaymentSumArr[0]);

                                                                    //рассчет оплаченной суммі, не оплаченной и получение id неоплаченных платежей
                                                                    $alreadyPayedSum = 0;
                                                                    foreach ($siblingElementsResult as $oneNeededElem){
                                                                        if($oneNeededElem['PROPERTIES']['STATUS']['VALUE_ENUM_ID'] == 79){
                                                                            $suma = explode('|',$oneNeededElem['PROPERTIES']['SUMA_PLATEJU_UAH']['VALUE']);
                                                                            $alreadyPayedSum += $suma[0];
                                                                            $unPayedRassrochaWholeSum -= $suma[0];
                                                                        }

                                                                        //берем все неоплаченніе и "не вібрано"
                                                                        if($oneNeededElem['PROPERTIES']['STATUS']['VALUE_ENUM_ID'] != 79)
                                                                            $unPayedElemsArr[] = $oneNeededElem['FIELDS']['ID'];
                                                                    }

                                                                    //здесь проверка суммі платежей, чтобі сумма оплаченных + текущий + неоплаченный = сумме рассрочки
                                                                    if(($alreadyPayedSum + $newPaymentSumArr[0] + $unPayedRassrochaWholeSum) < $dealSumUahArr[0])
                                                                        $errors[] = 'Суми платежу '.$newPaymentSumArr[0].' грн. не достатньо для закриття розстрочки. Вона має бути '
                                                                            .($newPaymentSumArr[0] + ($dealSumUahArr[0] - ($alreadyPayedSum + $newPaymentSumArr[0]))).' грн.!';
                                                                    elseif(($alreadyPayedSum + $newPaymentSumArr[0] + $unPayedRassrochaWholeSum) > $dealSumUahArr[0])
                                                                        $errors[] = 'Сума платежу '.$newPaymentSumArr[0].' грн. перевищує залишок по розстрочці. Вона має бути '
                                                                            .($dealSumUahArr[0] - $alreadyPayedSum).' грн.!';
                                                                    else{

                                                                        //пересчет, если сохраненная сумма платежа != новой сумме платежа
                                                                     //   if($newUahSum != $oldElemDataResult[0]['PROPERTIES']['SUMA_PLATEJU_UAH']['VALUE']){
                                                                            if(count($unPayedElemsArr) > 0){
                                                                                $remainder = $unPayedRassrochaWholeSum % count($unPayedElemsArr);
                                                                                //делим платежи как я делал єто при создании єлементов!
                                                                                if($remainder > 0){
                                                                                    $biggerPayment = (($unPayedRassrochaWholeSum - $remainder) / count($unPayedElemsArr) + $remainder).'|'.$newPaymentSumArr[1];
                                                                                    $equalPayment = ($unPayedRassrochaWholeSum - $remainder) / count($unPayedElemsArr).'|'.$newPaymentSumArr[1];
                                                                                }
                                                                                //или все делим на равніе части
                                                                                else $equalPayment = ($unPayedRassrochaWholeSum / count($unPayedElemsArr)).'|'.$newPaymentSumArr[1];
                                                                            }
                                                                            else{
                                                                                if(($alreadyPayedSum + $newPaymentSumArr[0]) < $dealSumUahArr[0]) $errors[] = 'Суми останнього платежу '.$newPaymentSumArr[0].' грн. не достатньо для закриття розстрочки. Вона має бути '.($newPaymentSumArr[0] + ($dealSumUahArr[0] - ($alreadyPayedSum + $newPaymentSumArr[0]))).' грн.!';
                                                                                //if(($alreadyPayedSum + $newPaymentSumArr[0]) > $dealSumUahArr[0]) $errors[] = 'Сума останнього платежу '.$newPaymentSumArr[0].' грн. перевищує залишок по розстрочці. Вона має бути '.($dealSumUahArr[0] - $alreadyPayedSum).' грн.!';
                                                                            }
                                                                      //  }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            // }
                        }
                        //блокируем смену суммі, если статус == "НЕ выбрано" или "Не оплачено"
                        else {
                            $newUahSum = '';
                            foreach ($arFields['PROPERTY_VALUES']['111'] as $value) $newUahSum = $value['VALUE'];
                            if($newUahSum != $oldElemDataResult[0]['PROPERTIES']['SUMA_PLATEJU_UAH']['VALUE']) $errors[] = 'Суму платежу можна змінити тільки при сплаті!';
                        }

                        //обновляем все элементы
                        $allElems = [];
                       // $updateOtherElems = ['NONONO!!!'];
                        if(($updElementsFields || $biggerPayment || $equalPayment) && !$errors){


                            //24.06.2019 - Добавил условие, чтобы не получать при досрочном платеже отриц. суммы в оставшихся
                            // и присваивать им 0.00|UAH
                            foreach ($siblingElementsResult as $key => $oneElem){
                                if($oneElem['PROPERTIES']['STATUS']['VALUE_ENUM_ID'] != 79){
                                    if($biggerPayment){
                                        if($unPayedElemsArr[0] == $oneElem['FIELDS']['ID']) {
                                            $unPayedRassrochaWholeSum > 0 ? $updElementsFields['111'] = $biggerPayment : $updElementsFields['111'] = '0.00|'.$newPaymentSumArr[1];
                                        }
                                        else{
                                          //  $updElementsFields['111'] = $equalPayment;
                                            $unPayedRassrochaWholeSum > 0 ? $updElementsFields['111'] = $equalPayment : $updElementsFields['111'] = '0.00|'.$newPaymentSumArr[1];
                                        }
                                    }
                                    elseif (!$biggerPayment && $equalPayment) {
                                        //$updElementsFields['111'] = $equalPayment;
                                        $unPayedRassrochaWholeSum > 0 ? $updElementsFields['111'] = $equalPayment : $updElementsFields['111'] = '0.00|'.$newPaymentSumArr[1];
                                    }
                                }
                                else $updElementsFields['111'] = $oneElem['PROPERTIES']['SUMA_PLATEJU_UAH']['VALUE'];

                               // $updElementsFields['113'] = $oneElem['PROPERTIES']['DATA_PLATEJU']['VALUE'];

                                $allElems[] = $updElementsFields;

                                $updateOtherElems[] = self::updatePropertiesInListElement1($oneElem['FIELDS']['ID'],self::IBLOCK_31,$updElementsFields);
                            }
                        }
                    }
                }
            }

            //self::logData('3ListElemBeforeUpdate.log',[$arFields,$oldElemDataResult,$siblingElementsResult,$allElems/*,$updateOtherElems/*,$newPaymentSum*/]);
            //self::logData('2ListElemBeforeUpdate.log',[/*$arFields,$oldElemDataResult,*/[$unPayedElemsArr,count($unPayedElemsArr),'Уже оплачено: '.$alreadyPayedSum,'Не оплачено: '.$unPayedRassrochaWholeSum,'Больший платеж: '.$biggerPayment,'РАвній платеж: '.$equalPayment],$dealSumUahArr,$newPaymentSumArr,$newPaymentSum,$allElems,$dealData]);
           // self::logData('4ListElemBeforeUpdate.log',[[$unPayedElemsArr,count($unPayedElemsArr),'Уже оплачено: '.$alreadyPayedSum,'Не оплачено: '.$unPayedRassrochaWholeSum,'Больший платеж: '.$biggerPayment,'РАвній платеж: '.$equalPayment],$dealSumUahArr,$newPaymentSumArr,$allElems]);
           // self::logData('7ListElemBeforeUpdate.log',[$payDate,$kursElemDataResult[0]['PROPERTIES']['KURS_DOLARA']['VALUE'],$kursUsdNbuMassive]);
          //  self::logData('8BeforeUpdate.log',[$payDate,$kursUsdRes]);
        }

        if($errors){
            $text = '';
            foreach ($errors as $error){
                $text .= $error."\n";
            }

            global $APPLICATION;
            $APPLICATION->throwException($text);
            return false;
        }
    }

    //функция создания элементов списка "рассрочка" - Нужно ее візівать для создания єлементов списка = 31
    private function createRassrochkaElements($massive){
        $result = false;

        if($massive){

            if($massive['PAYMENTS_NUM'] > 0){
                $remainder = false;
                $biggerPayment = false;
                $equalPayment = false;

                //рассчет платежей
                $wholePaymentUAH = explode('|',$massive['RASSROCHKA_SUM_UAH']);

                //Проверяем, делится ли с отстатком
                $remainder = $wholePaymentUAH[0] % $massive['PAYMENTS_NUM'];
                if($remainder > 0){
                    $biggerPayment = ($wholePaymentUAH[0] - $remainder) / $massive['PAYMENTS_NUM'] + $remainder;
                    $equalPayment = ($wholePaymentUAH[0] - $remainder) / $massive['PAYMENTS_NUM'];
                }
                //или все делим на равніе части
                else $equalPayment = $wholePaymentUAH[0]/$massive['PAYMENTS_NUM'];


                for($i = 1; $i <= $massive['PAYMENTS_NUM']; $i++){
//                    if($remainder > 0 && $i == 1){
//                        $sumUAH = $biggerPayment;
//                    }
//                    else $sumUAH = $equalPayment;

                    //решение проблемі с задваиванием месяцев - не везде есть 31 число, поєтому скидіваем до 28 (как в Феврале)
                    //и берем последнее число месяца
                    if(date('d',strtotime($massive['START_DATE'])) > 28) {
                     //   echo 'Новая дата старта = '.date('d.m.Y', strtotime($massive['START_DATE'].'-4 day')).'<br>';
                        $massive['START_DATE'] = date('d.m.Y', strtotime($massive['START_DATE'].'-4 day'));
                    }


                    $newListElemFields = [
                        'NAME' => $massive['CONTRACT'].', платіж # '.$i,
                        "ACTIVE"         => "Y", // активен
                        "IBLOCK_ID"      => self::IBLOCK_31,
                        "PROPERTY_VALUES"=> [
                            '107' => $massive['CONTACTS'],
                            '108' =>
                                [
                                    'n0' =>
                                    [
                                        'VALUE' => $massive['DEAL_ID']
                                    ]
                                ],
                            '109' =>
                                [
                                    'n0' =>
                                        [
                                            'VALUE' => $massive['ZHK_NAME']
                                        ]
                                ],
                            '110' => 80, //Статус 80 - "Не оплачено" // 79 - Оплачено
                            '111' =>
                                [
                                    'n0' =>
                                        [
                                            'VALUE' => ($remainder > 0 && $i == 1) ? $biggerPayment.'|'.$wholePaymentUAH[1] : $equalPayment.'|'.$wholePaymentUAH[1],//'5000|UAH',
                                        ]
                                ],


                            '113' =>
                                ['
                                    n0' =>
                                    [
                                        'VALUE' => date('t.m.Y', strtotime($massive['START_DATE'].'+'.($i-1).' months')),//последний день выбранного месяца + платежи на n-месяцев
                                    ]
                                ],

                            '124' => 6643, //Пересчет 6643 - "Нет" // 6642 - Да
                        ],
                    ];

                   // $result[] = $newListElemFields;
                    $result[] = self::createNewListElement($newListElemFields);

                 //   self::logData('2406CreateListElems.log',[$i,$newListElemFields,$massive]);
                }

            }

        }
        return $result;
    }

    private function getKursFromListOrSite($payDate){
        $result = false; //курс доллара

        //поиск курса в списке IB = 33 по дате платежа
        $kursElemDataFilter = ['PROPERTY_116' =>  date('Y-m-d', strtotime($payDate)),'IBLOCK_ID' => self::IBLOCK_33];
        $kursElemDataSelect = ["ID","IBLOCK_ID","NAME","PROPERTY_*"];
        $kursElemDataResult = self::getListElementsAdnPropsByFilter($kursElemDataFilter,$kursElemDataSelect);
        if($kursElemDataResult){
            //$arFields['PROPERTY_VALUES']['115'] = $kursElemDataResult[0]['PROPERTIES']['KURS_DOLARA']['VALUE'];
            $result = $kursElemDataResult[0]['PROPERTIES']['KURS_DOLARA']['VALUE'];
        }
        else{
            $url = 'https://bank.gov.ua/NBUStatService/v1/statdirectory/exchange?';
            $formatType = 'json';
            $queryData = http_build_query([
                'date' => date('Ymd', strtotime($payDate)),
                'valcode' => 'USD',
            ]);
            $queryData .= '&'.$formatType;
            $kursUsdNbuMassive = json_decode(json_encode(self::makeGetRequest($url.$queryData)),true);
            if($kursUsdNbuMassive && $kursUsdNbuMassive[0]['cc'] == 'USD'){

                //создаем єлемент списка с датой и курса в ИБ = 33
                $newKursListElemFields = [
                    'NAME' => 'Курс на '.$payDate,
                    "ACTIVE"         => "Y", // активен
                    "IBLOCK_ID"      => self::IBLOCK_33,
                    "PROPERTY_VALUES"=> [
                        '116' => $payDate, //дата курса
                        '117' => $kursUsdNbuMassive[0]['rate'], // курс долара
                    ],
                ];
                $cursElemCreateResult = self::createNewListElement($newKursListElemFields);
                if($cursElemCreateResult['result'])
                    $result = $kursUsdNbuMassive[0]['rate'];
                    //$arFields['PROPERTY_VALUES']['115'] = $kursUsdNbuMassive[0]['rate'];
            }
        }
        return $result;
    }



}