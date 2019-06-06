<?

//отлавливаем кастом сессию с id расписания
//AddEventHandler("crm", "OnAfterCrmDealAdd", Array("RassrochkaDealEvents", "RassrochkaElemsAddAfterDealCreate"));
//AddEventHandler("crm", "OnBeforeCrmDealAdd", Array("RassrochkaDealEvents", "RassrochkaElemsAddBeforeDealCreate"));

//логирование перед соданием элемента списка
//AddEventHandler("iblock", "OnBeforeIBlockElementAdd", Array("RassrochkaDealEvents", "beforeIBlockElementAddFunction"));


//Событие при обновленнии сделки - переход на конкретные стадии 2-х направлений "договора" Киев и Ирпень
//AddEventHandler("crm", "OnBeforeCrmDealUpdate", Array("RassrochkaDealEvents", "createRassrochkaListElementsForDeal"));

class RassrochkaDealEvents extends CustomFunctions{

    const IBLOCK_31 = 31; //ID списка элементов с рассрочками
    const ALLOVED_ON_STAGES = ['PREPARATION','C1:PREPARATION']; //Стадии, на которіх срабатівают собітия создания єл. рассрочки

    public function createRassrochkaListElementsForDeal(&$arFields){
        $dealData = 0;
        $errors = [];

        if(isset($arFields['STAGE_ID']) && in_array($arFields['STAGE_ID'], self::ALLOVED_ON_STAGES) && $arFields['ID'] > 0){ //стадию заменить на EXECUTING и C1:EXECUTING

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
                                'RASSROCHKA_SUM_USD' => $dealData['UF_CRM_1550227726'],
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
                            if(isset($arFields['UF_CRM_1550227712']) && $arFields['UF_CRM_1550227712']) $newDataMassive['RASSROCHKA_SUM_UAH'] = $arFields['UF_CRM_1550227712'];

                            //сумма рассрочки, USD
                          //  if($dealData['UF_CRM_1550227726']) $newDataMassive['RASSROCHKA_SUM_USD'] = $dealData['UF_CRM_1550227726'];
                            if(isset($arFields['UF_CRM_1550227726']) && $arFields['UF_CRM_1550227726']) $newDataMassive['RASSROCHKA_SUM_UAH'] = $arFields['UF_CRM_1550227726'];

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
                            if($newDataMassive['PAYMENTS_NUM'] > 0) $rassrochkaElemsCreateResult = self::createRassrochkaElements($newDataMassive);
                        }
                    }

                }

            }

            self::logData('BeforeDealUpdate.log',[$arFields,$dealData,$errors,$newDataMassive,$rassrochkaElemsCreateResult]);
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



    public function RassrochkaElemsAddAfterDealCreate(&$arFields){
        self::logData('LogAfterDealCreate.log',$arFields);
    }

    public function RassrochkaElemsAddBeforeDealCreate(&$arFields){
        $errors = [];
        if(in_array($arFields['STAGE_ID'],self::ALLOVED_ON_STAGES)){ //стадию заменить на EXECUTING и C1:EXECUTING
            if($arFields['UF_CRM_1550841222'] == 90){
                if(empty($arFields['UF_CRM_1550227609'])) $errors[] = 'НЕ УКАЗАН ПЕРВЫЙ ВЗНОС!';
                if(empty($arFields['UF_CRM_1550227814'])) $errors[] = 'НЕ УКАЗАНО КОЛ-ВО ОПЛАЧЕННЫХ КЛИЕНТОМ МЕТРОВ !';
            }

        }


        self::logData('LogBeforeDealCreate.log',$arFields);


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

    public function beforeIBlockElementAddFunction(&$arFields){
        self::logData('ListElemBeforeCreate.log',$arFields);
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

                    $newListElemFields = [
                        'NAME' => $massive['CONTRACT'].', платіж # '.$i,
                        "ACTIVE"         => "Y", // активен
                        "IBLOCK_ID"      => 31,
                        "PROPERTY_VALUES"=> [
                            '107' => $massive['CONTACTS'],
                            '108' => $massive['DEAL_ID'],
                            '109' => $massive['ZHK_NAME'],
                            '110' => 80, //Статус 80 - "Не оплачено" // 79 - Оплачено
                            '111' => ($remainder > 0 && $i == 1) ? $biggerPayment.'|'.$wholePaymentUAH[1] : $equalPayment.'|'.$wholePaymentUAH[1],//'5000|UAH',
                            '113' => date('t.m.Y', strtotime($massive['START_DATE'].'+'.($i-1).' months')),//последний день выбранного месяца + платежи на n-месяцев
                        ],
                    ];

                   // $result[] = $newListElemFields;
                    $result[] = self::createNewListElement($newListElemFields);
                }



            }


        }
        return $result;
    }


}