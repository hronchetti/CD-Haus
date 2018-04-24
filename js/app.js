/**
 * Angular Application master, just for config settings
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

                /* When the URL includes /album/ and given Album_ID change controller to
                 * TrackController so user sees the tracks for the given album
                 * Otherwise (no matter what URL is given) redirect back to root URL
                 * and use AlbumsController
                 */

                $routeProvider
                    .when('/album/:Album_ID', {
                        controller: 'TracksController',
                        templateUrl: 'js/partials/album-focus.html'
                    }).otherwise({
                        redirectTo: '/',
                        controller: 'AlbumsController',
                        templateUrl: 'js/partials/album-list.html'
                });
            }
        ]
    );
})();
/*
 * Using Immediately-Invoked Function Expression (IIFE) that executes straight away and doesn't unnecessarily dirty
 * scope by creating global properties
 */