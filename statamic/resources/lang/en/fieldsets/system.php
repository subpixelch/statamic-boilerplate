<?php

return [

    'license_key' => 'License Key',
    'license_key_instruct' => 'Enter the key for the corresponding domain from your [Statamic Account](https:/account.statamic.com/licenses).',

    'locales' => 'Locales',
    'locales_instruct' => 'The locales from which your site will be accessed.',

    'timezone' => 'Timezone',
    'timezone_instruct' => 'The [timezone](http://php.net/manual/en/timezones.php) you want your site to operate under.',

    'date_format' => 'Date format',
    'date_format_instruct' => 'The PHP date format string used when outputting unformatted date variables.',

    'default_extension' => 'Default extension',
    'default_extension_instruct' => 'The file extension for your content files.',

    'filesystems' => 'Filesystems',
    'filesystems_instruct' => 'Define how and where your various files will be accessed.',

    'app_key' => 'Application Key',
    'app_key_instruct' => 'This key is used to secure your application. It should be a strong, 32 character string.',

    'redactor' => 'Redactor Settings',
    'redactor_instruct' => 'YAML representations of [Redactor settings
                            objects](https://imperavi.com/assets/pdf/redactor-documentation-10.pdf).  
                            Each item will be available to select when creating a Redactor field.',

    'protect' => 'System-wide Protection',
    'protect_instruct' => 'Entering a protection scheme here will apply it to your entire site\'s front-end.',
    
    'csrf_exclude' => 'CSRF Excluded URLs',
    'csrf_exclude_instruct' => 'A list of URLS to exclude from CSRF protection',

];
