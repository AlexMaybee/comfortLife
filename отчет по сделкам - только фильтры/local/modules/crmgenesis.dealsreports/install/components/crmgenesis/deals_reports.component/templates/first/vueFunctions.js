let tab = new Vue({
    el: '#reports',
    data: {
        test: 'OLOLO TEST',
        filters: {
            dateFrom: '',
            dateTo: '',
            category: '',
            onlyOpenedDeals: true,
            currentStageId: [],
            dealType: [],
            payType: '',
            squarePriceFrom: '',
            squarePriceTo: '',
            percentRedeemedFrom: '',
            percentRedeemedTo: '',
            installmentNumberFrom: '',
            installmentNumberTo: '',
            presents: [],

        },
        list: {
            category: [],
            stages: [],
            dealTypes: [],
            payTypes: [],
            presents: [],
            paymentNumbers: [],
            error: '',
        }

    },
    methods: {
        getDates: function () {
            let date = new Date(),
                month, day;
            if(date.getMonth() < 10) month = '0' + (date.getMonth()+1);
            else month = date.getMonth()+1;

            if(date.getDate() < 10) day = '0' + date.getDate();
            else day = date.getDate();

            this.filters.dateFrom = date.getFullYear() + '-' + month + '-' + day;
            this.filters.dateTo = date.getFullYear() + '-' + month + '-' + day;
        },
        getCategories: function () {
            let self = this;
            BX.ajax({
                method: "POST",
                url: '/local/components/crmgenesis/deals_reports.component/ajax.php',
                data: {'ACTION': 'GIVE_ME_CATEGORIES_FOR_FILTER'},
                dataType: "json",
                onsuccess: function (response) {

                 //   console.log(response);
                    if(response.result) {
                        self.list.category = response.result;

                        //присвоение селекту направления значения
                        self.filters.category = response.result[0].ID

                        //стадии по направлениям
                        self.getStagesList(self.filters.category);


                    }
                    else self.list.error = response.error;

                }
            });
        },
        getStagesList: function (categoryId) {
            let self = this;
            BX.ajax({
                method: "POST",
                url: '/local/components/crmgenesis/deals_reports.component/ajax.php',
                data: {
                    'ACTION':'GIVE_ME_STAGES_LIST_FOR_FILTER',
                    'CATEGORY_ID': categoryId,
                },
                dataType: "json",
                onsuccess: function (response) {
                      console.log(response)
                    if(response.result){
                        self.list.stages = response.result;
                        // self.filters.currentStageId = [self.list.stages[0].ID]; //присваиваем значение selected при загрузке, чтобы можно было сразу запустить загрузку таблицы
                    }
                    else self.list.error = response.error;
                }
            });
        },
        getDealTypes: function () {
            let self = this;
            BX.ajax({
                method: "POST",
                url: '/local/components/crmgenesis/deals_reports.component/ajax.php',
                data: {
                    'ACTION':'GIVE_ME_DEAL_TYPES_LIST_FOR_FILTER',
                },
                dataType: "json",
                onsuccess: function (response) {
                   // console.log(response)
                    if(response.result){
                        self.list.dealTypes = response.result;
                        // self.filters.dealType = [self.list.dealTypes[0].ID]; //присваиваем значение selected при загрузке, чтобы можно было сразу запустить загрузку таблицы
                    }
                    else self.list.error = response.error;
                }
            });
        },
        getPayType: function () {
            let self = this;
            BX.ajax({
                method: "POST",
                url: '/local/components/crmgenesis/deals_reports.component/ajax.php',
                data: {
                    'ACTION':'GIVE_ME_PAY_TYPE_LIST_FOR_FILTER',
                },
                dataType: "json",
                onsuccess: function (response) {
               //     console.log(response)
                    if(response.result){
                        self.list.payTypes = response.result;
                        // self.filters.presents = [self.list.presents[0].ID]; //присваиваем значение selected при загрузке, чтобы можно было сразу запустить загрузку таблицы
                        console.log(self.list.payTypes)
                    }
                    else self.list.error = response.error;
                }
            });
        },
        getPresents: function () {
            let self = this;
            BX.ajax({
                method: "POST",
                url: '/local/components/crmgenesis/deals_reports.component/ajax.php',
                data: {
                    'ACTION':'GIVE_ME_PESENTS_LIST_FOR_FILTER',
                },
                dataType: "json",
                onsuccess: function (response) {
                  //  console.log(response)
                    if(response.result){
                        self.list.presents = response.result;
                        // self.filters.presents = [self.list.presents[0].ID]; //присваиваем значение selected при загрузке, чтобы можно было сразу запустить загрузку таблицы
                    }
                    else self.list.error = response.error;
                }
            });
        },
        getPaymentNumbers: function () {
            let self = this;
            BX.ajax({
                method: "POST",
                url: '/local/components/crmgenesis/deals_reports.component/ajax.php',
                data: {
                    'ACTION':'GIVE_ME_PAYMENT_NUMBERS_LIST_FOR_FILTER',
                },
                dataType: "json",
                onsuccess: function (response) {
                   // console.log(response)
                    if(response.result){
                        self.list.paymentNumbers = response.result;
                        // self.filters.presents = [self.list.paymentNumbers[0].ID]; //присваиваем значение selected при загрузке, чтобы можно было сразу запустить загрузку таблицы

                    }
                    else self.list.error = response.error;
                }
            });
        },
        changeCategory: function () {
            this.getStagesList(this.filters.category);
        },
    },
    computed: {

    },
    mounted: function () {
        this.getDates();
        this.getCategories();
        this.getDealTypes();
        this.getPayType();
        this.getPresents();
        this.getPaymentNumbers();
    },

});