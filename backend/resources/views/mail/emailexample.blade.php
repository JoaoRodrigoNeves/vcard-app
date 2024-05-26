<!DOCTYPE html>
<html>
<head>
    <title>Seu Assunto Aqui</title>
</head>
<body>
<p>Olá, {{$name}}!</p>
<p>Para teres acesso à tua conta de administrador clica no botão abaixo e repõe a tua palavra passe</p>
<div style="text-align: center;">
    <a href="{{ url('http://172.22.21.113/admin/set-password/' . $emailToken) }}"
       style="display: inline-block; padding: 10px 20px; background-color: #f16758; color: #ffffff; text-decoration: none; border-radius: 5px;">Clique
        Aqui</a>
</div>
</body>
</html>
