let api = new API()
        .setCanvasApi(new CanvasAPIStudentMock())
        .setMsgBroker(new MsgBrokerMock())
        .setPersistence(new PersistenceMock())

window.program = new BasicSolution(api)
let program = window.program;
await program.defineUIElements();


