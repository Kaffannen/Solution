class CanvasAPI {

static getCourseId(){
    return window.location.pathname.split('/').filter(Boolean)[1];
}

static getAssignmentId(){
    return window.location.pathname.split('/').filter(Boolean)[3];
}

static getUserInfo(){
    return fetch('https://hvl.instructure.com/api/v1/users/self')
                .then(response => response.json());
}

static getCourseInfo(){
    return fetch(`https://hvl.instructure.com/api/v1/courses/${CanvasAPI.getCourseId()}`)
                .then(response => response.json());

}
static getAssignmentInfo(){
    return fetch(`https://hvl.instructure.com/api/v1/courses/${CanvasAPI.getCourseId()}/assignments/${CanvasAPI.getAssignmentId()}`)
                .then(response => response.json());
}

static getGroupMembers(){
    return fetch(`https://hvl.instructure.com/api/v1/courses/${CanvasAPI.getCourseId()}/assignments/${CanvasAPI.getAssignmentId()}/users/self/group_members`)
                .then(response => response.json());
}
static getAssignmentGroup(assignmentGroupId){
    return fetch(`https://hvl.instructure.com/api/v1/courses/${CanvasAPI.getCourseId()}/assignment_groups/${assignmentGroupId}`)
                .then(response => response.json());
}
}