<?php

return [

    'super_admin' => [
        'user.view',
        'user.create',
        'user.edit',
        'user.delete',
        'property.view',
        'property.create',
        'property.edit',
        'property.delete',
        'settings.manage',
    ],

    'admin' => [
        'user.view',
        'user.create',
        'user.edit',
        'user.delete',
        'property.view',
        'property.create',
        'property.edit',
    ],

    'agent' => [
        'property.view',
        'property.create',
        'property.edit',
    ],

];