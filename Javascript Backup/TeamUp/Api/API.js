class API {
    #canvasApi;
    #msgBroker;
    #persistence;

    setCanvasApi(canvasApi) {
        this.#canvasApi = canvasApi;
        return this;
    }
    setMsgBroker(msgBroker) {
        this.#msgBroker = msgBroker;
        return this;
    }
    setPersistence(persistence) {
        this.#persistence = persistence;
        return this;
    }

    onLoadInfo() {
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
    fetchGroup(assignmentGroupId) {
        return this.#canvasApi.getSelfGroup(assignmentGroupId)
    }
    fetchGroupMembers(selfId) {
        return this.#canvasApi.getGroupMembers(selfId)
    }

    fetchObject(endpoint, rejectreason, body = null) {
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
    fetchGroups(assignmentId) {
        return this.#canvasApi.fetchGroups()
    }
}



