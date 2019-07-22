<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();


class CustomDealsReports extends CBitrixComponent{

    public function test(){
        return 'Это тест, Уасся!';
    }

    //значения для фильтра направления
    public function getCategoriesForFilter(){
        $result = ['result' => 'Test', 'error' => false];

        $this->sentAnswer($result);
    }


    //ответ в консоль
    private function sentAnswer($answ){
        echo json_encode($answ);
    }
}