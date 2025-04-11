<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفعيل الحساب</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #333;
            text-align: center;
            font-size: 24px;
        }

        .content {
            font-size: 16px;
            color: #555;
            line-height: 1.6;
            margin-top: 20px;
        }

        .code {
            display: block;
            text-align: center;
            font-size: 22px;
            background-color: #f4f4f4;
            padding: 10px;
            margin: 20px 0;
            font-weight: bold;
            border-radius: 6px;
        }

        .footer {
            text-align: center;
            font-size: 14px;
            color: #888;
            margin-top: 30px;
        }

        .footer a {
            color: #007bff;
            text-decoration: none;
        }
    </style>
</head>
<body>

    <div class="container">
        <h1>مرحباً، {{ $user->user }}</h1>

        <div class="content">
            <p>شكرًا لتسجيلك في موقعنا. لتفعيل حسابك، يرجى إدخال الكود التالي:</p>
            <span class="code">{{ $activationCode }}</span>
            <p>إذا لم تكن قد طلبت هذا، يمكنك تجاهل هذه الرسالة.</p>
        </div>

    </div>

</body>
</html>
