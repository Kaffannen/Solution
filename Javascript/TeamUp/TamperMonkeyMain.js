let api = new API()
        .setCanvasApi(CanvasAPI)
        .setMsgBroker(MsgBrokerMock)
        .setPersistence(PersistenceMock)

window.program = new BasicSolution(api)
let program = window.program;
await program.defineUIElements();


