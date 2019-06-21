BX.ready(function() {
    let personButton = new RassrochkaRestInfo();
});

class RassrochkaRestInfo {
    constructor(){
        this.url = window.location.href;

        //проверяем, что текущая страница - сделка
        if(this.checkIfDealDetailsPage(this.url)){
            this.dealId = this.checkIfDealDetailsPage(this.url);
            this.makeRequestToDealData(this.dealId);
        }

        //проверяем, что текущая страница - список № 31
        if(this.checkIfList31Page(this.url)){
            this.makeRequestToElemsList31();
        }
    }

    checkIfDealDetailsPage(string){
        var matchMassive;
        if(matchMassive = string.match(/\/crm\/deal\/details\/([\d]+)/i)){
            // console.log(matchMassive);
            return matchMassive[1] > 0 ? matchMassive[1] : false;
        }
        else return false
    }

    checkIfList31Page(string){
        var matchMassive, result = false;
        if(matchMassive = string.match(/\/services\/lists\/([\d]+)\/view\/0\//i)){
            // console.log(matchMassive);
            if(matchMassive[1] == 31) result = matchMassive[1];
        }
        return result;
    }

    makeRequestToDealData(dealId){
        var self = this;
        BX.ajax({
            method: "POST",
            url: '/local/lib/addRassroschkaRests/ajax/handler.php',
            data: {'DEAL_ID':dealId,'ACTION':'GIVE_ME_DEAL_DATA'},
            dataType: "json",
            onsuccess: function (data) {

                console.log(data);
                if(data.DEAL){

                    let searchBlock = $('.pagetitle-inner-container');
                    if(searchBlock.length > 0) {
                        let customBlock = document.createElement('div');
                        customBlock.className = 'my_custom_ressrochka_indicators_block';
                        customBlock.style.cssText = 'display: flex; /*background-color: #c59e92;*/ color: #fff;margin: 10px 20px';

                        //добавляем на стр. блок, в который будем добавлять индикаторы
                        searchBlock.after(customBlock);

                        if(data.DEAL.PAYED) self.rassrochkaPayedIndicator(data.DEAL);
                        if(data.DEAL.WITH_START_PAY_PAYED) self.rassrochkaPayedWithStartPayIndicator(data.DEAL);
                        if(data.DEAL.UNPAYED) self.rassrochkaUnPayedIndicator(data.DEAL);
                        if(data.DEAL.PAY_EXPIRED) self.rassrochkaExpiredIndicator(data.DEAL);
                    }
                }
                else console.log(data.ERROR);
            }
        });
    }

    makeRequestToElemsList31(){
        var self = this;
        BX.ajax({
            method: "POST",
            url: '/local/lib/addRassroschkaRests/ajax/handler.php',
            data: {'ACTION':'GIVE_ME_PAYMENTS_RESULT_MASSIVE'},
            dataType: "json",
            onsuccess: function (data) {

             //   console.log('makeRequestToElemsList31: ',data);
                if(data.RESULT){
                    let searchBlock = $('.pagetitle-inner-container');
                  //  console.log(searchBlock.length);
                    if(searchBlock.length > 0) {
                        let customBlock = document.createElement('div');
                        customBlock.className = 'my_custom_ressrochka_indicators_block';
                        customBlock.style.cssText = 'display: flex; /*background-color: #c59e92;*/ color: #fff;margin: 10px 20px';

                        //добавляем на стр. блок, в который будем добавлять индикаторы
                        searchBlock.after(customBlock);

                        if (data.RESULT.PAYED_WITH_START_PAY) self.rassrochkaWholeListPayedWithStartFromDealIndicator(data.RESULT.PAYED_WITH_START_PAY);
                        if (data.RESULT.EXPIRED_PAYMENTS) self.rassrochkaWholeListExpiredIndicator(data.RESULT.EXPIRED_PAYMENTS);
                    }
                }
                else console.log(data.ERROR);
            }
        });
    }

    //кнопки-индикаторы

    rassrochkaPayedIndicator(data){
       // var mdiv = $('.pagetitle-container.pagetitle-align-right-container .crm-entity-actions-container');
        var mdiv = $('.my_custom_ressrochka_indicators_block');
        if(mdiv != null){
            var bp = document.createElement('span');
            bp.className = 'rassrochka-payed-indicator task-view-button bp_start webform-small-button webform-small-button-accept task-indicator';
            bp.innerHTML =  'Сплачено: ' + data.PAYED + ' грн.';
            bp.title = 'По поточнiй угодi сплачено ' + data.PAYED + ' грн. з ' + data.RASSROCHKA_WHOLE_SUM;
            bp.style.cssText = 'display: inline-block!important; background-color: #4be00e; color: #191313;';
         //   mdiv.before(bp);
            mdiv.append(bp);
        }
    }

    rassrochkaUnPayedIndicator(data){
        var mdiv = $('.my_custom_ressrochka_indicators_block');
        if(mdiv != null){
            var bp = document.createElement('span');
            bp.className = 'rassrochka-unpayed-indicator task-view-button bp_start webform-small-button webform-small-button-accept task-indicator';
            bp.innerHTML = 'Залишок: ' + data.UNPAYED + ' грн.';
            bp.title = 'Залишок по розстрочцi ' + data.UNPAYED + ' грн. з ' + data.RASSROCHKA_WHOLE_SUM;
            bp.style.cssText = 'display: inline-block!important; background-color: #09c8f3; color: #fff;';
            mdiv.append(bp);
        }
    }

    rassrochkaExpiredIndicator(data){
        var mdiv = $('.my_custom_ressrochka_indicators_block');
        if(mdiv != null){
            var bp = document.createElement('span');
            bp.className = 'rassrochka-expired-indicator task-view-button bp_start webform-small-button webform-small-button-accept task-indicator';
            bp.innerHTML = 'БОРГ: ' + data.PAY_EXPIRED + ' грн.';
            bp.title = 'Прострочено платежiв на суму ' + data.PAY_EXPIRED;
            bp.style.cssText = 'display: inline-block!important; background-color: #f33f09; color: #fff;';
            mdiv.append(bp);
        }
    }

    rassrochkaPayedWithStartPayIndicator(data){
        var mdiv = $('.my_custom_ressrochka_indicators_block');
        if(mdiv != null){
            var bp = document.createElement('span');
            bp.className = 'rassrochka-with-start-pay-indicator task-view-button bp_start webform-small-button webform-small-button-accept task-indicator';
            bp.innerHTML = 'Разом с першим внеском: ' + data.WITH_START_PAY_PAYED + ' грн.';
            bp.title = 'Разом с першим внеском отримано суму ' + data.WITH_START_PAY_PAYED;
            bp.style.cssText = 'display: inline-block!important; background-color: #0239b7; color: #fff;';
            mdiv.append(bp);
        }
    }


    //Индикаторы на стр. списка № 31
    rassrochkaWholeListExpiredIndicator(expiredSum){
        var mdiv = $('.my_custom_ressrochka_indicators_block');
        if(mdiv != null){
            var date = new Date();
            var bp = document.createElement('span');
            bp.className = 'rassrochka-expired-indicator task-view-button bp_start webform-small-button webform-small-button-accept task-indicator';
            bp.innerHTML = 'ЗАГАЛЬНИЙ БОРГ на '+ date.getDate()+'.'+ (date.getMonth() + 1) + '.' + date.getFullYear() +': ' + expiredSum + ' грн.';
            bp.title = 'Прострочено платежiв на суму ' + expiredSum;
            bp.style.cssText = 'display: inline-block!important; background-color: #f33f09; color: #fff;margin: 10px 20px';
            mdiv.append(bp);
        }
    }

    rassrochkaWholeListPayedWithStartFromDealIndicator(payedSum){
        var mdiv = $('.my_custom_ressrochka_indicators_block');
        if(mdiv != null){
            var date = new Date();
            var bp = document.createElement('span');
            bp.className = 'rassrochka-whole-payed-indicator task-view-button bp_start webform-small-button webform-small-button-accept task-indicator';
            bp.innerHTML = 'ЗАГАЛОМ ОТРИМАНО на '+ date.getDate()+'.'+ (date.getMonth() + 1) + '.' + date.getFullYear() +': ' + payedSum + ' грн.';
            bp.title = 'Прострочено платежiв на суму ' + payedSum;
            bp.style.cssText = 'display: inline-block!important; background-color: #22b127; color: #fff;margin: 10px 20px';
            mdiv.append(bp);
        }
    }

}