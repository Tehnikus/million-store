<?php 
return [
  // Common
  'messages' => [
    'settings_saved'            => 'Налаштування збережені',
    'contacts_saved'            => 'Контакти збережені',
    'homepage_saved'            => 'Головна сторінка збережена',
    'file_saved'                => 'Файл збережено',
    'error_saving_file'         => 'Помилка при збереженні файлу',
    'delete_restricted_title'   => 'Увага! Ви не можете це видалити через інші залежні дані!',
    'delete_cascade_warning'    => 'Увага! Якщо ви видалите цеб інші залежні дані також будуть видалені!',
  ],
  'customers' => [
    'customer' => [
      'navigation_label'      => 'Покупці',
      'model_label_singular'  => 'Покупець',
      'fields' => [
        'customer'          => 'Покупець',
        'is_approved'       => 'Схвалений',
        'customer_group_id' => 'Група',
        'email'             => 'Email',
        'first_name'        => 'Ім\'я',
        'last_name'         => 'Прізвище',
        'phone'             => 'Телефон',
        'addresses'              => 'Адреси покупця',
        'addresses_label'        => 'Назва адреси',
        'addresses_placeholder'  => 'Наприклад: Дім, Робота',
        'is_default_shipping'    => 'Адреса доставки за замовченням',
        'is_default_billing'     => 'Адреса оплати за замовченням',
        'add_address'            => 'Додати адресу',
        'locale'            => 'Бажана мова покупця',
        'password'          => 'Пароль',
        'marketing_opt_in'  => 'Згода на маркетинг',
        'gdpr_consent_at'   => 'Згода GDPR',
        'anonymized_at'     => 'Дата анонімізації',
        'deleted_at'        => 'Дата видалення',
        'created_at'        => 'Дата реєстрації',
        'updated_at'        => 'Дата зміни',
        'presonal_data'     => 'Персональні дані',
        'store_data'        => 'Дані магазину',
        'email_data'        => 'Email',
        'email_verified_at' => 'Дата верифікації Email',
        'company_data'      => 'Компанія',
        'company_name'      => 'Назва компанії',
        'vat_number'        => 'Номер ІПН/ЕДРПОУ',
        'privacy_data'      => 'Конфіденційність',
        'anonymize'         => 'Анонімізувати дані'
      ],
      'messages' => [
        'anonymize_title'       => 'Анонімізувати дані користувача?',
        'anonymize_description' => 'Ця дія є незворотною. Усі персональні дані користувача будуть замінені на фіктивні заглушки',
        'anonymize_confirm'     => 'Я розумію, продовжити',
      ]
    ],
    'customer_groups' => [
      'navigation_label'      => 'Групи покупців',
      'model_label_singular'  => 'Група покупців',
      'fields' => [
        'name'                   => 'Назва',
        'code'                   => 'Тип',
        'price_modifier_percent' => 'Модифікатор ціни',
        'free_shipping'          => 'Безкоштовна доставка',
        'show_prices'            => 'Показувати ціни',
        'requires_approval'      => 'Потребує схвалення',
        'tax_exempt'             => 'Показувати податки',
        'is_default'             => 'За замовчуванням для нових користувачів',
        'sort_order'             => 'Порядок сортування',
        'is_active'              => 'Активна',
        'group_settings'         => 'Налаштування групи',
      ],
      'helpers' => [
        'price_modifier_percent' => 'Тут можна вказати позитивне або негативне значення, яке буде застосовано до всіх цін у вашому магазині',
        'code'                   => 'Загальний тип групи покупців, наприклад: нові, постійні, B2B тощо.'
      ]
    ]
  ],
  'seo' => [
    'slugs' => [
      'model_label_singular'  => 'URL',
      'navigation_label'      => 'URLs',
      'fields' => [
        'url'                   => 'URL',
        'language'              => 'Мова',
        'type'                  => 'Тип',
        'id'                    => 'ID',
        'redirect'              => 'Редирект 301',
        'sluggable_id'          => 'ID страницы'
      ],
      'helpers' => [
        'redirect'  => 'Переадресовувати запит з кодом 301 з поточного URL на цей',
        'is_active' => 'Якщо URL не активний, то і поточний URL, і Редирект буде віддавати код 410',
        'type'      => 'Тип сторінки, наприклад BlogPost, Product, ProductOption Category, Manufacturer і т.д.',
        'id'        => 'ID сторінки щоб контролер знав яку саме сторінку відкривати',
      ],
      'errors' => [
        'slug_taken' => 'Цей URL вже використовується',
        'alpha_dash' => 'Дозволені тільки латинські літери, цифри і тире замість пробілів',
      ]
    ],
    'robots' => [
    ],
  ],
  'blog' => [
    'authors'  => [
      'navigation_label'      => 'Автори',
      'model_label_singular'  => 'Автор',
      'fields'  => [
        'avatar'          => 'Аватар',
        'name'            => 'Ім\'я',
        'sort_order'      => 'Сортування',
        'is_active'       => 'Активний',
        'created_at'      => 'Дата створення',
        'posts_count'     => 'Кілкість статей',
        'social_links'    => 'Соціальні мережі автора',
        'social_platform' => 'Платформа',
        'social_url'      => 'URL',
      ],
      'buttons' => [
        'add_social_link' => 'Додати соціальну мережу автора'
      ],
      'helpers' => [
        'avatar' => 'відображається в кожному пості і на сторінці автора'
      ],
    ],
    'comments' => [
      'navigation_label'      => 'Відгуки',
      'model_label_singular'  => 'Відгук',
      'fields' => [
        'post'               => 'Стаття',
        'type'               => 'Тип',        
        'author'             => 'Автор',
        'body'               => 'Відгук',
        'rating'             => 'Рейтинг',
        'is_approved'        => 'Схавлений',
        'created_at'         => 'Дата створення',
        'reply_body'         => 'Відповідь',
        'locale'             => 'Мова',
        'thread'             => 'Відповіді',
        'reply_author'       => 'Автор відповіді',
      ],
      'filters' => [
        'is_approved'        => 'Схвалені коментарі',
        'rating'             => 'Рейтинг',
      ],
      'actions' => [
        'reply'              => 'Відповісти',
      ],
      'notifications' => [
        'reply_sent'         => 'Відповідь додана',
      ],
      'labels' => [
        'admin_reply'        => 'Відповідь админістратора',
        'customer_comment'   => 'Коментар кліента',
        'store_reply_author' => 'Адміністратор',
      ]
    ],
    'tags'  => [
      'navigation_label'      => 'Теґи',
      'model_label_singular'  => 'Теґ',
      'fields'  => [
        'name'        => 'Назва',
        'sort_order'  => 'Сортування',
        'is_active'   => 'Активний',
        'is_menu'     => 'Відображати в меню',
        'created_at'  => 'Дата створення',
        'posts_count' => 'Статті',
      ],
      'helpers' => [],
    ],
    'posts' => [
      'navigation_label'      => 'Статті',
      'model_label_singular'  => 'Стаття',
      'fields'  => [
        'image'       => 'Зображення',
        'name'        => 'Назва',
        'sort_order'  => 'Сортування',
        'is_active'   => 'Активна',
        'created_at'  => 'Дата створення',
      ],
      'helpers' => [
        'tags' => 'З якими тегами пов\'язана ця стаття'
      ],
    ]
  ],
  'common' => [
    'fields' => [
      'name'              => 'Назва',
      'slug'              => 'URL',
      'description_short' => 'Короткий опис',
      'description_full'  => 'Повний опис',
      'h1'                => 'H1',
      'meta_title'        => 'Meta title',
      'meta_description'  => 'Meta description',
      'faq_question'      => 'Питання',
      'faq_answer'        => 'Відповідь',
      'how_to_step_name'  => 'Назва крока/етапа',
      'how_to_step_text'  => 'Опис крока/етапа',
      'image'             => 'Зображення',
      'image_description' => 'Опис',
      'footer_tab'        => 'Назва вкладки',
      'footer_content'    => 'Вміст вкладки',
      'created_at'        => 'Дата створення',
      'updated_at'        => 'Дата змінення',
      'is_active'         => 'Активний',
    ],
    'helpers' => [
      'name'              => 'Ім\'я',
      'slug'              => 'Має бути унікальним кожною мовою в магазине',
      'description_short' => 'Вступний текст, який відображається над всіма іншими блоками. рекомендована довжина - до 500 символів',
      'description_full'  => 'Повний опис',
      'h1'                => 'Додайте основні ключові слова, зробіть схожим на title',
      'meta_title'        => 'Додайте основні ключові слова. Рекомендована довжина - 50–60 символів. Довший заголовок буде обрізаний Google. Максимальна довжина - 150 символів',
      'meta_description'  => 'Рекомендована довжина - 120 символів для мобільних пристроїв та 150 символів для настільних комп\'ютерів. Довший опис буде обрізаний Google. Максимальна довжина - 255 символів',
      'image_description' => 'Alt атрибут зображення, найкраще до 125 символів',
      'images_tab'        => 'Не забудьте заповнити атрибут Alt для зображення, це допоможе пошуковим системам зрозуміти, что зображено, та, відповідно, покращить SEO-рейтинг',
      'footer_tab'        => 'Тут можна додати контент у вигляді вкладок, в основному для SEO, щоб наповнити сторінку відсутніми ключовими словами',
      'faq_tab'           => 'FAQ відобразиться під повним описом у вигляді згортаємих блоків Питання/відповідь',
      'how_to_tab'        => 'HowTo відобразиться під повним описом у вигляді кроків і вказань',
      'image_type_not_set_title' => 'Розміри зображень не задано',
      'image_type_not_set_info'  => 'Відсутні настройки розірів зображень для <b>:type</b>! Встановіть ці розміри в налаштуваннях у розділі Дизайн/Зображення, щоб додати картинки до цієї сторінки',
    ],
    'tabs' => [
      'content'     => 'Контент',
      'description' => 'Опис',
      'faq'         => 'FAQ',
      'how_to'      => 'HowTo',
      'images'      => 'Зображення',
      'footer'      => 'Футер',
      'relations'   => 'Зв\'язки',
      'general'     => 'Основні',
    ],
    'buttons' => [
      'save'              => 'Зберегти',
      'cancel'            => 'Скасувати',
      'add_faq_row'       => 'Додати нове Питання/Відпвідь',
      'add_image_row'     => 'Додати нове зображення',
      'add_how_to_step'   => 'Додати крок HowTo',
      'add_how_to_tool'   => 'Додати інструмент HowTo',
      'add_how_to_supply' => 'Додати необхіну річ для HowTo',
      'add_footer_tab'    => 'Додати вкладку Footer',
    ],
  ],
  'design' => [
    'image_types' => [
      'title'               => 'Типи та розміри зображень',
      'subheading'          => 'Тут можна змінити логотип магазина і задати глобальні розміри зображень',
      'logo'                => 'Логотип',
      'product'             => 'Товар',
      'category'            => 'Категорія',
      'blog'                => 'Блог',
      'product_miniature'   => 'Товар - мініатюра',
      'product_main'        => 'Товар - основне',
      'product_options'     => 'Товар - опції',
      'blog_miniature'      => 'Блог - мініатюра',
      'blog_main'           => 'Блог - основне',
      'category_miniature'  => 'Категорія - мініатюра',
      'category_main'       => 'Категорія - основне',
      'slider'              => 'Слайдер',
      'misc'                => 'Інші',
      'image_type'          => 'Тип зображення',
      'width'               => 'Ширина (px)',
      'height'              => 'Висота (px)',
    ],
    'css_editor' => [
      'subheading' => 'Тут ви можете додати інші CSS стилі, які слідують після основного CSS файлу. Основний CSS файл не змінюється',
      'css' => 'CSS',
    ],
  ],
  'navigation' => [
    'groups' => [
      'orders'          => 'Замовлення',
      'catalog'         => 'Каталог',
      'blog'            => 'Блог',
      'stock'           => 'Склад',
      'customers'       => 'Клієнти',
      'seo'             => 'SEO',
      'design'          => 'Дизайн',
      'store_settings'  => 'Налаштування магазина',
      'global_settings' => 'Глобальні налаштування',
    ],
    'items' => [
      'store_contacts'              => 'Контакти',
      'store_settings'              => 'Налаштування',
      'store_homepage_description'  => 'Головна',
      'image_settings'              => 'Зображення',
      'css_editor'                  => 'CSS Редактор',
      'robots_editor'               => 'Robots.txt',
    ],
  ],

  // Info pages
  'info_pages' => [
    'navigation_label'     => 'Інфо сторінки',
    'model_label_singular' => 'Інфо сторінка',
  ],

  // Store contacts form
  'store_contacts' => [
    'fields' => [
      'legal_infos'                => 'Юридична інформація',
      'organization_description'   => 'Опис організації',
      'local_business_description' => 'Опис фізичного магазину',
      'legal_name'                 => 'Юридична назва',
      'address_details'            => 'Адреса',
      'country'                    => 'Країна',
      'region'                     => 'Регіон або код області',
      'city'                       => 'Місто',
      'street'                     => 'Вулиця, номер будинку',
      'iso_code'                   => 'ISO-код країни',
      'postal_code'                => 'Поштовий індекс',
      'geo_infos'                  => 'Географічні координати',
      'latitude'                   => 'Широта',
      'longitude'                  => 'Довгота',
      'email'                      => 'Електронна пошта',
      'open_hours'                 => 'Години роботи',
      'day'                        => 'День тижня',
      'opens'                      => 'Відчиняється',
      'closes'                     => 'Зачиняється',
      'phones'                     => 'Номери телефонів',
      'phone_name'                 => 'Назва телефону (Робочий, Сервіс, Підтримка тощо)',
      'phone_number'               => 'Номер телефону',
      'social_links'               => 'Посилання на соціальні мережі',
      'social_link_icon'           => 'Іконка',
      'social_link_title'          => 'Назва',
      'social_link_link'           => 'Посилання',
      'social_contacts'            => 'Контакти в месенджерах (WhatsApp, Viber тощо)',
      'social_contact_icon'        => 'Іконка',
      'social_contact_title'       => 'Назва',
      'social_contact_link'        => 'Посилання',
    ],
    'helpers' => [
      'legal_name'                 => 'Юридична назва компанії. Використовується в мікророзмітці JSON-LD та на сторінці «Про нас»',
      'organization_description'   => 'Короткий опис вашої компанії у кількох реченнях. Використовується в мікророзмітці JSON-LD та на сторінці «Про нас»',
      'local_business_description' => 'Короткий опис точки продажів (фізичного магазину) у кількох реченнях. Використовується в мікророзмітці JSON-LD та на сторінці «Про нас». Якщо у вас немає офлайн-магазину, це поле можна залишити порожнім',
      'country'                    => 'Назва країни звичайною мовою. Використовується на сторінці «Про нас» та в мікророзмітці JSON-LD для відображення розширених результатів пошуку Google',
      'region'                     => 'Назва регіону звичайною мовою. Використовується на сторінці «Про нас» та в мікророзмітці JSON-LD для відображення розширених результатів пошуку Google',
      'city'                       => 'Назва міста звичайною мовою. Використовується на сторінці «Про нас» та в мікророзмітці JSON-LD для відображення розширених результатів пошуку Google',
      'street'                     => 'Назва вулиці та номер будинку звичайною мовою. Використовується на сторінці «Про нас» та в мікророзмітці JSON-LD для відображення розширених результатів пошуку Google',
      'iso_code'                   => 'Дволітерний ISO-код країни у форматі ISO 3166-1 alpha-2: UA, PL, US, GB тощо. Використовується на сторінці «Про нас» та в мікророзмітці JSON-LD',
      'postal_code'                => 'Поштовий індекс за адресою реєстрації або розташування компанії. Підвищує довіру пошукових систем до інформації про компанію',
      'latitude'                   => 'Формат: 50.450100 (десяткові градуси). Допомагає Google Maps визначити розташування вашого магазину. Якщо у вас немає точки продажів (фізичного магазину), це поле можна залишити порожнім',
      'longitude'                  => 'Формат: 30.523400 (десяткові градуси). Допомагає Google Maps визначити розташування вашого магазину. Якщо у вас немає точки продажів (фізичного магазину), це поле можна залишити порожнім',
      'open_hours'                 => 'Приклад дня тижня: <u><b>Monday</b></u> - завжди англійською,<br>приклад часу роботи: <u><b>10:30</b></u>',
      'social_contacts'            => 'Посилання на месенджери: Viber, Facebook messenger, WhatsApp, тощо. Іконки можна додати в форматі SVG, назва - просто заголовок<br><b>Приклади посилань:</b>
        <ul class="fi-sc-unordered-list">
          <li><u><b>Viber:</b></u> viber://contact?number=%2B{НомерТелефонаБезЗнакаПлюс}</li>
          <li><u><b>WhatsApp:</b></u> https://wa.me/{НомерТелефонаБезЗнакаПлюс}</li>
          <li><u><b>Telegram:</b></u> https://t.me/{ВашАкаунтТелеграм}</li>
          <li><u><b>Facebook Messenger:</b></u> https://m.me/{ВашАкаунтФейсбук}</li>
        </ul>
      ',
    ],
    'buttons' => [
      'add_open_hours'     => 'Додати час роботи',
      'add_phone'          => 'Додати номер телефона',
      'add_social_link'    => 'Додати соціальне посилання',
      'add_social_contact' => 'Додати соціальний контакт',
    ],
  ],

  // Store settings
  'store_settings' => [
    'tabs' => [
      'delivery_settings'       => 'Доставка',
      'checkout_settings'       => 'Замовлення',
      'legal_settings'          => 'Правові налаштування',
      'tax_settings'            => 'Податки',
      'analytics_settings'      => 'Аналітка',
      'seo_defaults'            => 'SEO за замовченням',
      'notification_settings'   => 'Повідомлення',
      'maintenance_settings'    => 'Режим обслуговування',
    ],
    'delivery_settings' => [
      'fields' => [
        'transit_min'   => 'Мінімальний час доставки (дні)',
        'transit_max'   => 'Максимальний час доставки (дні)',
        'handling_min'  => 'Мінімальний час обробки (дні)',
        'handling_max'  => 'Максимальний час обробки (дні)',
        'return_cost'   => 'Вартість повернення',
      ],
      'helpers' => [
        'transit'     => 'Це значення використовується в розмітці JSON-LD для відображення розширених сніпетів Google і лише як резервне значення. Фактичний час доставки може відрізнятися',
        'handling'    => 'Це значення використовується в розмітці JSON-LD для відображення розширених сніпетів Google і лише як резервне значення. Фактичний час обробки може відрізнятися',
        'return_cost' => 'Це значення використовується в розмітці JSON-LD для відображення розширених сніпетів Google і лише як резервне значення',
      ]
    ],
    'checkout_settings' => [
      'fields' => [
        'minimal_order_total'     => 'Minimal checkout total',
        'agreement_pages'         => 'Agreement pages',
        'service_agreement_page'  => 'Service agreement page',
        'return_rules_page'       => 'Return rules page',
        'checkout_custom_fields'  => 'Додаткові поля',
        'checkout_address_fields' => 'Поля адреси',
        'firstname'               => 'Ім\'я',
        'lastname'                => 'Прізвище',
        'building'                => 'Будинок',
        'apartment'               => 'Квартира',
        'postal_code'             => 'Поштовий індекс',
        'region'                  => 'Регіон',
        'phone'                   => 'Телефон',
        'payer'                   => 'Платник',
        'country'                 => 'Країна',
        'city'                    => 'Місто',
        'street'                  => 'Вулиця',
        'vat_number'              => 'Номер ІПН',
        'company'                 => 'Компанія',
        'time'                    => 'Вибір часу',
        'date'                    => 'Вибір дати',
        'datetime'                => 'Вибір дати та часу',
        'text'                    => 'Текст',
        'textarea'                => 'Текстове поле (textarea)',
        'checkbox'                => 'Чекбокс',
        'add_field'               => 'Додати поле',
        'field_type'              => 'Тип поля',
        'field_name'              => 'Назва поля',
        'is_required'             => 'Обов\'язкове',
      ],
      'helpers' => [
        'checkout_fields' => 'Поля оформлення замовлення, такі як адреса та ім\'я одержувача, які будуть відображатися в кошику',
        'custom_fields'   => 'Додаткові поля оформлення замовлення, такі як бажана дата доставки',
        'service_agreement_page' => 'Буде відображатися як прапорець «Я прочитав(-ла) і погоджуюся з Угодою про надання послуг» на сторінці оформлення замовлення. Пропустіть це, якщо вам це не потрібно',
        'return_rules_page' => 'Буде відображатися як прапорець «Я прочитав(-ла) і погоджуюся з Правилами повернення» на сторінці оформлення замовлення. Пропустіть це, якщо вам це не потрібно',
      ],
    ],
  ],

  // Countries
  'countries' => [
    'navigation_label'      => 'Країни',
    'model_label_singular'  => 'Країна',
    'fields' => [
      'name'                 => 'Назва',
      'iso_code'             => 'ISO код',
      'phone_code'           => 'Код телефона',
      'default_currency_id'  => 'Основна валюта',
      'is_eu_member'         => 'Входить в ЕС',
      'regions'              => 'Області',
      'region'               => 'Область',
      'add_region'           => 'Додати область',
      'is_active'            => 'Активна',
    ],
    'helpers' => [
      'add_region'  => 'Необов\'язково, ви можете пропустити це',
      'iso_code'    => 'ISO-код країни у дволітерному форматі ISO 3166-1 alpha-2: UA, PL, US, GB тощо. ISO-код використовується в мікроданих JSON-LD для відображення розширених сніпетів Google',
      'phone_code'  => 'Телефонний код країни: +380, +48, ​​+1, +44 для форматування телефонів під час оформлення замовлення',
    ],
  ],

  // Currencies
  'currencies' => [
    'navigation_label'      => 'Валюти',
    'model_label_singular'  => 'Валюта',
    'fields' => [
      'name'          => 'Назва',
      'iso_code'      => 'Код ISO',
      'sign'          => 'Знак валюти',
      'rate'          => 'Курс',
      'rate_default'  => 'Основна',
      'is_active'     => 'Активна',
    ],
    'helpers' => [
      'name'          => 'Назва валюти в панелі адміністратора',
      'iso_code'      => 'Код ISO використовується в розмітці JSON-LD',
      'sign'          => 'Знак використовується для відображення цін у фронтенді та бекенді',
      'rate'          => 'Обмінний курс до вашої валюти за замовчуванням',
      'rate_default'  => 'Ця валюта використовується для розрахунку курсів інших валют. Ця настройка задається глобально для всіх магазинів - тільки для розрахунку курсів',
    ],
  ],

  // Languages
  'languages' => [
    'navigation_label'      => 'Мови',
    'model_label_singular'  => 'Мова',
    'fields' => [
      'flag'                      => 'Прапорець',
      'name'                      => 'Назва',
      'iso_code'                  => 'Код ISO',
      'locale'                    => 'Локаль',
      'is_active'                 => 'Активний',
      'stores'                    => 'Магазини',
      'default_currency'          => 'Валюта',
      'fulltext_search_language'  => 'Словник PostgreSQL',
      'is_default'                => 'Основна мова'
    ],
    'helpers' => [
      'name'                      => 'Назва мови в панелі адміністратора',
      'iso_code'                  => 'Приклад: <b>en-US</b>. Код ISO використовується в розмітці JSON-LD',
      'locale'                    => 'Локаль використовується в заголовку HTML, щоб вказати пошуковим системам мову сторінки',
      'default_currency'          => 'Валюта за замовчуванням визначена для відображення цін залежно від мови, щоб пошукові системи бачили правильні ціни залежно від мови. Це не впливає на клієнтів, вони все ще можуть змінювати відображену валюту',
      'fulltext_search_language'  => 'Морфологічний словник повнотекстового пошуку. Ви можете використовувати власний словник для PostgreSQL, але спочатку його потрібно буде встановити'
    ],
  ],

  // Stores
  'stores' => [
    'navigation_label'      => 'Магазини',
    'model_label_singular'  => 'Магазин',
    'fields' => [
      'name'                => 'Назва',
      'host'                => 'Домен',
      'languages'           => 'Мови',
      'currencies'          => 'Валюти',
      'countries'           => 'Країни',
      'is_active'           => 'Активний',
    ],
    'relations' => [
      'languages' => [
        'is_default' => 'Основна мова',
        'is_active'  => 'Активний',
      ],
    ],
    'helpers' => [
      'name'          => 'Назва магазина, тільки для адмінки',
      'host'          => 'Доменне ім\'я магазина без http:// або https://. <br> Приклад: <u><b>store.com</b></u>',
      'on_save_title' => 'Інформація',
      'on_save_info'  => 'Налаштування мов, валют та країн з\'являться після збереження магазину. Не забудьте їх налаштувати',
    ],
  ],

  // Users
  'users' => [
    'navigation_label' => 'Користувачі',
    'model_label_singular' => 'Користувач',
    'fields' => [
      'name'        => 'Ім\'я',
      'email'       => 'Email',
      'role'        => 'Тип',
      'password'    => 'Пароль',
      'locale'      => 'Мова інтерфейса',
      'created_at'  => 'Дата створення',
      'is_active'   => 'Активний',
    ],
  ]
];