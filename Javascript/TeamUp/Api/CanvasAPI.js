class CanvasAPI {

getCourseId(){
    return window.location.pathname.split('/').filter(Boolean)[1];
}

getAssignmentId(){
    return window.location.pathname.split('/').filter(Boolean)[3];
}

getUserInfo(){
    return fetch('https://hvl.instructure.com/api/v1/users/self')
                .then(response => response.json());
}

getCourseInfo(){
    return fetch(`https://hvl.instructure.com/api/v1/courses/${CanvasAPI.getCourseId()}`)
                .then(response => response.json());

}
getAssignmentInfo(){
    return fetch(`https://hvl.instructure.com/api/v1/courses/${CanvasAPI.getCourseId()}/assignments/${CanvasAPI.getAssignmentId()}`)
                .then(response => response.json());
}

getGroupMembers(selfId){
    return fetch(`https://hvl.instructure.com/api/v1/courses/${CanvasAPI.getCourseId()}/assignments/${CanvasAPI.getAssignmentId()}/users/${selfId}/group_members`)
                .then(response => response.json());
}
getAssignmentGroup(assignmentGroupId){
    return fetch(`https://hvl.instructure.com/api/v1/courses/${CanvasAPI.getCourseId()}/assignment_groups/${assignmentGroupId}`)
                .then(response => response.json());
}
}