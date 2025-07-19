<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>إعادة تعيين كلمة المرور</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .code { font-size: 24px; font-weight: bold; color: #3490dc; }
    </style>
</head>
<body>
    <div class="container">
        <h2>مرحباً!</h2>
        <p>لقد تلقيت هذا البريد لأنك طلبت إعادة تعيين كلمة المرور لحسابك.</p>
        
        <p>كود التحقق الخاص بك هو:</p>
        <div class="code">{{ $code }}</div>
        
        <p>هذا الكود صالح لمدة 30 دقيقة فقط.</p>
        
        <p>إذا لم تطلب إعادة تعيين كلمة المرور، يمكنك تجاهل هذا البريد.</p>
        
        <p>مع تحياتي،<br>
        Code zen فريق </p>
    </div>
</body>
</html>