'use strict';

/**
 * @ngdoc function
 * @name cutvApiAdminApp.controller:ChannelCtrl
 * @description
 * # MainCtrl
 * Controller of the cutvApiAdminApp
 */
angular.module('cutvApiAdminApp')

.controller('ChannelVideosCtrl', function($scope, $rootScope, $http, $location, $routeParams, ChannelService) {

    // init
    $scope.options = [
        {
            name: 'Publish',
            value: 'publish'
        },
        {
            name: 'Unpublish',
            value: 'pending'
        },
        {
            name: 'Trash',
            value: 'trash'
        }
    ];

    $scope.selectedAction = $scope.options[0];
    $scope.managingVideos = false;
    $scope.selectedVideos = [];

    // ChannelService.getChannels($routeParams.channelId).then(channel => {
    //     $scope.channel = channel;
    //     $rootScope.channelName = channel.playlist_name;
    // }).then(() => {


    ChannelService.getChannelSources($routeParams.channelId).then((sources) => {
        $scope.sources = sources.map(source => {
            source.newSrc = 0;
            source.movePlaylists = false;
            source.query = '';
            return source;
        });

    });
    // });

    // editing functions
    $scope.manageSourceVideos = (source) => {

        source.managingVideos = !source.managingVideos;

        if (source.managingVideos) {
            ChannelService.getSourceVideos(source.source_id).then((videos) => {
                source.video_posts = videos;
                return $scope.sources.map(source => {


                    if (source.video_posts) {
                        return source.video_posts.map(video => {
                            video.selected = $scope.selectedVideos.includes(video.ID);
                            video.status = video.snaptube_vid ? 'published' : 'pending';
                            video.snaptube_status = $scope.getSnaptubeStatus(video);
                        });
                    }
                    return source;
                });
            });
        }
    };

    $scope.updateChannelVideos = () => {
        $scope.working = true;
        let action;
        switch ($scope.selectedAction.value) {
            case "pending":
                action = 'cutv_unpublish_snaptube_video';
                break;
            case "trash":
                action = 'cutv_trash_snaptube_video';
                break;
            default:
                action = 'cutv_convert_snaptube';
        }

        const query = {
            action: action,
            videos: $scope.selectedVideos
        };

        ChannelService.wpRequest(query).then(videos => {
            return $scope.sources.map(source => {
                if (source.video_posts) {
                    _.forEach(videos, video => {

                        if (action === 'cutv_trash_snaptube_video') {
                            source.video_posts = _.reject(source.video_posts, { ID: Number(video) });
                        } else if (video.ID) {
                            source.video_posts = source.video_posts.map(v => {
                                const vid = $scope.selectedVideos.includes(video.ID) && video.ID === v.ID ? video : v;
                                vid.snaptube_status = (action === 'cutv_unpublish_snaptube_video' && $scope.selectedVideos.includes(video.ID) && video.ID === v.ID) ? 'unpublished' : $scope.getSnaptubeStatus(vid);
                                return vid;
                            });

                            video.selected = false;
                            unselectVideo(video);
                        }

                    });
                    $scope.selectedVideos = [];
                    $scope.updateSource(source);
                }
                return source;
            });

        });

    };

    $scope.openSourceDialog = (source, action) => {


        switch (action) {
            // case 'edit':
            //     $http.get(`/wp-admin/post.php?post=${source.source_id}&action=edit`).then((res) => {
            //         console.log(JSON.parse(res))
            //     });
            //     $(`#${action}Sources_${source.source_id}`).find('.content').html(`<iframe src="/wp-admin/post.php?post=${source.source_id}&action=edit"></iframe>`);
            //   break;
            case 'run':
                $http.get(`/wp-admin/admin.php?page=wpvr&run_sources&ids=${source.source_id}`).then((res) => {
                    var el = document.createElement( 'html' );
                    el.innerHTML = res.data;
                    $(`#${action}Sources_${source.source_id}`).find('.content').html($(el.getElementsByClassName('wpvr_source_insights')).html());
                });
              break;
            default:
                // $(`#${action}Sources_${source.source_id}`).modal('show');

        }
                    $(`#${action}Sources_${source.source_id}`).modal('show')


    };
    $scope.closeSourceDialog = (source, action) => $(`#${action}Sources_${source.source_id}`).modal('hide');

    $scope.deleteSource = (source) => {
        const deletingSourceId = source.source_id;
        ChannelService.moveSourceVideos(deletingSourceId, source.newSrc, source.movePlaylists).then(() => {
            $scope.sources = _.reject($scope.sources, { source_id: deletingSourceId });
            // console.log('success');
        });
    };

    $scope.updateSource = (source) => {
        ChannelService.getChannelSources($routeParams.channelId).then((sources) => {

            const this_source = sources.filter(s => { return s.source_id === source.source_id })[0];

            // using a clone so the counts from channels don't overwrite these
            // needs to be an array of videos, not a total number, for selecting purpose
            source.source_video_counts = _.clone(this_source.source_video_counts);

            $scope.$emit('sourceVideosUpdated', sources);
            $scope.working = false;
        });
    };

    //video selection
    $scope.setSourceFilter = (source, filter) => {
        // get source's videos by status

        source.filter = _.merge(source.filter, filter);

        return source.source_video_counts[source.filter.status].map(id => {

            const this_video = source.video_posts ? source.video_posts.filter(v => v.ID === id)[0] : null;

            if (this_video) {
                $scope.toggleVideoSelected(this_video);
            } else {
                if ($scope.selectedVideos.includes(id)) {
                    unselectVideo(id, true);
                } else {
                    selectVideo(id, true);
                }
            }
        });
    };



    const selectVideo = (video, isID = false) => {
        const id = isID ? video : video.ID;
        if (!$scope.selectedVideos.includes(id)) {
            $scope.selectedVideos.push(id);
        }
    };

    const unselectVideo = (video, isID = false) => {
        const id = isID ? video : video.ID;
        if ($scope.selectedVideos.includes(id)) {
            $scope.selectedVideos.splice($scope.selectedVideos.indexOf(id), 1);
        }
    };

    $scope.toggleVideoSelected = (video) => {
        video.selected = !video.selected;
        if (video.selected) {
            selectVideo(video);
        } else if (!video.selected) {
            unselectVideo(video);
        }
    };

    // video status
    $scope.getSnaptubeStatus = (video) => {
        // pending: never published to snaptube (wpvr_video is a draft)
        // published: published to snaptube  (regardless of status, if it's in snaptube, it's published)
        // unpublished: published to snaptube but disabled (not on front-end, but still has post meta)
        let status = video.snaptube_vid ? 'published' : 'pending';
        if (video.post_status === 'pending') {
            status = 'unpublished';
        }
        return status;
    };

});
