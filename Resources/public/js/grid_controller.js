'use strict';

function GridController($scope, $http) {
    $scope.data = {};
    $scope.data.last = 1;
    $scope.data.page = 1;
    $scope.data.perPage = 10;
    $scope.data.exportType ='csv';
    $scope.data.search = '';
    $scope.data.sorts = {};

    $scope.getData = function() {
        console.log("GetData Called");
        $http({
            method: 'GET',
            url: 'data.json',
            params: {
                "exportType": $scope.data.exportType,
                "page": $scope.data.page,
                "perPage": $scope.data.perPage,
                "search": $scope.data.search,
                "sorts": $scope.data.sorts
            }
        }).success(
        function(data, status, headers, config) {
            $scope.data.actions = data.actions;
            $scope.data.entities = data.entities;
            $scope.data.exportLink = data.exportLink;
            $scope.data.filtered = data.filtered;
            $scope.data.last = data.last;
            $scope.data.headers = data.headers;
            $scope.data.page = data.page;
            $scope.data.total = data.total;
        }).error(function(data, status, headers, config) {
            $scope.status = status;
            console.log('status');
            console.log(status);
        });
    }

    $scope.getData();

    $("select.grid-export").select2({
        minimumInputLength:0
        ,width: "100%"
    });

    $("select.grid-export").on("change",
        function (event) {
            $scope.data.exportType=$(this).val();
            $scope.getData();
        });

    $("select.grid-control-pages").select2({
        minimumInputLength:0
        ,width: "100%"
    });

    $("select.grid-control-pages").on("change",
        function (event) {
            $scope.data.perPage =$(this).val();
            $scope.getData();

        });

    $scope.previousPage = function() {
        if (1 < parseInt($scope.data.page)) {
            $scope.data.page = parseInt($scope.data.page) - 1;
            $scope.getData();
        }
    }
    $scope.nextPage = function() {
        if (parseInt($scope.data.last) > parseInt($scope.data.page)) {
            $scope.data.page = parseInt($scope.data.page) + 1;
            $scope.getData();
        }
    }
    $scope.sort = function(event, column) {
        if (undefined == $scope.data.sorts.length) {
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
        $scope.getData();
    }
}
