<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>فتح المحتوى</title>
</head>
<body>
<p>جاري فتح تطبيق Nerd...</p>

<script>
    window.location.href = "{{ $deepLink }}";

    setTimeout(function () {
        window.location.href = "{{ $fallbackUrl }}";
    }, 5000);
</script>
</body>
</html>
