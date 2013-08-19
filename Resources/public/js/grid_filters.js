'use strict';

angular.module('gridFilters', [])
    .filter('boolean' , function() {
        return function(input) {
            console.log("Input");
            console.log(input);
            return ('true' == input) ? 'true-check' : 'false-ex';
        };
    });


