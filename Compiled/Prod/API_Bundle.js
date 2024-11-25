let api = new API()
        .setCanvasApi(new CanvasAPI())
        .setMsgBroker(new MsgBrokerMock())
        .setPersistence(new PersistenceMock())

window.program = new BasicSolution(api)
let program = window.program;
await program.defineUIElements();



class BasicSolution extends EzUI {
    async defineUIElements(){
        super.defineUIElements();
        await this.fetchBruker();
        return this;
    }

    static STATES = {
        INIT: function(){
        }
    };

    fetchBruker(){
        return program.getApi().onLoadInfo()
            .then(loadInfo=>{
                if (loadInfo.course.enrollments[0].role === "StudentEnrollment"){
                    let bruker = new Student(loadInfo,this)
                                    .defineUIElements()
                                    .setState(Student.STATES.COLLAPSED);
                                this.velgBruker(bruker);
                }
                else  {
                    let bruker = new Underviser(loadInfo,this)
                                    .defineUIElements()
                                    .setState(Underviser.STATES.COLLAPSED);
                                this.velgBruker(bruker);
                }

            })
            .catch(error =>console.log(error))
    }

    velgBruker(bruker){
        if (this.getFavourite()!==undefined)
            this.removeChild(this.getFavourite())
        super.setFavourite(bruker);
    }

    constructor(api) {
        super(api);
    }
}


class PersistenceMock {


}
class MsgBrokerMock {


}
class CanvasAPI {

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
                    return courseInfo
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
        fetch(`https://hvl.instructure.com/api/v1/courses/${this.getCourseId()}/users?page=1&per_page=200&enrollment_type=student`),
        fetch(`https://hvl.instructure.com/api/v1/courses/${this.getCourseId()}/users?page=1&per_page=200&enrollment_type=teacher`),
        fetch(`https://hvl.instructure.com/api/v1/courses/${this.getCourseId()}/users?page=1&per_page=200&enrollment_type=ta`)
    ]);

    // Convert response to JSON
    const students = await studentsResponse.json();
    const teachers = await teachersResponse.json();
    const assistants = await assistantsResponse.json();

    const studentgroups = [];

    // Process students
    for (let i = 0; i < students.length; i++) {
        // Fetch the group members for the current student
        const groupResponse = await fetch(`https://hvl.instructure.com/api/v1/courses/${this.getCourseId()}/assignments/${this.getAssignmentId()}/users/${students[i].id}/group_members`);
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

class API{
    #canvasApi;
    #msgBroker;
    #persistence;

setCanvasApi(canvasApi){
    this.#canvasApi = canvasApi;
    return this;
}
setMsgBroker(msgBroker){
    this.#msgBroker = msgBroker;
    return this;
}
setPersistence(persistence){
    this.#persistence = persistence;
    return this;
}

    onLoadInfo(){
        return Promise.all([
            this.#canvasApi.getUserInfo(),
            this.#canvasApi.getCourseInfo(),
            this.#canvasApi.getAssignmentInfo()])
        .then(([user, course, assignment]) => {
            return {
            id: user.id,
            user: user,
            course: course,
            assignment: assignment
        }
    })
    }
    fetchGroup(assignmentGroupId){
        return this.#canvasApi.getAssignmentGroup(assignmentGroupId)
    }
    fetchGroupMembers(selfId){
        return this.#canvasApi.getGroupMembers(selfId)
    }

    fetchObject(endpoint, rejectreason, body= null) {
        return new Promise((resolve, reject) => {
            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(body)
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(response.status.toString());
                    }
                    return response.json();
                })
                .then(body => {
                    resolve(body);
                })
                .catch(error => {
                    switch (error.message) {
                        case "401":
                            reject(error.message + ": " + rejectreason);
                            break;
                        default:
                            reject(error.message + ": Annen feil");
                    }
                });
        });
    }
    fetchGroups(assignmentId){
        return this.#canvasApi.fetchGroups()
    }
}



