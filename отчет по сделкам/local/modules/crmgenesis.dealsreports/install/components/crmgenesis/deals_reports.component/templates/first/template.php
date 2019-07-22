<? if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
//$APPLICATION->AddHeadScript('/local/components/crmgenesis/deals_reports.component/templates/first/vue.min.js');
$APPLICATION->AddHeadScript('/local/components/crmgenesis/deals_reports.component/templates/first/vue.js');


?>

<div id="reports">

    <h2 class="report-title">Отчет по сделкам с
        <span class="date-span">{{filters.dateFrom.split('-').reverse().join('.')}}</span>
        по
        <span class="date-span">{{filters.dateTo.split('-').reverse().join('.')}}</span>
    </h2>

    <div class="filters">
        <table>
            <tr>
                <td>
                    <label for="deal_category">Выберите направление:</label>
                </td>
                <td>
                    <select name='deal_category' v-model="filters.category" @change="">
                       <!-- <option v-for="category in categories" v-bind:value="category.ID">{{category.NAME}}</option>-->
                    </select>
                </td>
                <td>
                    <label for="deal_category">Выберите текущую стадию:</label>
                </td>
                <td>
                    <select name='deal_category' multiple v-model="filters.currentStageId" @change="">
                        <!-- <option v-for="stage in stagesList" v-bind:value="stage.ID">{{stage.NAME}}</option>-->
                    </select>
                </td>

            </tr>
            <tr>


                <td>
                    <label for="deal_category">Выберите Тип сделки:</label>
                </td>
                <td>
                    <select name='deal_category' multiple v-model="filters.dealType" @change="">
                       <!-- <option v-for="assigned in assignedList" v-bind:value="assigned.ID">{{assigned.NAME}}</option>-->
                    </select>
                </td>
                <td>
                    <label for="only_opened">Только сделки в работе</label>
                </td>
                <td>
                    <input name="only_opened" v-model="filters.onlyOpenedDeals" type="checkbox" @change="" id="only_opened">
                </td>
            </tr>
            <tr>
                <td>
                    <label for="date_from">Дата с:</label>
                </td>
                <td>
                    <input name="date_from" v-model="filters.dateFrom" type="date" @change="">
                </td>
                <td>
                    <label for="date_to">Дата по:</label>
                </td>
                <td>
                    <input name="date_to" v-model="filters.dateTo" type="date" @change="">
                </td>
            </tr>
        </table>

    </div>

</div>







<script src="/local/components/crmgenesis/deals_reports.component/templates/first/vueFunctions.js"></script>
