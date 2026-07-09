<?php

// Curated module bundles for the interactive `app:install` command.
// `modules` is a list of package_name slugs (matching each package's
// module.json `package_name`), or `null` to mean "everything".
//
// These are a starting recommendation, not derived automatically — review
// and adjust per bundle as the product evolves. Add more presets here
// without touching any installer code.

return [
    'full' => [
        'label' => 'Full Suite (all modules)',
        'modules' => null,
    ],
    'hr' => [
        'label' => 'HR Only',
        'modules' => [
            'account', 'product-service', 'hrm', 'recruitment',
            'performance', 'timesheet', 'training', 'calendar',
        ],
    ],
    'sales' => [
        'label' => 'Sales & CRM',
        'modules' => [
            'account', 'product-service', 'pos', 'lead',
            'quotation', 'form-builder',
        ],
    ],
    'real-estate' => [
        'label' => 'Real Estate Brokerage',
        'modules' => [
            'account', 'product-service', 'hrm', 'lead',
            'quotation', 'contract', 'real-estate',
        ],
    ],
    'restaurant' => [
        'label' => 'Restaurant',
        'modules' => [
            'account', 'product-service', 'pos', 'restaurant',
        ],
    ],
];
