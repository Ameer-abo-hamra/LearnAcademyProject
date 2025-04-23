<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::get('/clear-all', function () {
    // تنظيف الكاش (cache)
    Artisan::call('cache:clear');

    // تنظيف كاش الكود المجمع (config cache)
    Artisan::call('config:clear');

    // تنظيف كاش الجلسات (session cache)
    Artisan::call('session:clear');

    // تنظيف الكاش الخاص بالتوجيهات (routes cache)
    Artisan::call('route:clear');

    // تنظيف الكاش الخاص بالعرض (views)
    Artisan::call('view:clear');

    // تنظيف الكاش الخاص بالترجمة (translations)
    Artisan::call('translation:clear'); // إذا كنت تستخدم الترجمة المخصصة

    // تنظيف كاش إعدادات Composer (إذا كنت بحاجة لذلك)
    Artisan::call('optimize:clear');

    // إرجاع استجابة لإعلامك أنه تم تنظيف كل شيء
    return 'Everything has been cleared!';
});

Route::get('/', function () {
    return view('welcome');
});
