<?php

return [
    'textbox' => [
        'migration' => "\$table->string('{field}');",
        'migration_nullable' => "\$table->string('{field}')->nullable();",
        'fillable' => true,
        'validation' => 'required|string|max:255',
        'validation_nullable' => 'nullable|string|max:255',
        'component' => 'Input',
        'import' => 'Input',
        'props' => [
            'placeholder' => 'Enter {field_label}',
            'required' => true
        ],
        'faker' => 'fake()->words(2, true)'
    ],
    'email' => [
        'migration' => "\$table->string('{field}');",
        'migration_nullable' => "\$table->string('{field}')->nullable();",
        'fillable' => true,
        'validation' => 'required|email|max:255',
        'validation_nullable' => 'nullable|email|max:255',
        'component' => 'Input',
        'import' => 'Input',
        'props' => [
            'type' => 'email',
            'placeholder' => 'Enter {field_label}',
            'required' => true
        ],
        'faker' => 'fake()->email()'
    ],
    'textarea' => [
        'migration' => "\$table->longText('{field}');",
        'migration_nullable' => "\$table->longText('{field}')->nullable();",
        'fillable' => true,
        'validation' => 'required|string',
        'validation_nullable' => 'nullable|string',
        'component' => 'Textarea',
        'import' => 'Textarea',
        'props' => [
            'placeholder' => 'Enter {field_label}',
            'rows' => 3
        ],
        'faker' => 'fake()->sentence(10)'
    ],
    'select' => [
        'migration' => "\$table->boolean('{field}')->default(true);",
        'fillable' => true,
        'validation' => 'boolean',
        'cast' => 'boolean',
        'component' => 'Select',
        'import' => 'Select, SelectContent, SelectItem, SelectTrigger, SelectValue',
        'options' => ['1' => 'Active', '0' => 'Inactive'],
        'render_table' => 'status_badge',
        'faker' => 'fake()->boolean(80)'
    ],
    'radiobutton' => [
        'migration' => "\$table->string('{field}')->default('0');",
        'fillable' => true,
        'validation' => 'required|string',
        'validation_nullable' => 'nullable|string',
        'component' => 'RadioGroup',
        'import' => 'RadioGroup, RadioGroupItem',
        'render_table' => 'radio_options',
        'faker' => 'fake()->randomElement(["0", "1"])'
    ],
    'checkbox' => [
        'migration' => "\$table->boolean('{field}')->default(false);",
        'fillable' => true,
        'validation' => 'boolean',
        'cast' => 'boolean',
        'component' => 'Checkbox',
        'import' => 'Checkbox',
        'render_table' => 'checkbox_badge',
        'faker' => 'fake()->boolean(70)'
    ],
    'checkboxgroup' => [
        'migration' => "\$table->json('{field}')->nullable();",
        'fillable' => true,
        'validation' => 'nullable|array',
        'validation_nullable' => 'nullable|array',
        'cast' => 'array',
        'component' => 'CheckboxGroup',
        'import' => 'Checkbox',
        'render_table' => 'checkbox_group_list',
        'faker' => 'fake()->randomElements(["0", "1", "2"], fake()->numberBetween(0, 3))'
    ],
    'datepicker' => [
        'migration' => "\$table->date('{field}')->nullable();",
        'fillable' => true,
        'validation' => 'nullable|date',
        'validation_nullable' => 'nullable|date',
        'cast' => 'date',
        'component' => 'DatePicker',
        'import' => 'DatePicker',
        'render_table' => 'date_format',
        'faker' => 'fake()->date()'
    ],
    'timepicker' => [
        'migration' => "\$table->time('{field}')->nullable();",
        'fillable' => true,
        'validation' => 'nullable|date_format:H:i',
        'validation_nullable' => 'nullable|date_format:H:i',
        'component' => 'TimePicker',
        'import' => 'Input',
        'render_table' => 'time_format',
        'faker' => 'fake()->time()'
    ],
    'daterangepicker' => [
        'migration' => "\$table->string('{field}')->nullable();",
        'fillable' => true,
        'validation' => 'nullable|string',
        'validation_nullable' => 'nullable|string',
        'component' => 'DateRangePicker',
        'import' => 'DateRangePicker',
        'render_table' => 'date_range_format',
        'faker' => 'fake()->date() . " - " . fake()->date()'
    ],
    'number' => [
        'migration' => "\$table->integer('{field}')->nullable();",
        'fillable' => true,
        'validation' => 'nullable|integer|min:0',
        'validation_nullable' => 'nullable|integer|min:0',
        'component' => 'NumberInput',
        'import' => 'Input',
        'render_table' => 'number_format',
        'faker' => 'fake()->numberBetween(0, 1000)'
    ],
    'currency' => [
        'migration' => "\$table->decimal('{field}', 10, 2)->nullable();",
        'fillable' => true,
        'validation' => 'nullable|numeric|min:0',
        'validation_nullable' => 'nullable|numeric|min:0',
        'cast' => 'decimal:2',
        'component' => 'CurrencyInput',
        'import' => 'CurrencyInput',
        'render_table' => 'currency_format',
        'faker' => 'fake()->randomFloat(2, 10, 1000)'
    ],
    'richtext' => [
        'migration' => "\$table->longText('{field}')->nullable();",
        'fillable' => true,
        'validation' => 'nullable|string',
        'validation_nullable' => 'nullable|string',
        'component' => 'RichTextEditor',
        'import' => 'RichTextEditor',
        'render_table' => 'html_content',
        'faker' => 'fake()->randomHtml(3, 5)'
    ],
    'phone' => [
        'migration' => "\$table->string('{field}', 20)->nullable();",
        'fillable' => true,
        'validation' => 'nullable|string|max:20',
        'validation_nullable' => 'nullable|string|max:20',
        'component' => 'PhoneInputComponent',
        'import' => 'PhoneInputComponent',
        'render_table' => 'phone_format',
        'faker' => 'fake()->e164PhoneNumber()'
    ],
    'slider' => [
        'migration' => "\$table->integer('{field}')->default(50);",
        'fillable' => true,
        'validation' => 'nullable|array',
        'validation_nullable' => 'nullable|array',
        'cast' => 'integer',
        'component' => 'Slider',
        'import' => 'Slider',
        'render_table' => 'slider_format',
        'faker' => 'fake()->numberBetween(0, 100)'
    ],
    'switch' => [
        'migration' => "\$table->boolean('{field}')->default(false);",
        'fillable' => true,
        'validation' => 'boolean',
        'validation_nullable' => 'boolean',
        'cast' => 'boolean',
        'component' => 'Switch',
        'import' => 'Switch',
        'render_table' => 'switch_badge',
        'faker' => 'fake()->boolean(70)'
    ],
    'rating' => [
        'migration' => "\$table->integer('{field}')->nullable()->default(0);",
        'fillable' => true,
        'validation' => 'nullable|integer|min:0|max:5',
        'validation_nullable' => 'nullable|integer|min:0|max:5',
        'cast' => 'integer',
        'component' => 'Rating',
        'import' => 'Rating',
        'render_table' => 'rating_stars',
        'faker' => 'fake()->numberBetween(1, 5)'
    ],
    'media' => [
        'migration' => "\$table->string('{field}')->nullable();",
        'fillable' => true,
        'validation' => 'nullable|string',
        'validation_nullable' => 'nullable|string',
        'cast' => 'string',
        'component' => 'MediaPicker',
        'import' => 'MediaPicker',
        'render_table' => 'media_preview',
        'faker' => 'fake()->imageUrl(640, 480, null, false)'
    ],
    'multiselect' => [
        'migration' => "\$table->json('{field}')->nullable();",
        'fillable' => true,
        'validation' => 'nullable|array',
        'validation_nullable' => 'nullable|array',
        'cast' => 'array',
        'component' => 'MultiSelectEnhanced',
        'import' => 'MultiSelectEnhanced',
        'render_table' => 'multiselect_badges',
        'faker' => 'json_encode([fake()->randomElement(["option1", "option2", "option3"]), fake()->randomElement(["option4", "option5"])])'
    ],
    'color' => [
        'migration' => "\$table->string('{field}', 7)->default('#FF6B6B');",
        'fillable' => true,
        'validation' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
        'validation_nullable' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
        'component' => 'ColorInput',
        'import' => 'Input',
        'render_table' => 'color_display',
        'faker' => 'fake()->hexColor()'
    ],
    'datetime' => [
        'migration' => "\$table->timestamp('{field}')->nullable();",
        'migration_nullable' => "\$table->timestamp('{field}')->nullable();",
        'fillable' => true,
        'validation' => 'nullable|date',
        'validation_nullable' => 'nullable|date',
        'cast' => 'datetime',
        'component' => 'DateTimeRangePicker',
        'import' => 'DateTimeRangePicker',
        'props' => [
            'mode' => 'single',
            'placeholder' => 'Select {field_label}'
        ],
        'render_table' => 'datetime_format',
        'faker' => 'fake()->dateTime()->format("Y-m-d H:i:s")'
    ],
    'datetimerange' => [
        'migration' => "\$table->string('{field}')->nullable();",
        'migration_nullable' => "\$table->string('{field}')->nullable();",
        'fillable' => true,
        'validation' => 'nullable|string',
        'validation_nullable' => 'nullable|string',
        'component' => 'DateTimeRangePicker',
        'import' => 'DateTimeRangePicker',
        'props' => [
            'mode' => 'range',
            'placeholder' => 'Select {field_label}'
        ],
        'render_table' => 'datetime_range_format',
        'faker' => 'fake()->dateTime()->format("Y-m-d H:i:s") . " - " . fake()->dateTime()->format("Y-m-d H:i:s")'
    ],
    'tagsinput' => [
        'migration' => "\$table->json('{field}')->nullable();",
        'migration_nullable' => "\$table->json('{field}')->nullable();",
        'fillable' => true,
        'validation' => 'nullable|array',
        'validation_nullable' => 'nullable|array',
        'cast' => 'array',
        'component' => 'TagsInput',
        'import' => 'TagsInput',
        'props' => [
            'placeholder' => 'Add {field_label}...'
        ],
        'render_table' => 'tags_list',
        'faker' => 'json_encode([fake()->word(), fake()->word(), fake()->word()])'
    ]
];