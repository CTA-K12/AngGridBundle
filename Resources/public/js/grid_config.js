'use strict';

angular.module('gridModule', ['gridFilters'])
    .config(['$routeProvider', function($routeProvider) {
        $routeProvider
            .when('/', {
                templateUrl: 'grid',
                controller: GridController
            })
            .otherwise({
                redirectTo: '/'
            });
    }]);