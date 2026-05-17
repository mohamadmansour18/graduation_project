<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>فتح الاختبار</title>
</head>
<body>
<p>جاري فتح تطبيق Nerd...</p>

<script>
    window.location.href = "{{ $deepLink }}";

    setTimeout(function () {
        window.location.href = "{{ $fallbackUrl }}";
    }, 3000);
</script>
</body>
</html>
