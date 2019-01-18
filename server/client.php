<?php

?>
<!DOCTYPE html>
<html>
<head>
	<title>Чат</title>
</head>
<body>
	<button id="PIN">PIN</button>

	<script type="text/javascript">
		socket = new WebSocket("ws://127.0.0.1:8000");
        var packet = JSON.stringify({
			user:"tester01",
			message:"from browser"
		});

        socket.onopen = function() {
		  	console.log("Соединение установлено.");
		};

		socket.onclose = function(event) {
		  	if (event.wasClean) {
		    	console.log('Соединение закрыто чисто');
		  	} else {
		    	console.log('Обрыв соединения'); // например, "убит" процесс сервера
		  	}
		  	console.log('Код: ' + event.code + ' причина: ' + event.reason);
		};

        socket.onmessage = function(evt) {
        	console.log("Получены данные: "+evt.data);
        };

        socket.onerror = function(error) {
		  	console.log("Ошибка " + error.message);
		};
		
		var button = document.getElementById("PIN");

		button.onclick = function(){
			console.log(socket);
			socket.send(packet);
		};
	</script>
</body>
</html>