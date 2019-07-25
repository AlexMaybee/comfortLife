<? if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
//$APPLICATION->AddHeadScript('/local/components/crmgenesis/deals_reports.component/templates/first/vue.min.js');
$APPLICATION->AddHeadScript('/local/components/crmgenesis/deals_reports.component/templates/first/vue.js');


?>

<div id="reports">

    <h2 class="report-title">Звіт по угодах з
        <span class="date-span">{{filters.dateFrom.split('-').reverse().join('.')}}</span>
        по
        <span class="date-span">{{filters.dateTo.split('-').reverse().join('.')}}</span>
    </h2>

    <div v-if="list.error">
        <h2 class="error">{{list.error}}</h2>
    </div>
    <div v-else>

        <div class="filters">
            <table>

                <tr>
                    <td>
                        <label for="date_from">Дата з:</label>
                    </td>
                    <td>
                        <input name="date_from" v-model="filters.dateFrom" type="date" @change="" id="date_from">
                    </td>
                    <td>
                        <label for="date_to">Дата по:</label>
                    </td>
                    <td>
                        <input name="date_to" v-model="filters.dateTo" type="date" @change="" id="date_to">
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="deal_category">Оберіть напрямок:</label>
                    </td>
                    <td>
                        <select name='deal_category' v-model="filters.category" @change="changeCategory()" id="deal_category">
                             <option v-for="category in list.category" v-bind:value="category.ID">{{category.NAME}}</option>
                        </select>
                    </td>

                    <td>
                        <label for="deal_pay_type">Оберіть тип оплати:</label>
                    </td>
                    <td>
                        <select name='deal_pay_type' v-model="filters.payType" @change="" id="deal_pay_type">
                            <option v-for="type in list.payTypes" v-bind:value="type.ID">{{type.VALUE}}</option>
                        </select>
                    </td>

                    <td>
                        <label for="only_opened">Тільки угоди в роботі</label>
                    </td>
                    <td>
                        <input name="only_opened" v-model="filters.onlyOpenedDeals" type="checkbox" @change="" id="only_opened">
                    </td>

                </tr>
                <tr>
                    <td>
                        <label for="deal_stages">Оберіть стадії (множ, + ctrl):</label>

                       <!-- {{filters.currentStageId}}-->
                    </td>
                    <td>
                        <select name='deal_stages' id='deal_stages'
                                multiple
                                :size="list.stages.length"
                                v-model="filters.currentStageId"
                                @change="">
                            <option v-for="stage in list.stages" v-bind:value="stage.ID">{{stage.NAME}}</option>
                        </select>
                    </td>
                    <td>
                        <label for="deal_type">Оберіть тип угоди (ЖК):</label>
                    </td>
                    <td>
                        <select name='deal_type' id='deal_type'
                                multiple
                                :size="list.dealTypes.length"
                                v-model="filters.dealType" @change="">
                             <option v-for="type in list.dealTypes" v-bind:value="type.STATUS_ID">{{type.NAME}}</option>
                        </select>
                    </td>

                    <td>
                        <label for="deal_presents">Оберіть подарунки:</label>
                    </td>
                    <td>
                        <select name='deal_presents' id='deal_presents'
                                multiple
                                :size="list.presents.length"
                                v-model="filters.presents" @change="">
                            <option v-for="present in list.presents" v-bind:value="present.ID">{{present.VALUE}}</option>
                        </select>
                    </td>
                </tr>


                <template v-if="filters.payType == 90">
                    <tr>
                        <td>
                            <label for="deal_installments_from">Кількість платежів від:</label>
                        </td>
                        <td>
                            <select name='deal_installments_from' v-model="filters.installmentNumberFrom" @change="" id="deal_installments_from">
                                <option v-for="number in list.paymentNumbers" v-bind:value="number.ID">{{number.VALUE}}</option>
                            </select>
                        </td>
                        <td>
                            <label for="deal_installments_to">Кількість платежів по:</label>
                        </td>
                        <td>
                            <select name='deal_installments_to' v-model="filters.installmentNumberTo" @change="" id="deal_installments_to">
                                <option v-for="number in list.paymentNumbers" v-bind:value="number.ID">{{number.VALUE}}</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="redeem_percent_from">Перший внесок від, %:</label>
                        </td>
                        <td>
                            <input type="number" name="redeem_percent_from" v-model="filters.percentRedeemedFrom" id="redeem_percent_from">
                        </td>
                        <td>
                            <label for="redeem_percent_to">Перший внесок по, %:</label>
                        </td>
                        <td>
                            <input type="number" name="redeem_percent_to" v-model="filters.percentRedeemedTo" id="redeem_percent_to">
                        </td>
                    </tr>
                </template>

                <tr>
                    <td>
                        <label for="square_price_from">Вартість м<sup>2</sup>, від:</label>
                    </td>
                    <td>
                        <input type="number" name="square_price_from" v-model="filters.squarePriceFrom" id="square_price_from">
                    </td>
                    <td>
                        <label for="square_price_to">Вартість м<sup>2</sup>, по:</label>
                    </td>
                    <td>
                        <input type="number" name="square_price_to" v-model="filters.squarePriceTo" id="square_price_to">
                    </td>
                </tr>

            </table>

        </div>







    </div>



</div>







<script src="/local/components/crmgenesis/deals_reports.component/templates/first/vueFunctions.js"></script>
