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
<script type="module">
    import Echo from 'laravel-echo';

    setTimeout(() => {
        window.Echo.channel('test')
            .listen('App\\Events\\TestEvent', (e) => {
                console.log(e);
            });
    }, 300);
</script>

<script>
    setTimeout(() => {
        Window.Echo.channel('test')
            .listen('app\Events\TestEvent.php', (e) => {
                console.log(e);
            })
    }, 300);
</script>

</html>
