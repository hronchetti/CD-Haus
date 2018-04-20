/**
 * Angular Application master
 */
(function () {

    'use strict';  // Using javascript strict syntax mode to facilitate quality syntax

    angular.module('CDHaus', // Name of the app
        [
            // ngRoute makes the application single page by using the passed URL to determine what is shown in the View directive
            'ngRoute'
        ]
    ).config(
        [
            '$routeProvider',

            function ($routeProvider) {

                $routeProvider
                    .when('/album/:albumid', {
                        controller: 'TracksController',
                        templateUrl: 'js/partials/album-focus.html'
                    }).otherwise({
                    redirectTo: '/'
                });
            }
        ]
    );
})();
/*
 * Using Immediately-Invoked Function Expression (IIFE) that executes straight away and doesn't unnecessarily dirty
 * scope by creating global properties
 */