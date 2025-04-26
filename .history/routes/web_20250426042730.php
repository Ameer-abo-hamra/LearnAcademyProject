<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::get('/clear-all', function () {
    // تنظيف الكاش (cache)
    Artisan::call('cache:clear');

    // تنظيف كاش الكود المجمع (config cache)
    Artisan::call('config:clear');

    // تنظيف الكاش الخاص بالتوجيهات (routes cache)
    Artisan::call('route:clear');

    // تنظيف الكاش الخاص بالعرض (views)
    Artisan::call('view:clear');

    // تنظيف كاش إعدادات Composer (إذا كنت بحاجة لذلك)
    Artisan::call('optimize:clear');

    // إرجاع استجابة لإعلامك أنه تم تنظيف كل شيء
    return 'Everything has been cleared!';
});


Route::get('/', function () {
    return view('test');
});
