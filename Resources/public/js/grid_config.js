'use strict';

angular.module('gridModule', ['gridFilters'])
    .config(['$routeProvider', function($routeProvider) {
        $routeProvider
            .when('/', {
                templateUrl: 'list',
                controller: GridController
            })
            .otherwise({
                redirectTo: '/'
            });
    }]);