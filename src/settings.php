<?php
include('referrals.php');
return [
    'settings' => [
        'displayErrorDetails' => false, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header
        'bail' =>
        [
            'bail_enabled' => false,
            'api' => getenv('BAIL_API'),
            'origin' => getenv('BAIL_ORIGIN'),
        ],
        'tile' => '/usr/local/bin/tile', // Location of the freesewing tile binary

        // Middleware settings
        'jwt' => [
            "secure" => true, // Don't allow access over an unencrypted connection
            'path' => '/',
            'passthrough' => [
                '/signup', 
                '/login', 
                '/recover', 
                '/reset', 
                '/activate', 
                '/resend',
                '/confirm',
                '/info/',
                '/shared/',
                '/download/',
                '/referral',
                '/comments/',
                '/status', 
                '/email/', 
                '/referrals/group', 
                '/debug', 
                '/patrons/list',
                '/error',
                '/errors',
                '/errors/all',
            ],
            'attribute' => 'jwt',
            'secret' => getenv("JWT_SECRET"),
            'lifetime' => "1 month",
            "error" => function ($request, $response, $arguments) {
                echo file_get_contents(dirname(__DIR__).'/templates/index.html');
            }
        ],
        
        // Renderer settings
        'renderer' => [
            'template_path' => dirname(__DIR__) . '/templates/',
        ],

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => getenv('LOG_FILE'),
            'level' => \Monolog\Logger::DEBUG,
        ],
        'testlogger' => [
            'name' => 'slim-app',
            'path' => '/tmp/data.freesewing.test.log',
            'level' => \Monolog\Logger::DEBUG,
        ],
        
        // Database
        'db' => [
            'type' => 'mariadb',
            'host' => getenv('DB_HOST'),
            'database' => getenv('DB_DB'),
            'user' => getenv('DB_USER'),
            'password' => getenv('DB_PASS'),
        ],
        'testdb' => [
            'type' => 'sqlite',
            'database' => __DIR__.'/../tests/sql/test.sq3',
        ],
        
        // Mailgun
        'mailgun' => [
            'api_key' => getenv('MAILGUN_KEY'),
            'template_path' => dirname(__DIR__) . '/templates/email',
            'instance' => getenv('MAILGUN_INSTANCE'),
        ],
        
        // SEPs (shitty email providers - basically Microsoft domains) will not deliver
        // MailGun messages, so we send email through GMAIL for these domains
        // using SwiftMailer
        'swiftmailer' => [
            'domains' => [
                'btinternet.com',
                'hotmail.be',
                'hotmail.de',
                'hotmail.fr',
                'hotmail.com',
                'hotmail.co.uk',
                'live.ca',
                'live.com',
                'live.co.uk',
                'live.com.au',
                'live.nl',
                'msn.com',
                'outlook.com',
                'snkmail.com',
                'yahoo.com',
                'yahoo.co.uk',
                'yahoo.co.nz',
                'yahoo.de',
                'yahoo.fr',
                'ymail.com',
            ],
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'encryption' => 'tls',
            'username' =>  getenv('GMAIL_USER'),
            'password' =>  getenv('GMAIL_SECRET'),
        ],

        // Storage settings
        'storage' => [
            'static_path' => dirname(__DIR__) . '/public/static',
            'temp_path' => '/tmp',
        ],
        'teststorage' => [
            'static_path' => '/tmp',
            'temp_path' => '/tmp',
        ],

        // App settings
        'app' => [
            'data_api' => getenv('DATA_API'),
            'core_api' => getenv('CORE_API'),
            'site' => getenv('SITE'),
            'origin' => getenv('ORIGIN'),
            'user_status' => ['active', 'inactive', 'blocked'],
            'user_role' => ['user', 'moderator', 'admin'],
            'handle_type' => ['user', 'model', 'draft'],
            'static_path' => '/static',
            'female_measurements' => ['underBust'],
            'motd' => '
**Tip**: These are your notes.
You can write whatever you want here.',  
        ],
        'badges' => [
            'login' => '2018',
        ],
        'patrons' => [
            'tiers' => [2,4,8],
        ],

        // Migration settings
        'mmp' => [
            'public_path' => 'https://makemypattern.com/sites/default/files/styles/user_picture/public',
        ],
        
        // Measurement titles
        'measurements' => [
            'acrossBack' => 'Across back',
            'bicepsCircumference' => 'Biceps circumference',
            'bustSpan' => 'Bust span',
            'centerBackNeckToWaist' => 'Centerback neck to waist',
            'chestCircumference' => 'Chest circumference',
            'headCircumference' => 'Head circumference',
            'highBust' => 'High bust',
            'highPointShoulderToBust' => 'High point shoulder to bust',
            'hipsCircumference' => 'Hips circumference',
            'hipsToUpperLeg' => 'Hips to upper leg',
            'inseam' => 'Inseam',
            'naturalWaist' => 'Natural waist',
            'naturalWaistToFloor' => 'Natural waist to floor',
            'naturalWaistToHip' => 'Natural waist to hip',
            'naturalWaistToSeat' => 'Natural waist to seat',
            'naturalWaistToUnderbust' => 'Natural waist to underbust',
            'neckCircumference' => 'Neck circumference',
            'seatCircumference' => 'Seat circumference',
            'seatDepth' => 'Seat depth',
            'shoulderSlope' => 'Shoulder slope',
            'shoulderToElbow' => 'Shoulder to elbow',
            'shoulderToShoulder' => 'Shoulder to shoulder',
            'shoulderToWrist' => 'Shoulder to wrist',
            'underBust' => 'Underbust',
            'upperLegCircumference' => 'Upper leg circumference',
            'wristCircumference' => 'Wrist circumference',
        ],

        // Referral groups
        'referrals' => getReferralGroups(),
    ],
];
