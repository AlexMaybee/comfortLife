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
    }

    checkIfDealDetailsPage(string){
        var matchMassive, dealId;
        if(matchMassive = string.match(/\/crm\/deal\/details\/([\d]+)/i)){
            // console.log(matchMassive);
            return matchMassive[1] > 0 ? matchMassive[1] : false;
        }
        else return false
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
                    if(data.DEAL.PAYED) self.rassrochkaPayedIndicator(data.DEAL);
                    if(data.DEAL.UNPAYED) self.rassrochkaUnPayedIndicator(data.DEAL);
                    if(data.DEAL.PAY_EXPIRED) self.rassrochkaExpiredIndicator(data.DEAL);
                }
                else console.log(data.ERROR);
            }
        });
    }


    //кнопки-индикаторы

    rassrochkaPayedIndicator(data){
        var mdiv = $('.pagetitle-container.pagetitle-align-right-container .crm-entity-actions-container');
        if(mdiv != null){
            var bp = document.createElement('span');
            bp.className = 'rassrochka-payed-indicator task-view-button bp_start webform-small-button webform-small-button-accept task-indicator';
            bp.innerHTML =  'Сплачено: ' + data.PAYED + ' грн.';
            bp.title = 'По поточнiй угодi сплачено ' + data.PAYED + ' грн. з ' + data.RASSROCHKA_WHOLE_SUM;
            bp.style.cssText = 'display: inline-block!important; background-color: #4be00e; color: #191313;';
            mdiv.before(bp);
        }
    }

    rassrochkaUnPayedIndicator(data){
        var mdiv = $('.pagetitle-container.pagetitle-align-right-container .crm-entity-actions-container');
        if(mdiv != null){
            var bp = document.createElement('span');
            bp.className = 'rassrochka-unpayed-indicator task-view-button bp_start webform-small-button webform-small-button-accept task-indicator';
            bp.innerHTML = 'Залишок: ' + data.UNPAYED + ' грн.';
            bp.title = 'Залишок по розстрочцi ' + data.UNPAYED + ' грн. з ' + data.RASSROCHKA_WHOLE_SUM;
            bp.style.cssText = 'display: inline-block!important; background-color: #09c8f3; color: #fff;';
            mdiv.before(bp);
        }
    }

    rassrochkaExpiredIndicator(data){
        var mdiv = $('.pagetitle-container.pagetitle-align-right-container .crm-entity-actions-container');
        if(mdiv != null){
            var bp = document.createElement('span');
            bp.className = 'rassrochka-expired-indicator task-view-button bp_start webform-small-button webform-small-button-accept task-indicator';
            bp.innerHTML = 'БОРГ: ' + data.PAY_EXPIRED + ' грн.';
            bp.title = 'Прострочено платежiв на суму ' + data.PAY_EXPIRED;
            bp.style.cssText = 'display: inline-block!important; background-color: #f33f09; color: #fff;';
            mdiv.before(bp);
        }
    }
}