<!DOCTYPE html>
<html lang="en">

<head>
    @vite('resources/js/app.js')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>

<body>

</body>


<script>
    setTimeout(() => {
        console.log("hi")
        Window.Echo.channel('test')
            .listen('app\Events\TestEvent.php', (e) => {
                console.log(e);
            })
    }, 9000);
</script>

</html>
