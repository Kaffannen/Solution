let program;
document.addEventListener("DOMContentLoaded", async function () {
    let api = new API()
        .setCanvasApi(new CanvasAPIMock())
        .setMsgBroker(new MsgBrokerMock())
        .setPersistence(new PersistenceMock())
    window.program = new BasicSolution(new API())
    program = window.program;
    await program.defineUIElements();
    program.getFavourite().setState(Student.STATES.EXPANDED);
});