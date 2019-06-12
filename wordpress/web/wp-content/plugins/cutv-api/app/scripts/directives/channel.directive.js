'use strict';

/**
 * @ngdoc directive
 * @name cutvApiAdminApp.directive:videoCounts
 * @description
 * # manageChannel
 */
angular.module('cutvApiAdminApp')
    .directive('channel', function($http, $compile, ChannelService) {
        return {
            restrict: 'E',
            replace: true,
            scope: {
                channel: '=',
                sources: '=',
            },
            templateUrl: '/wp-content/plugins/cutv-api/app/templates/channel.html',
            link: function(scope, element, attrs) {

                scope.activeTab = 'sources';

                scope.$watch('sources', (newValue, oldValue, scope) => {
                    //Do anything with $scope.letters
                    if (!!newValue && !!newValue.selected && newValue.selected.length) {
                        scope.activeTab = 'videos';
                    }
                });

            }
        };
    });