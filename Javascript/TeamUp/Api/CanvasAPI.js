class CanvasAPI {

constructor(roleOverride = "none"){
    if (roleOverride === "student")
        this.roleOverride = "StudentEnrollment"
    else if (roleOverride === "teacher")
        this.roleOverride = "TeacherEnrollment"
    else
        this.roleOverride = "none"
}

getCourseId(){
    return window.location.pathname.split('/').filter(Boolean)[1];
}

getAssignmentId(){
    return window.location.pathname.split('/').filter(Boolean)[3];
}

getUserInfo(){
    return fetch('https://hvl.instructure.com/api/v1/users/self')
                .then(response => response.json())
}

getCourseInfo(){
    return fetch(`https://hvl.instructure.com/api/v1/courses/${this.getCourseId()}`)
                .then(response => response.json())
                .then(courseInfo => {
                    if (courseInfo.enrollments && courseInfo.enrollments.length > 0 && this.roleOverride !== "none") {
                                    courseInfo.enrollments[0].role = this.roleOverride;
                                }
                })

}
getAssignmentInfo(){
    return fetch(`https://hvl.instructure.com/api/v1/courses/${this.getCourseId()}/assignments/${this.getAssignmentId()}`)
                .then(response => response.json());
}

getGroupMembers(selfId){
    return fetch(`https://hvl.instructure.com/api/v1/courses/${this.getCourseId()}/assignments/${this.getAssignmentId()}/users/${selfId}/group_members`)
                .then(response => response.json());
}
getAssignmentGroup(assignmentGroupId){
    return fetch(`https://hvl.instructure.com/api/v1/courses/${this.getCourseId()}/assignment_groups/${assignmentGroupId}`)
                .then(response => response.json());
}
async fetchGroups(assignmentId) {
    let course = 25563;
    let assignment = 75844;

    // Fetch all users (students, teachers, assistants)
    const [studentsResponse, teachersResponse, assistantsResponse] = await Promise.all([
        fetch(`https://hvl.instructure.com/api/v1/courses/${course}/users?page=1&per_page=200&enrollment_type=student`),
        fetch(`https://hvl.instructure.com/api/v1/courses/${course}/users?page=1&per_page=200&enrollment_type=teacher`),
        fetch(`https://hvl.instructure.com/api/v1/courses/${course}/users?page=1&per_page=200&enrollment_type=ta`)
    ]);

    // Convert response to JSON
    const students = await studentsResponse.json();
    const teachers = await teachersResponse.json();
    const assistants = await assistantsResponse.json();

    const studentgroups = [];

    // Process students
    for (let i = 0; i < students.length; i++) {
        // Fetch the group members for the current student
        const groupResponse = await fetch(`https://hvl.instructure.com/api/v1/courses/${course}/assignments/${assignment}/users/${students[i].id}/group_members`);
        let group = await groupResponse.json();

        // Remove the students that are part of this group
        group.forEach(student => {
            const index = students.findIndex(s => s.id === Number(student.id));
            if (index !== -1) {
                students.splice(index, 1);
            }
        });

        // Add the group to the studentgroups array
        group = {
            id: group[0].id,
            members : group
        }
        studentgroups.push(group);
    }
    return {
            studentgroups,
            teachers,
            assistants
            };
    }
}
