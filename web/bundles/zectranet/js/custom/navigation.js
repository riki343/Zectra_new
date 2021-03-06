Zectranet.controller('NavigationController', ['$scope', '$http', '$rootScope', function($scope, $http, $rootScope){

	var title = $('title');
	var titleValue = title.text();
	var notificationsGetUrl = JSON_URLS.notificationsGet;
	var officeShowUrlBase = JSON_URLS.officeShow;
	var projectShowUrlBase = JSON_URLS.projectShow;
	var taskShowUrlBase = JSON_URLS.taskShow;
    var acceptRequestUserOffice = JSON_URLS.acceptRequestUserOffice;
    var declineRequestUserOffice = JSON_URLS.declineRequestUserOffice;
    var acceptRequestUserProject = JSON_URLS.acceptRequestUserProject;
    var declineRequestUserProject = JSON_URLS.declineRequestUserProject;
    var acceptRequestOfficeProject = JSON_URLS.acceptRequestOfficeProject;
    var declineRequestOfficeProject = JSON_URLS.declineRequestOfficeProject;
    var notificationsInitialized = false;

    $scope.requests = [];
    $scope.notifications = null;
	$scope.notifyHandler = null;
    $scope.notificationsLength = null;

	function Notify() {
		title.text((title.text() == titleValue) ? 'Incoming notifications!' : titleValue);
	}

	function StartNotify() {
		$scope.notifyHandler = setInterval(Notify, 3000);
	}

	function StopNotify() {
		clearInterval($scope.notifyHandler);
		title.text(titleValue);
		$scope.notifyHandler = null;
	}

	$scope.getNotification =  function getNotifications() {
		$http.get(notificationsGetUrl)
			.success(function(response) {
                if (response.result.requests && response.result.requests.length > 0){
                    $scope.requests = response.result.requests;
                } else {
                    $scope.requests = [];
                }

                if (response.result.notifications && response.result.notifications.length > 0){
                    if (response.result.notifications.length != $scope.notificationsLength){

                        StartNotify();
                        if (notificationsInitialized) {
                            document.getElementById('notif_sound').play();
                        }

                        var chatUpdate = false;
                        $scope.notifications = prepareNotifications(response.result.notifications);

						for (var i = 0; i < $scope.notifications.length; i++) {
							if ($scope.notifications[i].type == 'message_office'
								|| $scope.notifications[i].type == 'message_project'
								|| $scope.notifications[i].type == 'message_task'
								|| $scope.notifications[i].type == 'message_epic_story'
								|| $scope.notifications[i].type == 'private_message_office'
								|| $scope.notifications[i].type == 'private_message_project'
								|| $scope.notifications[i].type == 'private_message_task'
								|| $scope.notifications[i].type == 'private_message_epic_story'
							) {
								chatUpdate = true;
							}
						}

                        if (chatUpdate && notificationsInitialized) {
                            $rootScope.updateChat(0, 100);
                        }

                        $scope.notificationsLength = response.result.notifications.length;
						$rootScope.NOTIFICATIONS = $scope.notifications;
                    }
                } else {
					StopNotify();
					$scope.notifications = [];
				}
                notificationsInitialized = true;
				setTimeout(getNotifications, 5000);
			}
        );
    };

	function prepareNotifications(notifications) {
		notifications = _.map(notifications, function(n) {
			if (["message_office", "request_office", "private_message_office"].indexOf(n.type) != -1)
                n.href = officeShowUrlBase.replace('0', n.destinationid);
            else if (["message_task", "request_assign_task", "private_message_task"].indexOf(n.type) != -1)
                n.href = taskShowUrlBase.replace('0', n.destinationid);
            else {
				n.href = projectShowUrlBase.replace('0', n.destinationid);
			}

			return n;
		});

		return notifications;
	}

    $scope.acceptRequestUserOffice = function(office_id){
        window.location.href = acceptRequestUserOffice.replace('0', office_id);
    };

    $scope.acceptRequestUserProject = function(project_id){
        window.location.href = acceptRequestUserProject.replace('0', project_id);
    };

    $scope.acceptRequestOfficeProject = function(project_id, office_id){
        window.location.href = acceptRequestOfficeProject
            .replace('0', 'project_id')
            .replace('1', 'office_id')
            .replace('project_id', project_id)
            .replace('office_id', office_id);
    };

    $scope.declineRequestUserOffice = function(office_id){
        window.location.href = declineRequestUserOffice.replace('0', office_id);
    };

    $scope.declineRequestUserProject = function(project_id){
        window.location.href = declineRequestUserProject.replace('0', project_id);
    };

    $scope.declineRequestOfficeProject = function(project_id){
        window.location.href = declineRequestOfficeProject.replace('0', project_id);
    };

    console.log('NavigationController was loaded!');

}]);