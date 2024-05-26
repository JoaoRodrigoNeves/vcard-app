const httpServer = require("http").createServer();
const io = require("socket.io")(httpServer, {
    cors: {
        origin: "*", methods: ["GET", "POST"], credentials: true,
    },
});
httpServer.listen(8080, () => {
    console.log("listening on *:8080");
});

io.on("connection", (socket) => {

    socket.on('loggedIn', function (user) {
        socket.join(user.id)
        if (user.user_type === 'A') socket.join('administrator')
    })

    socket.on('loggedOut', function (user) {
        socket.leave(user.id)
        if (user.user_type === 'A') socket.leave('administrator')
    })

    socket.on('changedVcardStatus', function (vcard) {
        socket.in('administrator').except(parseInt(vcard.phone_number)).emit('changedVcardStatus', vcard)
        socket.in(parseInt(vcard.phone_number)).emit('changedVcardStatus', vcard)
    })

    socket.on('newTransaction', function (transaction, isAdmin) {

        if (isAdmin)
            socket.in(parseInt(transaction.vcard)).emit('newTransaction', transaction)
        else
            socket.in(parseInt(transaction.pair_vcard)).emit('newTransaction', transaction)
    })

    socket.on('newMoneyRequest', function (transaction) {
        socket.in(parseInt(transaction.payment_reference)).emit('newMoneyRequest', transaction)
    })

    socket.on('deletedVcard', function (vcard) {
        socket.in('administrator').except(parseInt(vcard.phone_number)).emit('deletedVcard', vcard)
        socket.in(parseInt(vcard.phone_number)).emit('deletedVcard', vcard)
    })
});