'use strict';

// require '../services/channel.svc';

/**
 * @ngdoc directive
 * @name cutvApiAdminApp.directive:manageSources
 * @description
 * # manageChannel
 */
angular.module('cutvApiAdminApp')
    .directive('manageSources', (ChannelService, $routeParams, $document) => {
        return {
            restrict: 'E',
            // replace: true,
            scope: {
                channel: '=',
                sources: '='
            },
            templateUrl: '/wp-content/plugins/cutv-api/app/templates/manage-sources.html',

            link: function(scope, element, attrs) {

                const formElement = $document.find('form')[0];
                scope.formClass = formElement.classList.value;

                scope.query = '';
                scope.updateSuccess = false;

                scope.selectedSources = new Set(scope.sources.selected);
                scope.availableSources = new Set(scope.sources.available);

                scope.sourcesToArrays = (() => {
                    scope.sources = {
                        selected: Array.from(scope.selectedSources),
                        available: Array.from(scope.availableSources)
                    }
                });


                scope.selectSource = (source) => {
                    if (source.selected) {
                        scope.selectedSources.add(source);
                        scope.availableSources.delete(source);
                    } else {
                        scope.availableSources.add(source);
                        scope.selectedSources.delete(source);
                    }
                    scope.sourcesToArrays();
                };

                scope.updateChannelSources = () => {

                    debugger;
                    if (!scope.channel) {

                        return;
                    }
                    const data = {
                        action: 'cutv_update_channel_sources',
                        sources: scope.sources.selected.map(s => s.source_id).join(','),
                        channel: scope.channel.pid,
                        move_videos: true
                    };

                    // debugger;
                    ChannelService.handlePluginAction(data).then(channelSourceIds => {

                        channelSourceIds = channelSourceIds.map(x => Number(x));
                        scope.sources.selected = scope.sources.selected.filter(source => channelSourceIds.includes(source.source_id));
                        scope.sources.available = scope.sources.available.filter(source => !channelSourceIds.includes(source.source_id));

                        // scope.$emit('sourcesUpdated', [scope.sources]);
                        $scope.$emit('videosUpdated');
                        scope.updateSuccess = true;
                    });

                };

            }

        };
    })
    .filter('orderObjectBy', function() {
        return function(input, attribute) {
            if (!angular.isObject(input)) return input;

            var array = [];
            for (var objectKey in input) {
                array.push(input[objectKey]);
            }
            console.log();
            array.sort(function(a, b) {
                a = a[attribute];
                b = b[attribute];
                return a - b;
            });
            return array;
        }
    });