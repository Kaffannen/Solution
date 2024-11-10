class API{

    //Til BrukerController
        regUser(credentials) {
            return this.fetchObject("bruker/registrer", "Bruker eksisterer fra før", credentials);
        }

        getDefaultbruker(){
        return new Promise((resolve, reject) => {
                try {
                    //const user = { id: 1, username: "Brukernavn! Du er jo en student", rolle: "student" };
                    const user = { id: 1, username: "Brukernavn! MockAPI'en sier at du er en lærer", rolle: "underviser" };
                    if (user) {
                        resolve(user);
                    } else {
                        throw new Error("User data not found");
                    }
                } catch (error) {
                    reject(error);
                }
            });
        }

        loginUser(credentials = undefined) {
            return this.fetchObject("bruker/login", "Feil brukernavn/passord",credentials)
        }

        logoutUser(){
            return this.fetchObject("bruker/logout","feil med logout");
        }

        deleteUser(credentials){
            return this.fetchObject("bruker/delete", "feil med delete",credentials)
        }

        //Til ChatRomController

        createChatRom(newRom){
            return this.fetchObject("chatterom/lag","en feil, kanskje navn er brukt?",newRom);
        }
        joinChatRom(romTema){
            return this.fetchObject("chatterom/join", "noe skar seg",romTema)
        }

        getChatterom(bruker){
            return this.fetchObject("chatterom/get","en feil", bruker)
                .then(body=>body)
        }

        //Til meldingcontroller
        postMelding(DTO){
            return this.fetchObject("melding/post", "noe trøbbel", DTO);
        }

        getMeldinger(chatrom){
            return this.fetchObject("melding/get","feil",chatrom)
                .then(body=>body)
        }
        getNewMeldinger(DTO){
            return this.fetchObject("melding/getlatest","feil med getnew", DTO)
        }

        deleteMelding(melding){
            return this.fetchObject("melding/delete","neh",melding);
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
