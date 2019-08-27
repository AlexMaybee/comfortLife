<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
require("class.php");


//подключение файла с классами и использование тех же методов.
$obj = new CustomDealsReports;
//echo '<br>'.$res = $obj->test();
$resultTable = $obj->getInfoByZhk($_REQUEST);



/*if($_REQUEST) {

    logData([$_REQUEST,$resultTable]);
}*/


header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: filename=zhk_statistics.xls");


if($resultTable):

?>
<html>
<head>
    <title>Отчет по календарю</title>
    <meta http-equiv="Content-Type" content="text/html; charset=<?= LANG_CHARSET ?>">
    <style>
        td {mso-number-format:\@;}
        .number0 {mso-number-format:0;}
        .number2 {mso-number-format:Fixed;}
    </style>

</head>
<body>
<table border="1">

    <tr></tr>

    <tr>
        <td colspan="2">Звіт з</td>
        <td><?=date('d.m.Y',strtotime($_REQUEST['dateFrom']));?></td>
        <td colspan="2">По</td>
        <td><?=date('d.m.Y',strtotime($_REQUEST['dateTo']));?></td>
    </tr>

    <tr></tr>

    <thead>
    <tr style="background-color: #0f82c5; color: #fff">
        <?php
            foreach ($resultTable['result']['TH_FIELDS'] as $key => $th_fields):
        ?>
        <th><?=$th_fields['NAME'];?></th>
        <?php
            endforeach;
        ?>
    </tr>
    </thead>

    <?php
        foreach ($resultTable['result']['TD_FIELDS'] as $index => $td_fields):
    ?>
    <tbody>
        <tr style="background-color: #ddf2fd">
            <td><?=($index + 1)?></td>
            <td><?=$td_fields['ZHK_NAME']?></td>
            <td><?=$td_fields['PAY_TYPE']?></td>
            <td>

                <?php
                    foreach ($td_fields['PRESENTS'] as $present):
                ?>

                    <?=$present['NAME'].' - '.$present['QUANTITY'].';'?>

                <?php
                    endforeach;
                ?>

            </td>
            <?
                if($td_fields['AVARGE_1M_SQU_FIRST_PAYMENT_UAH']):
            ?>

                <td>
                    <?=$td_fields['AVARGE_1M_SQU_FIRST_PAYMENT_UAH']?>
                </td>

            <?php
                endif;
            ?>

            <?
                if($td_fields['AVARGE_1M_SQU_FIRST_PAYMENT_USD']):
                ?>

                <td>
                    <?=$td_fields['AVARGE_1M_SQU_FIRST_PAYMENT_USD']?>
                </td>

            <?php
                endif;
            ?>

            <?
                if($td_fields['AVARGE_1M_SQU_REST_UAH']):
            ?>

                <td>
                    <?=$td_fields['AVARGE_1M_SQU_REST_UAH']?>
                </td>

            <?php
                endif;
            ?>

            <?
                if($td_fields['AVARGE_1M_SQU_REST_USD']):
            ?>

                <td>
                    <?=$td_fields['AVARGE_1M_SQU_REST_USD']?>
                </td>

            <?php
                endif;
            ?>

            <td><?=$td_fields['AVARGE_1M_SQU_UAH']?></td>
            <td><?=$td_fields['AVARGE_1M_SQU_USD']?></td>
            <td><?=$td_fields['WHOLE_SQU_M']?></td>

            <?
                if($td_fields['WHOLE_SQU_M_REDEEMED']):
            ?>

                <td>
                    <?=$td_fields['WHOLE_SQU_M_REDEEMED']?>
                </td>

            <?php
                endif;
            ?>

            <td><?=$td_fields['CONTRACT_SUM_UAH']?></td>
            <td><?=$td_fields['CONTRACT_SUM_USD']?></td>

        </tr>


        <?
            foreach ($td_fields['DEALS_MASSIVE'] as $i => $td_deals):
        ?>
            <tr>
                <td><?=(($index + 1).'.'.($i + 1))?></td>
                <td><?=$td_deals['TITLE']?></td>
                <td><?=$td_deals['PAY_TYPE']?></td>
                <td>
                    <?php
                        foreach ($td_deals['PRESENTS'] as $present):
                    ?>

                        <?=$present['NAME'].','?>

                        <?php
                        endforeach;
                    ?>
                </td>

                <?
                    if($td_deals['M_SQU_FIRST_PAYMENT_UAH']):
                ?>

                    <td>
                        <?=$td_deals['M_SQU_FIRST_PAYMENT_UAH']?>
                    </td>

                <?php
                    endif;
                ?>

                <?
                    if($td_deals['M_SQU_FIRST_PAYMENT_USD']):
                ?>

                    <td>
                        <?=$td_deals['M_SQU_FIRST_PAYMENT_USD']?>
                    </td>

                <?php
                    endif;
                ?>

                <?
                    if($td_deals['M_SQU_REST_UAH']):
                ?>

                    <td>
                        <?=$td_deals['M_SQU_REST_UAH']?>
                    </td>

                <?php
                    endif;
                ?>

                <?
                    if($td_deals['M_SQU_REST_USD']):
                ?>

                    <td>
                        <?=$td_deals['M_SQU_REST_USD']?>
                    </td>

                <?php
                    endif;
                ?>

                <td><?=$td_deals['M_SQU_UAH']?></td>
                <td><?=$td_deals['M_SQU_USD']?></td>
                <td><?=$td_deals['SQU_M']?></td>

                <?
                    if($td_deals['SQU_M_REDEEMED']):
                ?>

                    <td>
                        <?=$td_deals['SQU_M_REDEEMED']?>
                    </td>

                <?php
                    endif;
                ?>

                <td><?=$td_deals['CONTRACT_SUM_UAH']?></td>
                <td><?=$td_deals['CONTRACT_SUM_USD']?></td>


            </tr>
        <?
            endforeach;
        ?>


    </tbody>
    <?php
        endforeach;
    ?>


    <tfoot>
        <tr style="background-color: #0f82c5; color: #fff; font-weight: bolder">

            <td colspan="3">Загалом ЖК Знайдено: <?=count($resultTable['result']['TD_FIELDS']);?></td>

            <td></td>

            <?
                if($resultTable['result']['WHOLE_ROW']['WHOLE_AVARGE_1M_SQU_FIRST_PAYMENT_UAH']):
            ?>

                <td>
                    <?=$resultTable['result']['WHOLE_ROW']['WHOLE_AVARGE_1M_SQU_FIRST_PAYMENT_UAH']?>
                </td>

            <?php
                endif;
            ?>

            <?
                if($resultTable['result']['WHOLE_ROW']['WHOLE_AVARGE_1M_SQU_FIRST_PAYMENT_USD']):
            ?>

                <td>
                    <?=$resultTable['result']['WHOLE_ROW']['WHOLE_AVARGE_1M_SQU_FIRST_PAYMENT_USD']?>
                </td>

            <?php
                endif;
            ?>

            <?
                if($resultTable['result']['WHOLE_ROW']['WHOLE_AVARGE_1M_SQU_REST_UAH']):
            ?>

                <td>
                    <?=$resultTable['result']['WHOLE_ROW']['WHOLE_AVARGE_1M_SQU_REST_UAH']?>
                </td>

            <?php
                endif;
            ?>

            <?
                if($resultTable['result']['WHOLE_ROW']['WHOLE_AVARGE_1M_SQU_REST_USD']):
            ?>

                <td>
                    <?=$resultTable['result']['WHOLE_ROW']['WHOLE_AVARGE_1M_SQU_REST_USD']?>
                </td>

            <?php
                endif;
            ?>

            <td><?=$resultTable['result']['WHOLE_ROW']['WHOLE_AVARGE_1M_SQU_UAH']?></td>
            <td><?=$resultTable['result']['WHOLE_ROW']['WHOLE_AVARGE_1M_SQU_USD']?></td>
            <td><?=$resultTable['result']['WHOLE_ROW']['WHOLE_WHOLE_SQU_M']?></td>

            <?
                if($resultTable['result']['WHOLE_ROW']['WHOLE_WHOLE_SQU_M_REDEEMED']):
            ?>

                <td>
                    <?=$resultTable['result']['WHOLE_ROW']['WHOLE_WHOLE_SQU_M_REDEEMED']?>
                </td>

            <?php
                endif;
            ?>

            <td><?=$resultTable['result']['WHOLE_ROW']['WHOLE_CONTRACT_SUM_UAH']?></td>
            <td><?=$resultTable['result']['WHOLE_ROW']['WHOLE_CONTRACT_SUM_USD']?></td>

        </tr>
    </tfoot>


</table>
</body>
</html>

<?php

endif;


include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");


function logData($data){
    $file = __DIR__.'/testLog.log';
    file_put_contents($file, print_r($data,true), FILE_APPEND | LOCK_EX);
}