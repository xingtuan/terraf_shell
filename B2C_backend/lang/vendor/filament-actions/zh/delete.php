<?php

return [

    'single' => [

        'label' => '删除',

        'modal' => [

            'heading' => '删除 :label',

            'actions' => [

                'delete' => [
                    'label' => '删除',
                ],

            ],

        ],

        'notifications' => [

            'deleted' => [
                'title' => '已删除',
            ],

        ],

    ],

    'multiple' => [

        'label' => '删除所选',

        'modal' => [

            'heading' => '删除所选 :label',

            'actions' => [

                'delete' => [
                    'label' => '删除',
                ],

            ],

        ],

        'notifications' => [

            'deleted' => [
                'title' => '已删除',
            ],

            'deleted_partial' => [
                'title' => '已删除 :count / :total 条',
                'missing_authorization_failure_message' => '您没有权限删除 :count 条记录。',
                'missing_processing_failure_message' => ':count 条记录无法删除。',
            ],

            'deleted_none' => [
                'title' => '删除失败',
                'missing_authorization_failure_message' => '您没有权限删除 :count 条记录。',
                'missing_processing_failure_message' => ':count 条记录无法删除。',
            ],

        ],

    ],

];
