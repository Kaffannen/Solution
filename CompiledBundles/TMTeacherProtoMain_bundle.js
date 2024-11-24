let api = new API()
        .setCanvasApi(new CanvasAPI('teacher'))
        .setMsgBroker(new MsgBrokerMock())
        .setPersistence(new PersistenceMock())

window.program = new BasicSolution(api)
let program = window.program;
await program.defineUIElements();

