<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفعيل الحساب</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #e9ecef;
            margin: 0;
            padding: 0;
            direction: rtl;
        }

        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            padding: 30px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #2c3e50;
            text-align: center;
            font-size: 28px;
            margin-bottom: 20px;
        }

        .content {
            font-size: 18px;
            color: #555;
            line-height: 1.7;
            margin-top: 20px;
            text-align: right;
        }

        .code {
            display: block;
            text-align: center;
            font-size: 24px;
            background-color: #f1f3f5;
            padding: 12px 20px;
            margin: 20px 0;
            font-weight: bold;
            border-radius: 8px;
            color: #007bff;
            letter-spacing: 2px;
        }

        .footer {
            text-align: center;
            font-size: 14px;
            color: #888;
            margin-top: 40px;
        }

        .footer a {
            color: #007bff;
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        .btn {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 12px 25px;
            font-size: 16px;
            border-radius: 6px;
            text-decoration: none;
            text-align: center;
            margin-top: 30px;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

    <div class="container">
        <h1>مرحباً، {{ $user->username }}</h1>

        <div class="content">
            <p>شكرًا لتسجيلك في موقعنا. لتفعيل حسابك، يرجى إدخال الكود التالي:</p>
            <span class="code">{{ $activationCode }}</span>
            <p>إذا لم تكن قد طلبت هذا، يمكنك تجاهل هذه الرسالة.</p>
            <p>للأسئلة أو الدعم، لا تتردد في <a href="mailto:support@example.com">التواصل معنا</a>.</p>
        </div>

        <div class="footer">
            <p>© 2025 جميع الحقوق محفوظة لموقعنا</p>
            <a href="#">إلغاء الاشتراك</a>
        </div>

        <a href="#" class="btn">تفعيل الحساب</a>
    </div>

</body>
</html>
