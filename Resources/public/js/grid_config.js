'use strict';

var gridModule = angular.module('gridModule', ['ngCookies'])
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