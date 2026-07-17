<?php 
return [
  // Common
  'messages' => [
    'settings_saved'            => 'Settings saved successfully',
    'contacts_saved'            => 'Contacts saved successfully',
    'homepage_saved'            => 'Homepage saved successfully',
    'file_saved'                => 'File saved successfully',
    'error_saving_file'         => 'Error saving file',
    'delete_restricted_title'   => 'Warning! You cannot delete this because of dependent resources!',
    'delete_cascade_warning'    => 'Warning! If you delete this other resources bill be affected!',
  ],
  'customers' => [
    'customer' => [
      'navigation_label'      => 'Customers',
      'model_label_singular'  => 'Customer',
      'fields' => [
        'customer'               => 'Customer',
        'is_approved'            => 'Approved',
        'customer_group_id'      => 'Group',
        'email'                  => 'Email',
        'first_name'             => 'Firstname',
        'last_name'              => 'Lastname',
        'phone'                  => 'Phone',
        'addresses'              => 'Customer addresses',
        'addresses_label'        => 'Address name',
        'addresses_placeholder'  => 'E.g. Home, Work',
        'is_default_shipping'    => 'Is default shipping address',
        'is_default_billing'     => 'Is default billing address',
        'add_address'            => 'Add new address',
        'locale'                 => 'Customer preffered language',
        'password'               => 'Password',
        'marketing_opt_in'       => 'Marketing opt in',
        'gdpr_consent_at'        => 'GDPR consent',
        'anonymized_at'          => 'Anonymized at',
        'deleted_at'             => 'Deleted at',
        'created_at'             => 'Registration date',
        'updated_at'             => 'Change date',
        'presonal_data'          => 'Personal data',
        'store_data'             => 'Store data',
        'email_data'             => 'Email',
        'email_verified_at'      => 'Email verified at',
        'company_name'           => 'Company name',
        'company_data'           => 'Company',
        'vat_number'             => 'VAT number',
        'privacy_data'           => 'Privacy',
        'anonymize'              => 'Anonymize data'
      ],
      'messages' => [
        'anonymize_title'       => 'Anonymize user data?',
        'anonymize_description' => 'This action is irreversible. All user personal data will be replaced with dummy placeholders',
        'anonymize_confirm'     => 'I acknowledge, continue.',
      ]
    ],
    'customer_groups' => [
      'navigation_label'      => 'Customer groups',
      'model_label_singular'  => 'Customer group',
      'fields' => [
        'name'                   => 'Name',
        'code'                   => 'Type',
        'price_modifier_percent' => 'Price modifier',
        'free_shipping'          => 'Free shipping',
        'show_prices'            => 'Show prices',
        'requires_approval'      => 'Req. approval',
        'tax_exempt'             => 'Show taxes',
        'is_default'             => 'Default for new users',
        'sort_order'             => 'Sort orders',
        'is_active'              => 'Active',
        'group_settings'         => 'Group settings',
      ],
      'helpers' => [
        'price_modifier_percent' => 'You can set positve or negave value here, which will be applied to all prices in your store',
        'code'                   => 'General type of customer group, like new, regular, B2B, etc.'
      ]
    ]
  ],
  'seo' => [
    'slugs' => [
      'model_label_singular'  => 'URL',
      'navigation_label'      => 'URLs',
      'fields' => [
        'url'                   => 'URL',
        'language'              => 'Language',
        'type'                  => 'Type',
        'id'                    => 'ID',
        'redirect'              => 'Redirect 301',
        'sluggable_id'          => 'Page ID'
      ],
      'helpers' => [
        'redirect'  => 'Redirect with 301 code from current URL to this one',
        'is_active' => 'If is not active, both URL and Redirect will return 410 code',
        'type'      => 'Page type, e.g. BlogPost, Product, ProductOption Category, Manufacturer, etc.',
        'id'        => 'Page ID so controller can determine which page to open',
      ],
      'errors' => [
        'slug_taken' => 'This URL is already in use',
        'alpha_dash' => 'Only alpha-numeric and dashes allowed',
      ]
    ],
    'robots' => [
    ],
  ],
  'blog' => [
    'authors'  => [
      'navigation_label'      => 'Authors',
      'model_label_singular'  => 'Author',
      'fields'  => [
        'avatar'          => 'Author\'s avatar',
        'name'            => 'Name',
        'sort_order'      => 'Sort order',
        'is_active'       => 'Active',
        'created_at'      => 'Created at',
        'posts_count'     => 'Posts count',
        'social_links'    => 'Author\'s social links',
        'social_platform' => 'Platform',
        'social_url'      => 'URL',
      ],
      'buttons' => [
        'add_social_link' => 'Add author social link'
      ],
      'helpers' => [
        'avatar' => 'Displayed in every author\'s post and on author\'s page '
      ],
    ],
    'comments' => [
      'navigation_label'      => 'Comments',
      'model_label_singular'  => 'Comment',
      'fields' => [
        'post'               => 'Post',
        'type'               => 'Type',        
        'author'             => 'Author',
        'body'               => 'Comment',
        'rating'             => 'Rating',
        'is_approved'        => 'Approved',
        'created_at'         => 'Created at',
        'reply_body'         => 'Reply',
        'locale'             => 'Language',
        'thread'             => 'Replies',
        'reply_author'       => 'Store reply author',
      ],
      'filters' => [
        'is_approved'        => 'Approved',
        'rating'             => 'Rating',
      ],
      'actions' => [
        'reply'              => 'Reply',
      ],
      'notifications' => [
        'reply_sent'         => 'Reply sent successfully',
      ],
      'labels' => [
        'admin_reply'        => 'Admin reply',
        'customer_comment'   => 'Customer comment',
        'store_reply_author' => 'Store reply author',
      ]
    ],
    'tags'  => [
      'navigation_label'      => 'Tags',
      'model_label_singular'  => 'Tag',
      'fields'  => [
        'name'        => 'Name',
        'sort_order'  => 'Sort order',
        'is_active'   => 'Active',
        'is_menu'     => 'Show in menu',
        'created_at'  => 'Created at',
        'posts_count' => 'Posts count',
      ],
      'helpers' => [],
    ],
    'posts' => [
      'navigation_label'      => 'Posts',
      'model_label_singular'  => 'Post',
      'fields'  => [
        'image'       => 'Image',
        'name'        => 'Name',
        'sort_order'  => 'Sort order',
        'is_active'   => 'Active',
        'created_at'  => 'Created at',
      ],
      'helpers' => [
        'tags' => 'Which tags this post is related to'
      ],
    ]
  ],
  'common' => [
    'fields' => [
      'name'              => 'Name',
      'slug'              => 'URL',
      'description_short' => 'Short description',
      'description_full'  => 'Full description',
      'h1'                => 'H1',
      'meta_title'        => 'Meta title',
      'meta_description'  => 'Meta description',
      'faq_question'      => 'Question',
      'faq_answer'        => 'Answer',
      'how_to_step_name'  => 'Step name',
      'how_to_step_text'  => 'Step description',
      'image'             => 'Image',
      'image_description' => 'Description',
      'footer_tab'        => 'Tab title',
      'footer_content'    => 'Content',
      'created_at'        => 'Created at',
      'updated_at'        => 'Updated at',
      'is_active'         => 'Active',
    ],
    'helpers' => [
      'name'              => 'Name',
      'slug'              => 'Should be unique in every language storewide',
      'description_short' => 'Introductory text that is displayed above other page blocks. Recommended length - up to 500 characters',
      'description_full'  => 'Full description',
      'h1'                => 'Include your primary target keyword, match closely to title tag',
      'meta_title'        => 'Recommended 50–60 chars. Longer title will be truncated. Max length is 150 chars',
      'meta_description'  => 'Recommended 120 chars for mobile and 150 chars for desktop. Longer description will be truncated. Max length is 255 chars',
      'image_description' => 'Alt image attribute, best under 125 chars',
      'images_tab'        => 'Don\'t forget to set image\'s Alt attribue, it helps Search engines to understand what\'s depicted, thus better SEO ranking',
      'footer_tab'        => 'You can add some content here in form of tabs, mainly for SEO to enrich the page with missing keywords',
      'faq_tab'           => 'FAQ will be displayed under the full description as collapsing Question/Answer blocks',
      'how_to_tab'        => 'HowTo will be displayed under the full description as list of steps and directions',
      'image_type_not_set_info'  => 'Image type <b>:type</b> is missing! Set this image in Design/Image settings to add images for this page',
      'image_type_not_set_title' => 'Image dimensions are not set',
    ],
    'tabs' => [
      'content'     => 'Content',
      'description' => 'Description',
      'faq'         => 'FAQ',
      'how_to'      => 'HowTo',
      'images'      => 'Images',
      'footer'      => 'Footer',
      'relations'   => 'Relations',
      'general'     => 'General',
    ],
    'buttons' => [
      'save'              => 'Save',
      'cancel'            => 'Cancel',
      'add_faq_row'       => 'Add new Question/Answer',
      'add_image_row'     => 'Add new image',
      'add_how_to_step'   => 'Add new HowTo step',
      'add_how_to_tool'   => 'Add new HowTo tool',
      'add_how_to_supply' => 'Add new HowTo supply',
      'add_footer_tab'    => 'Add new Footer tab',
    ],
  ],
  'design' => [
    'image_types' => [
      'title'               => 'Image types and dimensions',
      'subheading'          => 'Here you can set your store logo and global image dimensions',
      'logo'                => 'Logo',
      'product'             => 'Product',
      'category'            => 'Category',
      'blog'                => 'Blog',
      'product_miniature'   => 'Product miniature',
      'product_main'        => 'Product main',
      'options_attributes'  => 'Options and attributes',
      'options'             => 'Options',
      'attributes'          => 'Attributes',
      'blog_miniature'      => 'Blog miniature',
      'blog_main'           => 'Blog main',
      'category_miniature'  => 'Category miniature',
      'category_main'       => 'Category main',
      'slider'              => 'Slider',
      'misc'                => 'Miscellaneous',
      'image_type'          => 'Image type',
      'width'               => 'Width (px)',
      'height'              => 'Height (px)',
    ],
    'css_editor' => [
      'subheading' => 'Here you can add override CSS styles that follow the main CSS file. The main CSS file is not changed',
      'css' => 'CSS',
    ],
  ],
  'navigation' => [
    'groups' => [
      'orders'          => 'Orders',
      'catalog'         => 'Catalog',
      'blog'            => 'Blog',
      'stock'           => 'Stock',
      'customers'       => 'Customers',
      'seo'             => 'SEO',
      'design'          => 'Design',
      'store_settings'  => 'Store settings',
      'global_settings' => 'Global settings',
    ],
    'items' => [
      'store_contacts'              => 'Contacts',
      'store_settings'              => 'Settings',
      'store_homepage_description'  => 'Homepage',
      'image_settings'              => 'Images settings',
      'css_editor'                  => 'CSS Editor',
      'robots_editor'               => 'Robots.txt',
    ],
  ],

  // Info pages
  'info_pages' => [
    'navigation_label'     => 'Info pages',
    'model_label_singular' => 'Info page',
  ],

  // Store contacts form
  'store_contacts' => [
    'fields' => [
      'legal_infos' => 'Legal infos',
      'organization_description'    => 'Organization description',
      'local_business_description'  => 'LocalBusiness description',
      'legal_name'                  => 'Legal name',
      'address_details'             => 'Address details',
      'country'                     => 'Country name',
      'region'                      => 'Region name or short code',
      'city'                        => 'City name',
      'street'                      => 'Street, building number',
      'iso_code'                    => 'Main operation country ISO code',
      'postal_code'                 => 'Postal code',
      'geo_infos'                   => 'Geo position',
      'latitude'                    => 'Latitude',
      'longitude'                   => 'Longitude',
      'email'                       => 'Email',
      'open_hours'                  => 'Open hours',
      'day'                         => 'Week day',
      'opens'                       => 'Opens',
      'closes'                      => 'Closes',
      'phones'                      => 'Phone numbers',
      'phone_name'                  => 'Phone name (Work, Service, Support, etc.)',
      'phone_number'                => 'Phone number',
      'social_links'                => 'Social networks links',
      'social_link_icon'            => 'Icon',
      'social_link_title'           => 'Title',
      'social_link_link'            => 'Link',
      'social_contacts'             => 'Social contacts',
      'social_contact_icon'         => 'Icon',
      'social_contact_title'        => 'Title',
      'social_contact_link'         => 'Link',
    ],
    'helpers' => [
      'legal_name'                  => 'Company legal name. Used in JSON-LD microdata and "About us" page',
      'organization_description'    => 'Description of your online business in several sentences. Used in JSON-LD microdata and "About us" page',
      'local_business_description'  => 'Description of physical store in several sentences. Used in JSON-LD microdata and "About us" page. If you don\'t have physical store, you can skip this',
      'country'                     => 'Country in plain language. This data is used in "About us" page and in JSON-LD microdata to display Google rich snippets',
      'region'                      => 'Region in plain language. This data is used in "About us" page and in JSON-LD microdata to display Google rich snippets',
      'city'                        => 'City in plain language. This data is used in "About us" page and in JSON-LD microdata to display Google rich snippets',
      'street'                      => 'Street and building number in plain language. This data is used in "About us" page and in JSON-LD microdata to display Google rich snippets',
      'iso_code'                    => 'Country ISO code in two letter ISO 3166-1 alpha-2 format: UA, PL, US, GB, etc. ISO code is used in "About us" page and in JSON-LD microdata to display Google rich snippets',
      'postal_code'                 => 'Postal code where your company registered/located. Adds trust to your company for Google bots',
      'latitude'                    => 'Format: 50.450100 (decimal degrees). Helps Google maps to locate your store. If you don\'t have physical store, you can skip this',
      'longitude'                   => 'Format: 30.523400 (decimal degrees). Helps Google maps to locate your store. If you don\'t have physical store, you can skip this',
      'open_hours'                  => 'Day of week example: <u><b>Monday</b></u>,<br>Open hours example: <u><b>10:30</b></u>',
      'social_contacts'             => 'Social messengers links: Viber, Facebook messenger, WhatsApp, etc. Icon may be in SVG, title - plain text<br><b>Examples of links:</b>
        <ul class="fi-sc-unordered-list">
          <li><u><b>Viber:</b></u> viber://contact?number=%2B{PhoneNumberWithoutPlus}</li>
          <li><u><b>WhatsApp:</b></u> https://wa.me/{PhoneNumberWithoutPlus}</li>
          <li><u><b>Telegram:</b></u> https://t.me/{YourAccountNameInTelegram}</li>
          <li><u><b>Facebook Messenger:</b></u> https://m.me/{YourAccountNameInTelegram}</li>
        </ul>
      ',
    ],
    'buttons' => [
      'add_open_hours'      => 'Add open hours',
       'add_phone'          => 'Add new phone number',
       'add_social_link'    => 'Add new social link',
       'add_social_contact' => 'Add new social contact',
    ],
  ],

  // Store settings
  'store_settings' => [
    'tabs' => [
      'delivery_settings'       => 'Delivery',
      'checkout_settings'       => 'Checkout',
      'legal_settings'          => 'Legal settings',
      'tax_settings'            => 'Taxes',
      'analytics_settings'      => 'Analytics',
      'seo_defaults'            => 'SEO defaults',
      'notification_settings'   => 'Notifications',
      'maintenance_settings'    => 'Maintenance',
    ],
    'delivery_settings' => [
      'fields' => [
        'transit_min'   => 'Minimal delivery time (days)',
        'transit_max'   => 'Maximal delivery time (days)',
        'handling_min'  => 'Minimal handling time (days)',
        'handling_max'  => 'Maximal handling time (days)',
        'return_cost'   => 'Return cost',
      ],
      'helpers' => [
        'transit'     => 'This value is used in JSON-LD markup to show Google rich snippets, and only as fallback value. Actual delivery time may vary',
        'handling'    => 'This value is used in JSON-LD markup to show Google rich snippets, and only as fallback value. Actual time handling may vary',
        'return_cost' => 'This value is used in JSON-LD markup to show Google rich snippets, and only as fallback value',        
      ]
    ],
    'checkout_settings' => [
      'fields' => [
        'minimal_order_total'     => 'Minimal checkout total',
        'agreement_pages'         => 'Agreement pages',
        'service_agreement_page'  => 'Service agreement page',
        'return_rules_page'       => 'Return rules page',
        'checkout_custom_fields'  => 'Custom checkout fields',
        'checkout_address_fields' => 'Address fields',
        'firstname'               => 'Firstname',
        'lastname'                => 'Lastname',
        'building'                => 'Building',
        'apartment'               => 'Apartment',
        'postal_code'             => 'Postal code',
        'region'                  => 'Region',
        'phone'                   => 'Phone',
        'payer'                   => 'Payer',
        'country'                 => 'Country',
        'city'                    => 'City',
        'street'                  => 'Street',
        'vat_number'              => 'Vat number',
        'company'                 => 'Company',
        'time'                    => 'Time picker',
        'date'                    => 'Date picker',
        'datetime'                => 'Datetime picker',
        'text'                    => 'Text',
        'textarea'                => 'Textarea',
        'checkbox'                => 'Checkbox',
        'add_field'               => 'Add field',
        'field_type'              => 'Field type',
        'field_name'              => 'Field name',
        'is_required'             => 'Required',
      ],
      'helpers' => [
        'checkout_fields'        => 'Checkout fields like address and receiver name that will be displayed in cart',
        'custom_fields'          => 'Additional checkout fields like preferred delivery date',
        'service_agreement_page' => 'Will be shown as checkbox "I\'ve read and agreed to Service agreement" on checkout page. Skip this, if you don\'t need this',
        'return_rules_page'      => 'Will be shown as checkbox "I\'ve read and agreed to Return rules" on checkout page. Skip this, if you don\'t need this',
      ],
    ],
  ],

  // Countries
  'countries' => [
    'navigation_label'      => 'Countries',
    'model_label_singular'  => 'Country',
    'fields' => [
      'name'                 => 'Name',
      'iso_code'             => 'ISO code',
      'phone_code'           => 'Phone code',
      'default_currency_id'  => 'Default currency',
      'is_eu_member'         => 'EU member',
      'regions'              => 'Regions',
      'region'               => 'Region',
      'add_region'           => 'Add region',
      'is_active'            => 'Active',
    ],
    'helpers' => [
      'add_region' => 'Not necessary, you can skip this',
      'iso_code'   => 'Country ISO code in two letter ISO 3166-1 alpha-2 format: UA, PL, US, GB, etc. ISO code is used in JSON-LD microdata to display Google rich snippets',
      'phone_code' => 'Phone code of country: +380, +48, +1, +44 to format phone numbers on checkout process',
    ],
  ],

  // Currencies
  'currencies' => [
    'navigation_label'      => 'Currencies',
    'model_label_singular'  => 'Currency',
    'fields' => [
      'name'          => 'Name',
      'iso_code'      => 'ISO Code',
      'sign'          => 'Currency sign',
      'rate'          => 'Exchange rate',
      'rate_default'  => 'Default',
      'is_active'     => 'Active',
    ],
    'helpers' => [
      'name'          => 'Currency name in Admin panel',
      'iso_code'      => 'ISO Code is used in JSON-LD markup',
      'sign'          => 'Sign is used to display prices in Frontend and Backend',
      'rate'          => 'Exchange rate to your default currency',
      'rate_default'  => 'This currency is used to calculate rates for other currencies. Only one default currency system-wide',
    ],
  ],

  // Languages
  'languages' => [
    'navigation_label'      => 'Languages',
    'model_label_singular'  => 'Language',
    'fields' => [
      'flag'                      => 'Flag',
      'name'                      => 'Name',
      'iso_code'                  => 'ISO Code',
      'locale'                    => 'Locale',
      'is_active'                 => 'Active',
      'stores'                    => 'Associated stores',
      'default_currency'          => 'Default currency',
      'fulltext_search_language'  => 'Fulltext search dictionary',
      'is_default'                => 'Default'
    ],
    'helpers' => [
      'name'                      => 'Language name in Admin panel',
      'iso_code'                  => 'Example: <b>en-US</b>. ISO Code is used in JSON-LD markup',
      'locale'                    => 'Locale is used in HTML header to tell search engines page language',
      'default_currency'          => 'Default currency is defined to display prices in relation to language so search engines see correct prices related to language. This does not affect customers, they still can change displayed currency',
      'fulltext_search_language'  => 'Fulltext search morphology dictionary. You may use custom dictionary for PostgreSQL but you will need to install it first'
    ],
  ],

  // Stores
  'stores' => [
    'navigation_label'      => 'Stores',
    'model_label_singular'  => 'Store',
    'fields' => [
      'name'                => 'Name',
      'host'                => 'Domain name',
      'languages'           => 'Languages',
      'currencies'          => 'Currencies',
      'countries'           => 'Countries',
      'is_active'           => 'Active',
    ],
    'relations' => [
      'languages' => [
        'is_default' => 'Default language',
        'is_active'  => 'Active for store',
      ],
    ],
    'helpers' => [
      'name'          => 'Name to display in admin panel',
      'host'          => 'Store domain name name without http:// or https://. <br> Example <u><b>store.com</b></u>',
      'on_save_title' => 'Info',
      'on_save_info'  => 'Language, currency, and country settings will be displayed after saving the store. Don\'t forget to set them up',
    ],
  ],

  // Users
  'users' => [
    'navigation_label' => 'Users',
    'model_label_singular' => 'user',
    'fields' => [
      'name'        => 'Name',
      'email'       => 'Email',
      'role'        => 'Role',
      'password'    => 'Password',
      'locale'      => 'Interface language',
      'created_at'  => 'Created at',
      'is_active'   => 'Active',
    ],
  ]
];