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
        'course.view',
        'course.create',
        'course.edit',
        'course.delete',
        'lesson.show',
        'lesson.create',
        'lesson.edit',
        'lesson.delete',
        'tag.view',
        'tag.create',
        'tag.edit',
        'tag.delete',
    ],

    'admin' => [
        'user.view',
        'user.create',
        'user.edit',
        'user.delete',
        'property.view',
        'property.create',
        'property.edit',
        'course.view',
        'tag.view',
    ],

    'agent' => [
        'property.view',
        'property.create',
        'property.edit',
        'property.delete',
        'course.view',
        'tag.view',
    ],

    'owner' => [
        'property.view',
        'property.create',
        'property.edit',
        'property.delete',
        'course.view',
        'tag.view',
    ],
];