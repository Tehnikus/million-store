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
    'ai_provider_error'         => 'AI Provider error!',
    'ai_fill_required_fields'   => 'Fill required fields before sending the prompt',
  ],
  'orders' => [
    'statuses' => [
      'navigation_label'      => 'Statuses',
      'model_label_singular'  => 'Status',
      'fields' => [
        'name'            => 'Status name',
        'color'           => 'Status color',
        'icon'            => 'Icon',
        'notification'    => 'Notify customer',
        'is_default'      => 'Default',
        'is_paid'         => 'Paid',
        'is_shipped'      => 'Shipped',
        'is_finished'     => 'Finished',
        'is_active'       => 'Active',
      ],
    ],
  ],
  'catalog' => [
    'products' => [
      'navigation_label'      => 'Products',
      'model_label_singular'  => 'Product',
      'table' => [
        'columns' => [
          'global_name'             => 'Global name',
          'sku'                     => 'SKU',
          'price'                   => 'Price',
          'created_at'              => 'Created',
          'updated_at'              => 'Updated',
          'valid_until'             => 'until',
          'store_name'              => 'Store name',
          'placement'               => 'Placement',
          'is_active'               => 'Active in this store',
          'is_not_active'           => 'Not active in this store',
          'is_not_associated'       => 'Not associated to this store',
        ],
        'filters' => [
          'is_active'     => 'Active',
          'is_available'  => 'Available for order',
          'categories'    => 'Categories',
          'manufacturers' => 'Manufacturers',
          'tags'          => 'Tags',
          'options'       => 'Options',
        ],
        'buttons' => [
          'delete_from_store'       => 'Delete from current store',
          'delete_from_all_stores'  => 'Delete product from all stores',
          'edit_product'            => 'Edit product',
        ],
        'messages' => [
          'empty_state_message'     => 'Products can be linked to multiple stores simultaneously while having different prices, descriptions, and sets of options and attributes, and can be displayed in different categories within each store',
          'delete_from_store'       => 'This action will delete this product from the current store, but not from other stores. All product data in current store will be deleted. Continue?',
          'delete_from_all_stores'  => 'This action will delete this product from all stores. All product data in all stores will be deleted. This action is irreversible. Continue?',
        ],
      ],
      'tabs' => [
        'content' => [
          'labels' => [
            'global_name'   => 'Global name',
            'is_active'     => 'Active in this store',
            'is_available'  => 'Available for order in this store',
          ],
          'helpers' => [
            'global_name'   => 'Shown only in Admin panel. Used for product quick search',
            'is_active'     => 'Whether the product appears in this store\'s catalog. Product status is set separately for every store',
            'is_available'  => 'Whether the product is available for order in this store. Product availability is set separately for every store',
          ],
        ],
        'placement' => [
          'label'   => 'Placement',
          'labels'  => [
            'categories'              => 'Categories to display',
            'category'                => 'Category',
            'is_primary_category'     => 'Primary category',
            'manufacturer'            => 'Manufacturer',
            'manufacturers'           => 'Manufacturers',
            'is_primary_manufacturer' => 'Primary manufacturer',
            'product_tags'            => 'Product tags',
            'tag'                     => 'Tag',
            'sort_order'              => 'Sort order',
            'search_custom_terms'     => 'Custom search terms',
            'search_excluded_refs'    => 'Excluded search references',
            'attribute_group'         => 'Attribute group',
            'option_group'            => 'Option group',
          ],
          'placeholders' => [
            'search_custom_terms'     => 'Custom search terms',
            'search_excluded_refs'    => 'Categories, Manufacturers, Tags, Options, Attributes...',
          ],
          'helpers' => [
            'categories'            => 'Select categories where product will be displayed. Primary category is used to build product URL',
            'manufacturers'         => 'Select manufacturers where product will be displayed. Primary manufacturer is used to display the manufacturer\'s logo and link on the product page',
            'tags'                  => 'Product tag is an additional way to group products together to show them on SEO filter page. They can be also displayed as badges on product miniature and product main image',
            'search_custom_terms'   => 'You can add custom search terms so that this product can be found by searching for those words',
            'search_excluded_refs'  => 'You can exclude search references so that this product will NOT be found by searching those references',
          ],
          'buttons' => [
            'add_tag'                 => 'Add product tag',
            'add_category'            => 'Add category',
            'add_manufacturer'        => 'Add manufacturer',
          ],
          'errors' => [
            'no_primary_category' => 'Select at least one category and set primary category',
            'no_categories'       => 'Create categories to add product to',
          ]
        ],
        'prices' => [
          'label' => 'Prices',
          'labels' => [
            'price_tiers'       => 'Price tiers',
            'base_price'        => 'Base price',
            'price_terms'       => 'Price terms',
            'price_values'      => 'Price values',
            'price_input'       => 'Price in :currency',
            'price_name'        => 'Price name',
            'valid_from'        => 'Price valid from date',
            'valid_until'       => 'Price valid until date',
            'customer_group'    => 'Customer group',
            'no_group'          => 'All customers',
            'status'            => 'Price tier type',
            'is_base'           => 'Base price',
            'discount_amount'   => 'Discount amount',
            'discount_percent'  => 'Discount percent',
            'priority'          => 'Price priority',
            'valid_quantity'    => 'Price valid quantity, pcs',
            'valid_dates'       => 'Price valid dates',
            'primary_currency'  => 'The primary currency of this store',
            'pcs'               => 'pcs',
            'priority_placeholder' => 'Greater number = more priority',
          ],
          'helpers' => [
            'price_tiers'       => 'Each price tier contains the prices of all product variants in all currencies',
            'status'            => 'A product must always have a base price level - that is, prices without discounts or conditions. To add discounts, create an additional price tier',
            'price_name'        => 'Displayed as badge near the price. Leave blank if you don\'t need this',
            'valid_quantity'    => 'Minimal product quantity in cart to apply this price',
            'priority'          => 'If multiple prices have similar appliance conditions, the higher priority price will be used',
            'customer_group'    => 'Select the customer group to which this price will apply or leave blank to apply this price tier for all customers',
            'valid_dates'       => 'Select the dates during which this price will apply',
          ],
          'buttons' => [
            'add_price_tier'    => 'Add price tier',
            'exchange_rate'     => 'Calculate at the exchange rate',
            'fill_all_combinations' => 'Fill all combinations with this price',
          ],
          'item_label' => [
            'is_base'           => 'Base price',
            'is_discount'       => 'Discount',
            'valid_from'        => 'from',
            'valid_until'       => 'until',
            'dates_valid'       => 'dates valid',
            'from_qty'          => 'from :qty pcs'
          ],
          'errors' => [
            'exactly_one_base' => 'The product must have a base price'
          ],
        ],
        'options' => [
          'label' => 'Options',
          'labels' => [
            'options'           => 'Options groups',
            'option_vals'       => 'Options values',
            'variants_selector' => 'Available product variants',
            'descriptions'      => 'Options descriptions',
            'group_name'        => 'Option name',
            'option_val_name'   => 'Option value name',
            'description'       => 'Option description',
          ],
          'helpers' => [
            'options'           => 'Select options linked to this product here. First select option groups and values, then check the checkboxes of variants that exist for this product',
            'variants_selector' => 'Check option combinations that will be applied to this product. Don\'t forget to fill each variant price on prices tab',
            'descriptions'      => 'You can assign a unique description to each option value - this is how it will appear on the product page. Names and descriptions are prefilled with default values, so you don\'t have to fill everything if you don\'t need unique names and descriptions',
            'group_name'        => 'Group name will be displayed on the product page in option selector and in product features table. This affects only how this option is displayed on product page, nothing else',
            'description'       => 'Option description is displayed in rhe product features table. You can add some unique description here as you need',
          ],
          'placeholders' => [
            'no_options'            => 'No options found',
            'search_options'        => 'Search option groups',
            'searching_options'     => 'Searching option groups...',
            'no_option_vals'        => 'No options variants found',
            'search_option_vals'    => 'Search option variants',
            'searching_option_vals' => 'Searching option variants...',
            'variants_search'       => 'Search combination variants',
            'no_variants'           => 'No variants found',
          ],
        ],
        'attributes' => [
          'label' => 'Attributes',
          'labels' => [
            'attribute'       => 'Attribute group',
            'attribute_value' => 'Attribute value',
          ],
          'placeholders' => [
            'no_attributes'               => 'Attributes not found',
            'search_attributes'           => 'Search attributes',
            'searching_attributes'        => 'Searching attributes...',
            'no_attribute_values'         => 'Attribute values not found',
            'search_attribute_values'     => 'Search attribute values',
            'searching_attribute_values'  => 'Searching attribute values...',
          ],
          'helpers' => [
            'label' => 'Attributes are unchangeable product features. Products can be filtered by attributes in product filter. Attributes are displayed in product features table on the product page'
          ],
          'buttons' => [
            'add_attribute'       => 'Add attribute',
            'add_attribute_value' => 'Add attribute value',
          ],
        ],
        'delivery'  => [
          'label' => 'Delivery'
        ],
        'inventory' => [
          'label' => 'Inventory'
        ],
        'blog_posts' => [
          'label' => 'Blog posts',
          'empty_state' => 'Here you can attach Blog posts to this product. Attached posts will be displayed on product page in additional infos section',
        ],
        'reviews' => [
          'label' => 'Reviews',
        ],
        'statistics' => [
          'label' => 'Statistics'
        ],
      ],
    ],
    'categories' => [
      'navigation_label'      => 'Categories',
      'model_label_singular'  => 'Category',
      'tabs' => [
        'content' => [
          'labels' => [
            'main'            => 'Main settings',
            'is_active'       => 'Active',
            'is_root'         => 'Root',
            'show_in_facets'  => 'Show in facets',
            'parent_id'       => 'Parent category',
          ],
          'helpers' => [
            'main'        => 'Parent category, status and filter block display',
            'parent_id'   => 'Select parent category or leave blank to set this category as Root',
          ],
        ],
        'products' => [
          'label' => 'Products',
          'labels' => [
            'table_title'           => 'Products in :name',
            'add_products'          => 'Add products',
            'add_products_heading'  => 'Add products to :name category',
            'is_primary'            => 'Primary category',
            'make_primary'          => 'Make this category primary',
            'make_primary_bulk'     => 'Set primary category',
            'detach_from'           => 'Detach :product from :name?',
            'detach_bulk'           => 'Detach selected',
            'detach_button'         => 'Detach',
            'detach_forbidden'      => 'This category is primary for this product. To detach product from this category first set a new primary category',
          ],
          'helpers' => [
            'attach_description'    => 'Added products will be displayed in this category. You can also change the product\'s primary category after adding',
            'make_primary_single'   => 'Select new primary category for :product',
            'make_primary_warning'  => 'Warning! This action will change the product URL!',
            'detach_bulk'           => 'Products for which this category is the primary one will be skipped. This action only affects where products will be displayed',
            'detach_single'         => 'This action only affects where product will be displayed',
          ],
          'notifications' => [
            'detached'        => 'Products detached from category: :count',
            'detach_skipped'  => 'Following products were skipped because this category is primary for them: ',
          ],
          'empty_state' => 'You can quickly attach products to this category here. Each product can be associated to multiple categories. Besides product listing categories are used to filter products',
        ],
      ],
      'table' => [
        'empty_state' => 'Categories are the primary way of grouping products. Each product can be associated to multiple categories. Besides product listing categories are used to filter products',
      ],
    ],
    'manufacturers' => [
      'navigation_label'      => 'Manufacturers',
      'model_label_singular'  => 'Manufacturer',
      'tabs' => [
        'content' => [
          'labels' => [
            'main'            => 'Main settings',
            'is_active'       => 'Active',
            'is_root'         => 'Root',
            'show_in_facets'  => 'Show in facets',
            'parent_id'       => 'Parent manufacturer',
          ],
          'helpers' => [
            'main'        => 'Parent manufacturer, status and filter block display',
            'parent_id'   => 'Select parent manufacturer or leave blank to set this manufacturer as Root',
          ],
        ],
        'products' => [
          'label' => 'Products',
          'labels' => [
            'table_title'           => 'Products in :name',
            'add_products'          => 'Add products',
            'add_products_heading'  => 'Add products to :name manufacturer',
            'is_primary'            => 'Primary manufacturer',
            'make_primary'          => 'Make this manufacturer primary',
            'make_primary_bulk'     => 'Set primary manufacturer',
            'detach_from'           => 'Detach :product from :name?',
            'detach_bulk'           => 'Detach selected',
            'detach_button'         => 'Detach',
            'detach_forbidden'      => 'This manufacturer is primary for this product. To detach the product from this manufacturer first set a new primary manufacturer',
          ],
          'helpers' => [
            'attach_description'    => 'Added products will be displayed in this manufacturer. You can also the product\'s primary manufacturer after adding',
            'make_primary_single'   => 'Select new primary manufacturer for :product',
            'make_primary_warning'  => 'Warning! This action will change manufacturer displayed in additional product info!',
            'detach_bulk'           => 'Products for which is manufacturer is primary one will be skipped. This action only affects where products will be displayed',
            'detach_single'         => 'This action only affects where product will be displayed',
          ],
          'notifications' => [
            'detached'        => 'Products detached from manufacturer: :count',
            'detach_skipped'  => 'Following products were skipped because this manufacturer is primary for them: ',
          ],
          'empty_state' => 'You can quickly attach products to this manufacturer here. A product may have multiple associated manufacturers, but only the primary one is displayed on the product page. Other manufacturers are used to filter products',
        ],
      ],
      'table' => [
        'group_title' => 'Product manufacturers are displayed on the product page as a logo and a link, as well as in the product filter. A product may have multiple associated manufacturers, but only the primary one is displayed on the product page. Other manufacturers are used to filter products',
      ],
    ],
    'attributes' => [
      'navigation_label'      => 'Attributes',
      'model_label_singular'  => 'Attribute',
      'fields'  => [
        'group_title'           => 'Attribute',
        'group'                 => 'Group',
        'group_name'            => 'Attribute group name',
        'values'                => 'Attibute entries',
        'image'                 => 'Image',
        'description'           => 'Attribute description',
        'attribute_name'        => 'Attribute name',
        'slug'                  => 'URL',
        'show_in_facets'        => 'Show in filter',
        'is_active'             => 'Active',
      ],
      'helpers' => [
        'group_title'           => 'Attributes are unchangable properties of a product. They are displayed on the product page as a table and in product filter',
        'group_name'            => 'Group name will be displayed in facet filter as a parent of attribute values',
        'is_active'             => 'Show this group on product page and filter block',
        'show_in_facets'        => 'Show this group in filter block',
        'value_is_active'       => 'Show this attribute value on product pages',
        'value_show_in_facets'  => 'Show this attribute value in filter block',
        'description'           => 'The description will be diplsayed on product page. Can be overridden from product settings',
      ],
      'buttons' => [
        'add_attribute_value' => 'Add attribute value'
      ]
    ],
    'options' => [
      'navigation_label'      => 'Options',
      'model_label_singular'  => 'Option',
      'fields'  => [
        'group_title'           => 'Option',
        'group'                 => 'Group',
        'group_name'            => 'Option group name',
        'values'                => 'Option variants',
        'image'                 => 'Image',
        'description'           => 'Description',
        'option_name'           => 'Option name',
        'slug'                  => 'URL',
        'show_in_facets'        => 'Show in filter',
        'is_active'             => 'Active',
        'is_default'            => 'Default pre-checked',
        'type'                  => 'Option type',
        'radio'                 => 'Radio button',
        'checkbox'              => 'Checkbox',
      ],
      'helpers' => [
        'group_title'           => 'Options are changable properties of a product. They affect product price, SKU, availability and weight. Options are displayed on product pages and in the product filter',
        'group_name'            => 'Group name will be displayed in facet filter as a parent of option values',
        'is_active'             => 'Show this group on product page and filter block',
        'show_in_facets'        => 'Show this group in filter block',
        'value_is_active'       => 'Show this option value on product pages',
        'value_show_in_facets'  => 'Show this option value in filter block',
        'is_default'            => 'Set this value pre-checked. Can be overriden by product settings',
        'type'                  => 'Option type defines how option can be selected: one of the options list (radio button) or multiple (checkbox)',
      ],
      'buttons' => [
        'add_option_value' => 'Add option value'
      ]
    ],
    'tags' => [
      'navigation_label'      => 'Tags',
      'model_label_singular'  => 'Tag',
      'tabs' => [
        'content' => [
          'labels'  => [
            'main'            => 'Main settings',
            'show_in_facets'  => 'Show in filter',
            'is_active'       => 'Active',
            'inline_style'    => 'Inline style',
          ],
          'helpers' => [
            'main'            => 'Inline style, status and filter block display',
            'is_active'       => 'Show this tag on product page in tag cloud and in filter block',
            'show_in_facets'  => 'Show this tag in filter block',
            'inline_style'    => 'Inline style to be applied to your tag on product miniature and product page',
          ],
        ],
        'products' => [
          'label'       => 'Products',
          'labels' => [
            'table_title'           => 'Products in :name tag',
            'detach_from'           => 'Detach :product from :name tag?',
            'detach_button'         => 'Detach',
            'add_products'          => 'Add products',
            'add_products_heading'  => 'Add products to :name tag',
            'detach_bulk'           => 'Detach selected',
          ],
          'helpers' => [
            'detach_single'         => 'This action only affects where product will be displayed',
            'attach_description'    => 'Added products will be displayed in this tag',
            'detach_bulk'           => 'This action only affects where products will be displayed',
          ],
          'notifications' => [
            'detached'              => 'Products detached from manufacturer: :count'
          ],
          'empty_state' => 'Here you can quickly attach products to this tag',
        ],
      ],
      'table' => [
        'empty_state' => 'Tags are used to group products when you need to combine items into a group and create a landing page for them. Tags are also displayed in products filter',
      ]
    ],
    'facet_pages' => [
      'navigation_label'      => 'Filter pages',
      'model_label_singular'  => 'Filter page',
      'fields' => [
        'is_active'         => 'Is active',
        'facet_list'        => 'Facet list',
        'facet_index'       => 'Facet index',
        'root_facet'        => 'Root filter',
        'additional_facets' => 'Additional filters'
      ],
      'buttons' => [
        'add_facet' => 'Add filter'
      ],
      'errors' => [
        'duplicate_combination'   => 'This filters combination already exists',
        'root_facet_not_selected' => 'Select at least one category or one manufacturer as root for this Filter page',
        'facet_type_required'     => 'Select filter type',
        'facet_value_required'    => 'Select filter value',
      ],
      'helpers' => [
        'root_facet'        => 'The root filter determines which filter will be the main and mandatory filter for this filter page',
        'additional_facets' => 'Additional filters define the combination of other filters together with the root filter for this filter page',
        'group_title'       => 'Filter pages are landing pages designed to more precisely target SEO queries. A filter page allows you to select a combination of a primary filter (such as category or manufacturer) and one or more additional filters (options, attributes, or tags) and create a page for that combination that possesses all the characteristics of a static page, such as a Title, H1 and a detailed description'
      ]
    ],
    'facets' => [
      'types' => [
        'category'     => 'Category',
        'manufacturer' => 'Manufacturer',
        'attribute'    => 'Attribute',
        'option'       => 'Option',
        'tag'          => 'Tag',
        'is_featured'  => 'Featured',
        'has_discount' => 'Discount',
        'bestseller'   => 'Bestseller',
        'best_reviews' => 'Best reviews',
      ]
    ],
    'product_reviews' => [
      'navigation_label'      => 'Product reviews',
      'model_label_singular'  => 'Product review',
      'fields' => [
        'product'            => 'Product',
        'type'               => 'Type',        
        'author'             => 'Author',
        'email'              => 'Email',
        'body'               => 'Review',
        'rating'             => 'Rating',
        'is_approved'        => 'Approved',
        'created_at'         => 'Created at',
        'reply_body'         => 'Reply',
        'locale'             => 'Language',
        'thread'             => 'Replies',
        'reply_author'       => 'Store reply author',
        'positive_notes'     => 'Pros',
        'negative_notes'     => 'Cons',
      ],
      'filters' => [
        'is_approved'        => 'Approved',
        'rating'             => 'Rating',
        'no_reply'           => 'No reply',
      ],
      'actions' => [
        'reply'              => 'Reply',
      ],
      'notifications' => [
        'reply_sent'         => 'Reply sent successfully',
      ],
      'buttons' => [
        'add_positive' => 'Add pro',
        'add_negative' => 'Add con',
      ],
      'labels' => [
        'admin_reply'        => 'Admin reply',
        'customer_review'    => 'Customer review',
        'store_reply_author' => 'Store reply author',
      ],
      'helpers' => [
        'group_title' => 'Here you can view product reviews, edit them, or write a response to a review'
      ]
    ]
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
        'date_anonymized_at'     => 'Anonymized at',
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
        'anonymize_confirm'     => 'I acknowledge, continue',
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
        'all_groups'             => 'All groups',
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
        'sluggable_id'          => 'Page ID',
        'robots'                => 'Robots tag',
        'index_follow'          => 'Index, Follow',
        'noindex_follow'        => 'Noindex, Follow',
        'index_nofollow'        => 'Index, Nofollow',
        'noindex_nofollow'      => 'Noindex, Nofollow',
      ],
      'helpers' => [
        'redirect'  => 'Redirect with 301 code from current URL to this one',
        'is_active' => 'If is not active, both URL and Redirect will return 410 code',
        'type'      => 'Page type, e.g. BlogPost, Product, ProductOption Category, Manufacturer, etc.',
        'id'        => 'Page ID so controller can determine which page to open',
        'robots'    => 'Robots meta tag is needed to tell search robots how this page should be added to the search index',
      ],
      'errors' => [
        'slug_taken'              => 'This URL is already in use',
        'alpha_dash'              => 'Only alpha-numeric and dashes allowed',
        'slug_duplicate_in_form'  => 'URL duplicate in this form', 
      ]
    ],
    'robots_editor' => [
      'navigation_label'  => 'Robots.txt',
      'fields' => [
        'edit_robots_file'          => 'Edit robots.txt',
        'robots_file_description'   => 'Every store has separate robots.txt file, they do not interfere',
      ],
      'messages' => [
        'file_saved'        => 'Robots.txt saved successfuly',
        'error_saving_file' => 'Error while saving Robots.txt file. You may need to make a symlink or set CHMOD to storage folder',
      ],
    ],
    'keywords' => [
      'navigation_label'      => 'Keywords',
      'model_label_singular'  => 'Keyword',
      'js' => [
        'column_seo_keyword'        => 'Keyword',
        'column_url'                => 'URL',
        'column_language'           => 'Language',
        'column_group'              => 'Group',
        'column_add_keyword'        => 'Add',
        'column_edit_keyword'       => 'Edit',
        'option_all_types'          => 'All types',
        'option_existing'           => 'Existing',
        'option_updated'            => 'Updated',
        'option_new'                => 'New',
        'option_imported'           => 'Imported',
        'button_add_row'            => 'Add row',
        'button_import'             => 'CSV import',
        'button_save_all'           => 'Save all',
        'button_add_to_beginning'   => 'Add to beginning',
        'button_add_to_end'         => 'Add to end',
        'button_replace'            => 'Replace',
        'button_find_duplicates'    => 'Find duplicates',
        'button_clear_filters'      => 'Clear filters',
        'button_add_to_page'        => 'Add to page',
        'button_copy'               => 'Copy',
        'button_delete'             => 'Delete',
        'button_move_up'            => 'Up',
        'button_move_down'          => 'Down',
        'button_remove_keyword'     => 'Delete',
        'text_search'               => 'Search',
        'confirm_replace'           => 'Replace the value with "{{ text }}" in all filtered rows?',
        'confirm_add_to_beginning'  => 'Add "{{ text }}" to the beginning of all filtered rows?',
        'confirm_add_to_end'        => 'Add "{{ text }}" to the end of all filtered rows?',
        'new_group_placeholder'     => 'New group name',
        'button_add_group'          => 'Add group',
      ],
    ],
    'meta_editor' => [
      'navigation_label'      => 'Meta editor',
      'model_label_singular'  => 'Meta editor',
      'fields' => [
        'formula'       => 'Formula',
        'target_field'  => 'Target field',
        'locale'        => 'Language',
        'currency_id'   => 'Currency',
      ],
      'buttons' => [
        'add_formula'                 => 'Add formula',
        'add_to_results'              => 'Add to Staging table',
        'save_formulas'               => 'Save formulas',
        'apply_formula'               => 'Generate',
        'save_staging'                => 'Save changes',
        'clear_staging'               => 'Clear changes',
        'delete'                      => 'Delete from staging',
        'edit'                        => 'Edit main record',
      ],
      'filters' => [
        'incomplete'                  => 'Incomplete fields',
        'incomplete_all'              => 'All',
        'incomplete_true'             => 'Only incomplete',
        'incomplete_false'            => 'Only filled',
      ],
      'helpers' => [
        'formula_placeholder'         => 'Supported tokens: {{name}}, {{manufacturer}}, {{price}}, {{minPrice}}, {{maxPrice}}',
        'results_table_title'         => 'Staging table',
        'results_table_descriptions'  => 'Select rows from the table above by clicking the "Add to Staging table" button. Rows appearing here can be edited or filled all at once by applying a Generation formula. Rows are saved only after hitting "Save" button. If some rows fail generation (e.g. missing token value) they will be skipped by saving process, you can edit them manually to fix errors and save afterwards',
        'formulas_heading'            => 'Generation formulas',
        'formulas_subheading'         => 'Create formulas here. Save formulas before applying them to rows',
        'formulas_section'            => 'Formulas HowTo',
        'formulas_description'        => ' 
          <p class="fi-callout-description">Basic usage: {{token}} in curly braces</p>
          <p class="fi-callout-description">Tokens supported:</p>
            <ul class="fi-callout-description">
              <li><b style="user-select: all">{{name}}</b> - entity name</li>
              <li><b style="user-select: all">{{parent}}</b> - entity parent, e.g. parent category, parent manufacturer, etc.</li>
              <li><b style="user-select: all">{{manufacturer}}</b> - manufacturer, only applicable for products and filter pages (if filter page has one)</li>
              <li><b style="user-select: all">{{store}}</b> - store name</li>
              <li><b style="user-select: all">{{price}}</b> - price, only applicable for products</li>
              <li><b style="user-select: all">{{minPrice}}</b> - minimum price. Lowest price in categories, manufacturers and filter pages. Lowest price for product option, if the product has one</li>
              <li><b style="user-select: all">{{maxPrice}}</b> - maximum price. Highest price in categories, manufacturers and filter pages. Highest price for product option, if the product has one</li>
              <li><b style="user-select: all">{{ratingAvg}}</b> - average rating for product. Category, manufacturer and filter page calculate average rating of all related products</li>
              <li><b style="user-select: all">{{productCount}}</b> - product count</li>
            </ul>
            <p class="fi-callout-description">Filters can be applied to tokens using colon: {{token:upper}}, {{token:lower}}, {{token:capitalize}}, {{token:number}}, {{token:currency}}. Example: {{store:upper}}, {{parent:lower}}</p>
            <p class="fi-callout-description">Tokens can be chained with vertical bar: {{manufacturer|parent|name}}. The first not empty token will be used in this case.</p>
            <p class="fi-callout-description">Literal placeholders can be applied using vertical bar and literal text in double quotes if the token is empty: {{token|"literal text"}}. Literal text will appear if all previously chained tokens are empty</p>
            <p class="fi-callout-description">Prefixes and suffixes can be applied to token by chaining them with vertical bar: {{minPrice|prefix:" as low as "|suffix:" and other deals "}}</p>
            <p class="fi-callout-description">Full examples:</p>
            <ul class="fi-callout-description">
              <li>Buy {{name}} and other {{parent:lower|"high quality products"}} from {{minPrice:currency}} in our store {{store:upper}}</li>
            </ul>
        ',
      ],
      'messages' => [
        'generate_confirmation'       => 'This action will apply formula to :target_field fields in language :locale', 
        'saved_with_errors'           => 'Some rows had errors while generation and were not saved. Faulty rows count - :count. See faulty rows in Staging table',
        'staging_saved'               => 'All rows saved! Good work!',
      ],
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
        'avatar'              => 'Displayed in every author\'s post and on author\'s page',
        'empty_state_message' => 'Blog authors allow blog posts to be categorized by author. This improves navigation and builds trust with search engines',
      ],
    ],
    'comments' => [
      'navigation_label'      => 'Comments',
      'model_label_singular'  => 'Comment',
      'fields' => [
        'post'               => 'Post',
        'type'               => 'Type',        
        'author'             => 'Author',
        'email'              => 'Email',
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
      ],
      'helpers' => [
        'empty_state_message' => 'Here you can edit visitor comments on your blog posts',
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
      'helpers' => [
        'manage_posts_title' => 'Posts in ":name" tag',
        'empty_state_message' => 'Blog post tags allow you to categorize articles by topic. Multiple tags can be assigned to a single article',
      ],
    ],
    'posts' => [
      'navigation_label'      => 'Posts',
      'model_label_singular'  => 'Post',
      'tabs' => [
        'content' => [
          'labels'  => [
            'main'        => 'Main settings',
            'image'       => 'Image',
            'name'        => 'Name',
            'sort_order'  => 'Sort order',
            'is_active'   => 'Active',
            'created_at'  => 'Created at',
          ],
          'helpers' => [
            'main'                => 'Blog post author, tags and display settings',
            'is_active'           => 'Article display setting. If the article is disabled, it will not appear on the Blog or on related product pages',
            'author'              => 'If an author is selected, the article page will display their name, avatar, a short description, and a link to the author\'s other articles',
            'tags'                => 'Article tags provide simple, "flat" navigation. An article can have multiple tags',
          ],
        ],
        'products' => [
          'label' => 'Products',
          'empty_state' => 'Here you can quickly attach products to this post',
        ],
        'comments' => [
          'label' => 'Comments',
          'labels' => [
            'table_heading'         => 'Manage comments',
            'edit_modal_heading'    => 'Edit blog post comment',
            'create_modal_heading'  => 'Create blog post comment',
          ],
        ],
      ],
      'table' => [
        'empty_state' => 'Your blog posts will be displayed here. You can link products to posts to display product links on the post pages',
      ]
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
      'ai_result'         => 'Result'
    ],
    'helpers' => [
      'name'                      => 'Name',
      'slug'                      => 'Should be unique in every language storewide',
      'description_short'         => 'Introductory text that is displayed above other page blocks. Recommended length - up to 500 characters',
      'description_full'          => 'Full description',
      'h1'                        => 'Include your primary target keyword, match closely to title tag',
      'meta_title'                => 'Recommended 50–60 chars. Longer title will be truncated by Google. Max length is 160 chars',
      'meta_description'          => 'Recommended 120 chars for mobile and 150 chars for desktop. Longer description will be truncated by Google. Max length is 255 chars',
      'image_description'         => 'Alt image attribute, best under 125 chars',
      'images_tab'                => 'Don\'t forget to set image\'s Alt attribue, it helps Search engines to understand what\'s depicted, thus better SEO ranking',
      'footer_tab'                => 'You can add some content here in form of tabs, mainly for SEO to enrich the page with missing keywords',
      'faq_tab'                   => 'FAQ will be displayed under the full description as collapsing Question/Answer blocks',
      'how_to_tab'                => 'HowTo will be displayed under the full description as list of steps and directions',
      'image_type_not_set_info'   => 'Image type <b>:type</b> is missing! Set this image in Design/Image settings to add images for this page',
      'image_type_not_set_title'  => 'Image dimensions are not set',
      'manager_page_title'        => ':entities in :name',
      'manager_page_attach_title'  => 'Attach :entities to :name',
      'manager_page_detach_title'  => 'Detach :entities from :name',
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
      'edit'              => 'Edit',
      'filter'            => 'Filter',
      'columns'           => 'Columns',
      'reorder'           => 'Reorder',
      'add_faq_row'       => 'Add new Question/Answer',
      'add_image_row'     => 'Add new image',
      'add_how_to_step'   => 'Add new HowTo step',
      'add_how_to_tool'   => 'Add new HowTo tool',
      'add_how_to_supply' => 'Add new HowTo supply',
      'add_footer_tab'    => 'Add new Footer tab',
      'create_slug'       => 'Create URL from name',
      'paste_h1'          => 'Paste name to H1',
      'paste_title'       => 'Paste H1 or name to Title',
      'paste_description' => 'Paste Title to the beginning of Description',
      'attach_record'     => 'Attach',
      'meta_editor'       => 'Meta Editor',
      'ai_action'         => 'AI generation',
      'ai_generate'       => 'Generate',
      'ai_action_replace' => 'Replace',
      'ai_action_append'  => 'Append to the end',
    ],
  ],
  'design' => [
    'menu_editor' => [
      'navigation_label'  => 'Main menu',
      'subheading'        => 'Change main menu items here',
      'blocks' => [
        'category'        => 'Category',
        'manufacturer'    => 'Manufacturer',
        'title'           => 'Title',
        'rich_text'       => 'Rich text',
        'product_card'    => 'Product card',
      ],
      'buttons' => [
        'add_element'     => 'Add menu item',
        'add_between'     => 'Add between',
      ],
    ],
    'layout_editor' => [
      'navigation_label'    => 'Layout editor',
      'subheading'          => 'Change pages layout here',
    ],
    'image_settings' => [
      'navigation_label'    => 'Images settings',
      'subheading'          => 'Here you can set your store logo and global image dimensions',
      'logo'                => 'Logo',
      'product'             => 'Product',
      'category'            => 'Category',
      'manufacturer'        => 'Manufacturer',
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
      'navigation_label'  => 'CSS Editor',
      'subheading'        => 'Here you can add override CSS styles that follow the main CSS file. The main CSS file is not changed',
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
  ],

  'store_homepage'  => ['navigation_label' => 'Homepage',],

  // Store contacts form
  'store_contacts' => [
    'navigation_label' => 'Contacts',
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
    'navigation_label' => 'Settings',
    'tabs' => [
      'delivery'        => 'Delivery',
      'checkout'        => 'Checkout',
      'legal'           => 'Legal settings',
      'taxes'           => 'Taxes',
      'analytics'       => 'Analytics',
      'seo_defaults'    => 'SEO defaults',
      'notifications'   => 'Notifications',
      'maintenance'     => 'Maintenance',
    ],
    'delivery' => [
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
    'checkout' => [
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

  // Stores
  'stores' => [
    'store_settings' => [
      'tabs' => [
        'ai_settings' => [
          'tab_label' => 'AI Settings',
          'labels' => [
            'prompt_settings'   => 'Prompt settings',
            'providers'         => 'Service providers',
            'name'              => 'Prompt name',
            'prompt'            => 'Prompt',
            'prompt_text'       => 'Prompt text',
            'prompt_extra'      => 'Prompt extra',
            'api_key'           => 'API Key',
            'provider'          => 'Provider',
            'endpoint'          => 'Endpoint URL',
            'model'             => 'Model'
          ],
          'helpers' => [
            'prompt_settings'   => 'Create predefined prompts here. You will be able to select one of the prompts or edit it before sending the request to the AI',
            'providers'         => 'Add an AI service provider of your choice here. You can add multiple providers and select the one that fits your needs later on all edit pages in the modal dialog',
            'prompt'            => 'Write the prompt for the AI model here',
            'select_prompt'     => 'Select the provider and the prompt',
            'no_settings'       => 'To use AI features first set an AI provider and API key in <a href=":url" style="color: var(--primary-500); font-weight: 600; text-decoration: underline;" target="_blank">Store settings</a>',
          ],
          'buttons' => [
            'add_prompt'        => 'Add new prompt',
            'add_provider'      => 'Add provider',
          ],
          'columns' => [
            'prompts'           => 'Prompts',
            'providers'         => 'Providers',
            'provider_settings' => 'Settings',
            'is_default'        => 'Default',
          ],
        ],
      ],
    ],
    // Info pages
    'info_pages' => [
      'navigation_label'     => 'Info pages',
      'model_label_singular' => 'Info page',
      'helpers' => [
        'group_title' => 'Info pages are pages containing key information about the store, such as contact details, delivery and payment policies, and legal terms'
      ]
    ],
  ],

  // Users
  'users' => [
    'navigation_label'     => 'Users',
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
  ],

  'global' => [
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
        'locale'                    => 'Two-letter language designation in accordance with the ISO standard. For example: en, uk, pl. Locale is used in HTML header to tell search engines page language',
        'default_currency'          => 'Default currency is defined to display prices in relation to language so search engines see correct prices related to language. This does not affect customers, they still can change displayed currency',
        'fulltext_search_language'  => 'Fulltext search morphology dictionary. You may use custom dictionary for PostgreSQL but you will need to install it first'
      ],
    ],

    // Countries
    'countries' => [
      'navigation_label'      => 'Countries',
      'model_label_singular'  => 'Country',
      'fields' => [
        'name'                  => 'Name',
        'localization_settings' => 'Localization settings',
        'iso_code'              => 'ISO code',
        'phone_code'            => 'Phone code',
        'default_currency_id'   => 'Default currency',
        'is_eu_member'          => 'EU member',
        'regions'               => 'Regions',
        'region'                => 'Region',
        'add_region'            => 'Add region',
        'is_active'             => 'Active',
      ],
      'helpers' => [
        'add_region' => 'Not necessary, you can skip this',
        'iso_code'   => 'Country ISO code in two letter ISO 3166-1 alpha-2 format: UA, PL, US, GB, etc. ISO code is used in JSON-LD microdata to display Google rich snippets',
        'phone_code' => 'Phone code of country: +380, +48, +1, +44 to format phone numbers on checkout process',
      ],
    ],

    'stores' => [
      'navigation_label'      => 'Stores',
      'model_label_singular'  => 'Store',
      'labels' => [
        'main'                => 'Main settings',
        'localization'        => 'Localization settings',
        'name'                => 'Name',
        'host'                => 'Domain name',
        'languages'           => 'Languages',
        'currencies'          => 'Currencies',
        'countries'           => 'Countries',
        'is_active'           => 'Active',
        'add_language'        => 'Add store language',
        'add_currency'        => 'Add store currency',
        'add_country'         => 'Add store country',
      ],
      'relations' => [
        'languages' => [
          'is_default' => 'Default language',
          'is_active'  => 'Active for store',
        ],
      ],
      'helpers' => [
        'main'              => 'Set store\'s domain, store name that will be displayed in admin panel and store status',
        'localization'      => 'Set the languages your customers will see, the currencies they can pay with, and the countries your store will ship products to',
        'name'              => 'Name to display in admin panel',
        'host'              => 'Store domain name name without http:// or https://. <br> Example <u><b>store.com</b></u>',
        'is_active'         => 'Global store status. If the store is set inactive, the website will stop opening. Use in case on maintenance',
        'host_placeholder'  => 'store.com'
      ],
      'errors' => [
        'languages'         => 'At least one language must be active and selected as the default language',
        'currencies'        => 'At least one currency must be active',
        'countries'         => 'At least one country must be active',
      ],
    ],

    // Store wizard
    'store_wizard' => [
      'navigation_label' => 'Store Wizard',
      'fields' => [
        'existing_currency' => 'Use existing currency',
        'new_currency'      => 'Create currency',
        'existing_language' => 'Use existing language',
        'new_language'      => 'Create language',
        'existing_country'  => 'Use existing country',
        'new_country'       => 'Create country',
      ],
      'messages' => [
        'currency_language_hint' => 'Default currency for this language will be set as :currency from the previous step. You can always change this setting in Global settings/Languages admin section',
        'currency_country_hint'  => 'Default currency for this country will be set as :currency from the previous step. You can always change this setting in Global settings/Countries admin section',
      ],
      'steps' => [
        'currency'  => 'Currency',
        'language'  => 'Language',
        'country'   => 'Country',
        'store'     => 'Store',
      ],
      'actions' => [
        'create' => 'Create store'
      ]
    ]
  ]
];