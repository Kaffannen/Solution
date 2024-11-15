let program;
document.addEventListener("DOMContentLoaded", async function () {
    let api = new API()
        .setCanvasApi(CanvasAPIMock)
        .setMsgBroker(MsgBrokerMock)
        .setPersistence(PersistenceMock)
    window.program = new BasicSolution(api)
    program = window.program;
    await program.defineUIElements();
    program.getFavourite().setState(Student.STATES.EXPANDED);
});