'use strict';

angular.module('gridModule', [])
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