<?php

return [
    'custom' => [
        'email' => [
            'required' => '이메일 주소를 입력해 주세요.',
            'email'    => '유효한 이메일 주소를 입력해 주세요.',
            'unique'   => '이미 등록된 이메일입니다. 로그인을 시도해 보세요.',
        ],
        'password' => [
            'required'  => '비밀번호를 입력해 주세요.',
            'confirmed' => '비밀번호가 일치하지 않습니다.',
            'min'       => '비밀번호는 최소 8자 이상이어야 합니다.',
        ],
        'name' => [
            'required' => '이름을 입력해 주세요.',
            'max'      => '이름은 :max자를 초과할 수 없습니다.',
        ],
        'company_name' => [
            'required' => '회사명을 입력해 주세요.',
        ],
        'message' => [
            'required' => '프로젝트 세부 정보를 입력해 주세요.',
        ],
        'company_website' => [
            'url' => '유효한 웹사이트 URL을 입력해 주세요 (https:// 포함).',
        ],
        'recipient_name' => [
            'required' => '수령인 이름을 입력해 주세요.',
        ],
        'address_line1' => [
            'required' => '주소를 입력해 주세요.',
        ],
        'city' => [
            'required' => '도시를 입력해 주세요.',
        ],
        'country' => [
            'required' => '국가를 입력해 주세요.',
            'size'     => '국가는 ISO 2자리 코드여야 합니다.',
        ],
        'collaboration_goal' => [
            'required' => '협업 목표를 입력해 주세요.',
        ],
        'material_interest' => [
            'required' => '관심 소재를 입력해 주세요.',
        ],
        'intended_use' => [
            'required' => '사용 목적을 입력해 주세요.',
        ],
        'inquiry_type' => [
            'required' => '응용 분야를 입력해 주세요.',
        ],
    ],

    'attributes' => [
        'email'              => '이메일 주소',
        'password'           => '비밀번호',
        'name'               => '이름',
        'company_name'       => '회사명',
        'message'            => '프로젝트 내용',
        'company_website'    => '회사 웹사이트',
        'recipient_name'     => '수령인 이름',
        'address_line1'      => '주소',
        'collaboration_goal' => '협업 목표',
        'material_interest'  => '관심 소재',
        'intended_use'       => '사용 목적',
        'inquiry_type'       => '응용 분야',
    ],
];
