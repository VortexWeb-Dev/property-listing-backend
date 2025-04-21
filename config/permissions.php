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
        'company.view',
        'company.create',
        'company.edit',
        'company.delete',
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
        'property.delete',
    ],

    'owner' => [
        'property.view',
        'property.create',
        'property.edit',
        'property.delete',
    ],
];