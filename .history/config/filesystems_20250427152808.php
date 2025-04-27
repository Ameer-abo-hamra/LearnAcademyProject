<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL') . '/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],
        'teachers' => [
            'driver' => 'local',
            'root' => public_path('uploads/'),
            'url' => env('APP_URL') . '/uploads',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],
        'streamable_videos' => [
            'driver' => 'local',
            'root' => public_path('streamable_videos'),
            'url' => env('APP_URL') . '/streamable_videos',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],
        'course_image' => [
            'driver' => 'local',
            'root' => public_path('course_image'),
            'url' => env('APP_URL') . '/course_image',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],
        'teacher_image' => [
            'driver' => 'local',
            'root' => public_path('teacher_image'),
            'url' => env('APP_URL') . '/teacher_image',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],
        'student_image' => [
            'driver' => 'local',
            'root' => public_path('student_image'),
            'url' => env('APP_URL') . '/teacher_image',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],
        'video_extension' => [
            'driver' => 'local',
            'root' => public_path('video_extension'),
            'url' => env('APP_URL') . '/video_extension',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],
        'course_attachments' => [
            'driver' => 'local',
            'root' => public_path('course_attachments'),
            'url' => env('APP_URL') . '/course_attachments',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],
        'video_thumbnail' => [
            'driver' => 'local',
            'root' => public_path('video_thumbnail'),
            'url' => env('APP_URL') . '/video_thumbnail',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],
        'specialization_image' => [
            'driver' => 'local',
            'root' => public_path('specialization_image'),
            'url' => env('APP_URL') . '/specialization_image',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
