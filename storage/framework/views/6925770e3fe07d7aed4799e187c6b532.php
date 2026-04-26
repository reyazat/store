<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    
    <title><?php echo e(config('app.name', 'فروشگاه')); ?></title>
    
    <link rel="icon" type="image/x-icon" href="/images/Logo/favicon.ico">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/Logo/favicon-16x16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/Logo/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="/images/Logo/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="128x128" href="/images/Logo/favicon-128x128.png">
    <link rel="apple-touch-icon" href="/images/Logo/favicon-128x128.png">
    
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=vazirmatn:400,500,700&display=swap" rel="stylesheet" />
    
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/src/main.ts']); ?>
</head>
<body class="font-sans antialiased">
    <div id="app"></div>
</body>
</html>
<?php /**PATH /home/zahra/etka/resources/views/app.blade.php ENDPATH**/ ?>