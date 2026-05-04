<?php

return [
    'custom' => [
        'email' => [
            'required' => '请输入电子邮件地址。',
            'email'    => '请输入有效的电子邮件地址。',
            'unique'   => '该邮箱已注册，请尝试登录。',
        ],
        'password' => [
            'required'  => '请输入密码。',
            'confirmed' => '两次密码输入不一致。',
            'min'       => '密码长度不得少于8个字符。',
        ],
        'name' => [
            'required' => '请输入姓名。',
            'max'      => '姓名不得超过 :max 个字符。',
        ],
        'company_name' => [
            'required' => '请输入公司名称。',
        ],
        'message' => [
            'required' => '请填写项目详情。',
        ],
        'company_website' => [
            'url' => '请输入有效的网站地址（包含 https://）。',
        ],
        'recipient_name' => [
            'required' => '请输入收件人姓名。',
        ],
        'address_line1' => [
            'required' => '请输入地址。',
        ],
        'city' => [
            'required' => '请输入城市。',
        ],
        'country' => [
            'required' => '请输入国家。',
            'size'     => '国家代码必须为两位 ISO 代码。',
        ],
        'collaboration_goal' => [
            'required' => '请填写合作目标。',
        ],
        'material_interest' => [
            'required' => '请填写感兴趣的材料。',
        ],
        'intended_use' => [
            'required' => '请填写用途说明。',
        ],
        'inquiry_type' => [
            'required' => '请填写应用领域。',
        ],
    ],

    'attributes' => [
        'email'              => '电子邮件地址',
        'password'           => '密码',
        'name'               => '姓名',
        'company_name'       => '公司名称',
        'message'            => '项目详情',
        'company_website'    => '公司网站',
        'recipient_name'     => '收件人姓名',
        'address_line1'      => '地址',
        'collaboration_goal' => '合作目标',
        'material_interest'  => '感兴趣的材料',
        'intended_use'       => '用途说明',
        'inquiry_type'       => '应用领域',
    ],
];
