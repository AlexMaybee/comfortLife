let tab = new Vue({
    el: '#reports',
    data: {
        test: 'OLOLO TEST',
        filters: {
            dateFrom: '',
            dateTo: '',
            category: '',
            dealType: [],
            currentStageId: [],
            onlyOpenedDeals: true,
        },

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

                    console.log(response);

                }
            });
        },
    },
    computed: {

    },
    mounted: function () {
        this.getDates();
        this.getCategories();
    }
});