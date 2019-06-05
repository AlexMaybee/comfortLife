<?

//AddEventHandler("crm", "OnAfterCrmDealAdd", Array("RassrochkaDealEvents", "testLogAfter"));//отлавливаем кастом сессию с id расписания

//Событие при обновленнии сделки - переход на конкретные стадии 2-х направлений "договора" Киев и Ирпень
AddEventHandler("crm", "OnBeforeCrmDealUpdate", Array("RassrochkaDealEvents", "createRassrochkaListElementsForDeal"));

class RassrochkaDealEvents extends CustomFunctions{

    const IBLOCK_31 = 31; //ID списка элементов с рассрочками

    public function createRassrochkaListElementsForDeal(&$arFields){
        $dealData = 0;
        $errors = [];

        if(isset($arFields['STAGE_ID']) && in_array($arFields['STAGE_ID'], ['PREPARATION','C1:PREPARATION']) && $arFields['ID'] > 0){ //стадию заменить на EXECUTING и C1:EXECUTING

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
                       /* if(
                            (
                                (isset($arFields['UF_CRM_1550227609']) && !$arFields['UF_CRM_1550227609'] && !$dealData['UF_CRM_1550227609']) || (!isset($arFields['UF_CRM_1550227609']) && !$dealData['UF_CRM_1550227609'])
                            )
                            ||
                            (
                                (isset($arFields['UF_CRM_1550227631']) && !$arFields['UF_CRM_1550227631'] && !$dealData['UF_CRM_1550227631']) || (!isset($arFields['UF_CRM_1550227631']) && !$dealData['UF_CRM_1550227631'])
                            )
                        ) $errors[] = 'Не вказано суму першого внеску у ГРН. або у $';*/

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


                        if(!$errors){
                            self::logData('SuccessDealUpdate.log',[$arFields]);
                        }
                    }



                }


            }


        //    self::logData('BeforeDealUpdate.log',[$arFields,$dealData,$errors]);
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



    public function testLogAfter(&$arFields){
        self::logData('LogAfterDealCreate.log',$arFields);
    }
}