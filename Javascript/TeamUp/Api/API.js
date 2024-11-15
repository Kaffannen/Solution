//import CanvasAPIMock from "https://kaffannen.github.io/Solution/Javascript/TeamUp/Api/CanvasAPIMock.js";

//export default class API{
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
        let courseId = 29406
        let assignmentId = 80710
        return Promise.all([
            this.#canvasApi.getUserInfo(),
            this.#canvasApi.getCourseInfo(courseId),
            this.#canvasApi.getAssignmentInfo(assignmentId)])
        .then(([user, course, assignment]) => {
            return {
            id: user.id,
            user: user,
            course: course,
            assignment: assignment
        }
    })
    }
    fetchGroup(){
        return this.#canvasApi.getAssignmentGroup()
    }
    fetchGroupMembers(){
        return this.#canvasApi.getGroupMembers()
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

}




class CanvasMsgBrokerMock {

}
class CanvasDBMock {

}

