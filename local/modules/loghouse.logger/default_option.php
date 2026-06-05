<?php

return [
    'formatter'          => 'text',
    'channel'            => 'app',
    'min_level'          => 'debug',
    'target_file'        => 'Y',
    'target_db'          => 'N',
    'target_telegram'    => 'N',
    'file_dir'           => '/logs',
    'file_min_level'     => 'debug',
    'file_rotation'      => 'day',
    'file_max_files'     => '30',
    'db_min_level'       => 'info',
    'telegram_token'     => '',
    'telegram_chat_id'   => '',
    'telegram_min_level' => 'error',
];