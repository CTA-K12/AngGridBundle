'use strict';

var GridController = ['$scope', '$http', '$cookieStore', 'initData', function($scope, $http, $cookieStore, initData) {
    $scope.data = initData.initData();
    $scope.data.exportType ='csv';
    $scope.data.requestCount = 0;
    $scope.data.addView=false;
    $scope.data.showControl=true;

    $scope.toggleAdd = function(){
      $scope.data.addView=!$scope.data.addView;
    }

    $scope.toggleControl = function(){
      $scope.data.showControl=!$scope.data.showControl;
    }

    $scope.notSorted = function(obj){
        if (!obj) {
            return [];
        }
        return Object.keys(obj);
    }

    $scope.makeRequest = function() {
        $scope.data.requestCount += 1;
        $scope.sendRequest($scope.data.requestCount);
    }

    $scope.sendRequest = function(i) {
        setTimeout(function() {
            $scope.getData(i)
        }, 300);
    }

    $scope.logThis = function(i) {
        console.log(i);
    }

    $scope.getData = function(count) {
        if (count != $scope.data.requestCount) {
            return;
        }
        $http({
            method: 'GET',
            url: 'data.json',
            params: {
                "exportType": $scope.data.exportType,
                "page": $scope.data.page,
                "perPage": $scope.data.perPage,
                "requestCount": $scope.data.requestCount,
                "search": $scope.data.search,
                "sorts": $scope.data.sorts,
            }
        }).success(
        function(data, status, headers, config) {
            if (parseInt(data.requestCount) != $scope.data.requestCount) {
                return;
            }
            $scope.data.buttons = data.buttons;
            $scope.data.entities = data.entities;
            $scope.data.exportLink = data.exportLink;
            $scope.data.filtered = data.filtered;
            $scope.data.last = data.last;
            $scope.data.headers = data.headers;
            $scope.data.page = data.page;
            $scope.data.paths = data.paths;
            $scope.data.total = data.total;
            $scope.data.exportArray = data.exportArray;
            var cookie = {
                page: $scope.data.page,
                perPage: $scope.data.perPage,
                search: $scope.data.search,
                sorts: $scope.data.sorts
            };
            $cookieStore.put('grid0', cookie);
            $cookieStore.remove('page');
            $cookieStore.remove('perPage');
            $cookieStore.remove('search');
            $cookieStore.remove('sorts');
        }).error(function(data, status, headers, config) {
            $cookieStore.remove('page');
            $cookieStore.remove('perPage');
            $cookieStore.remove('search');
            $cookieStore.remove('sorts');
            $scope.status = status;
        });
    }

    if (typeof $("select.grid-export").select2 == 'function'){

        $("select.grid-export").select2({
            minimumInputLength:0
            ,width: "100%"
        });

        $('input.s2').each(starts2);

        $("select.grid-export").on("change",
            function (event) {
                $scope.data.exportType=$(this).val();
                $scope.makeRequest();
            });

        $("select.grid-pages").select2({
            minimumInputLength:0
            ,width: "100%"
        });

        $("select.grid-pages").on("change",
            function (event) {
                $scope.data.perPage =$(this).val();
                $scope.makeRequest();
            });

    }

    $scope.previousPage = function() {
        if (1 < parseInt($scope.data.page)) {
            $scope.data.page = parseInt($scope.data.page) - 1;
            $scope.makeRequest();
        }
    }
    $scope.nextPage = function() {
        if (parseInt($scope.data.last) > parseInt($scope.data.page)) {
            $scope.data.page = parseInt($scope.data.page) + 1;
            $scope.makeRequest();
        }
    }
    $scope.sort = function(event, column) {
        if ($scope.data.headers[column].sortable) {
            if (undefined == $scope.data.sorts) {
                $scope.data.sorts = [{column: column, direction: 'asc'}];
            } else if (undefined == $scope.data.sorts[0]) {
                $scope.data.sorts = [{column: column, direction: 'asc'}];
            } else {
                if (event.shiftKey) {
                    if (column == $scope.data.sorts[$scope.data.sorts.length - 1]['column']) {
                        if ('asc' == $scope.data.sorts[$scope.data.sorts.length - 1]['direction']) {
                            $scope.data.sorts[$scope.data.sorts.length - 1]['direction'] = 'desc';
                        } else {
                            $scope.data.sorts.splice($scope.data.sorts.length - 1, 1);
                        }
                    } else {
                        var found = false;
                        for (var i = 0; i < $scope.data.sorts.length; i++)
                        {
                            if (column == $scope.data.sorts[i].column) {
                                found = true;
                            }
                        }
                        if (false == found) {
                            $scope.data.sorts.push({column: column, direction: 'asc'});
                        }
                    }
                } else {
                    if (column == $scope.data.sorts[0]['column']) {
                        if ('asc' == $scope.data.sorts[0]['direction']) {
                            $scope.data.sorts = [{column: column, direction: 'desc'}];
                        } else {
                            $scope.data.sorts = {};
                        }
                    } else {
                        $scope.data.sorts = [{column: column, direction: 'asc'}];
                    }
                }
            }
            $scope.makeRequest();
        }
    }

    $scope.changePage = function(perPage) {
        $scope.data.perPage = perPage;
        $scope.makeRequest();
    }
}];
