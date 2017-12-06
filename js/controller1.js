"use strict";

var myModule = angular.module("ModuleName", ['SomeModule']);

myModule.controller("ControllerName", ['$rootScope', '$scope', '$filter', 'SomeService',
    function($rootScope, $scope, $filter, SomeService) {
        $scope.$id += '-Name';

        this.someObject = { foo: 'bar' };

        SomeService.someCall();

        $scope.$on('eventName', function() {
            // do something
        });

        function somePrivateFunction() {
            // foo
        }

        this.somePublicFunction = function(param1) {
            // do something with param1
        }

    }
]);

myModule.filter('million', function() {
    return function(value) {
        return value / 1000000;
    };
});