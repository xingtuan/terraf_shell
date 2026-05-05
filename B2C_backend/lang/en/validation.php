<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Custom messages for user-facing form validation. Field-level overrides
    | are defined here so the 422 responses carry readable copy that the
    | frontend can surface directly without additional mapping.
    |
    */

    'custom' => [
        'email' => [
            'required' => 'Email address is required.',
            'email'    => 'Please enter a valid email address.',
            'unique'   => 'This email is already registered. Try signing in instead.',
        ],
        'password' => [
            'required'  => 'Password is required.',
            'confirmed' => "Passwords don't match.",
            'min'       => 'Password must be at least 8 characters.',
        ],
        'name' => [
            'required' => 'Please enter your full name.',
            'max'      => 'Name must be :max characters or fewer.',
        ],
        'company_name' => [
            'required' => 'Company name is required.',
        ],
        'message' => [
            'required' => 'Project details are required.',
        ],
        'company_website' => [
            'url' => 'Please enter a valid website URL (including https://).',
        ],
        'recipient_name' => [
            'required' => 'Recipient name is required.',
        ],
        'address_line1' => [
            'required' => 'Address is required.',
        ],
        'city' => [
            'required' => 'City is required.',
        ],
        'country' => [
            'required' => 'Country is required.',
            'size'     => 'Country must be a 2-letter ISO code.',
        ],
        'collaboration_goal' => [
            'required' => 'Collaboration goal is required.',
        ],
        'material_interest' => [
            'required' => 'Material interest is required.',
        ],
        'intended_use' => [
            'required' => 'Intended use is required.',
        ],
        'inquiry_type' => [
            'required' => 'Application is required.',
        ],
    ],

    'attributes' => [
        'email'              => 'email address',
        'password'           => 'password',
        'name'               => 'name',
        'company_name'       => 'company name',
        'message'            => 'project details',
        'company_website'    => 'company website',
        'recipient_name'     => 'recipient name',
        'address_line1'      => 'address',
        'collaboration_goal' => 'collaboration goal',
        'material_interest'  => 'material interest',
        'intended_use'       => 'intended use',
        'inquiry_type'       => 'application',
    ],
];
