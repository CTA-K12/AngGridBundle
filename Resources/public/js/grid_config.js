'use strict';

var gridModule = angular.module('gridModule', [])
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