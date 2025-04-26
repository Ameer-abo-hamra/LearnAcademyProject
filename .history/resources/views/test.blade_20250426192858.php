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
    <p>hi</p>
</body>


<script>
    setTimeout(() => {
        window.Echo.channel('teacher')
            .listen('TeacherEvent', (e) => {
                console.log(e.message);

            });

    }, 400);
</script>

</html>
