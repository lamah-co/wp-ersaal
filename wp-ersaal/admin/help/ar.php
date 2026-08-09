<?php
return [
    [
        'id' => 'quick-start',
        'title' => 'البداية السريعة',
        'content' => '
            <div class="ersaal-help-quick-start">
                <ol>
                    <li><a href="' . esc_url(admin_url('admin.php?page=ersaal-settings')) . '">افتح صفحة الإعدادات</a>.</li>
                    <li>أدخل رابط الـ API ومفتاح API Key.</li>
                    <li>اضغط على Test Connection للتحقق من الاتصال.</li>
                    <li>أدخل Sender ID المعتمد من حسابك.</li>
                    <li><a href="' . esc_url(admin_url('admin.php?page=ersaal-send-message')) . '">افتح صفحة Send SMS</a>.</li>
                    <li>أرسل رسالة تجريبية.</li>
                    <li>قم بإعداد WooCommerce إذا كنت ترغب في إرسال تلقائي للطلبات.</li>
                </ol>
            </div>
        '
    ],
    [
        'id' => 'getting-started',
        'title' => 'مقدمة',
        'content' => '
            <p><strong>إضافة Ersaal SMS</strong> تقوم بربط موقعك في ووردبريس ومتجر ووكومرس ببوابة إرسال للرسائل النصية.</p>
            <h3>المتطلبات الأساسية</h3>
            <ul>
                <li>حساب فعّال على منصة Ersaal.</li>
                <li>رابط الـ API ومفتاح API Key.</li>
                <li>Sender ID معتمد.</li>
            </ul>
        '
    ],
    [
        'id' => 'connection-account',
        'title' => 'الاتصال والحساب',
        'content' => '
            <p>من خلال <a href="' . esc_url(admin_url('admin.php?page=ersaal-settings')) . '">الإعدادات</a>، يمكنك إدارة اتصالك ومتابعة حالة الباقة.</p>
            <ul>
                <li><strong>Project Status:</strong> يجب أن يكون Active حتى تتمكن من الإرسال.</li>
                <li><strong>Wallet Balance:</strong> الرصيد المالي المتاح للإرسال. ظهور "Unknown" لا يعني بالضرورة رصيداً صفرياً.</li>
                <li><strong>Subscription:</strong> يوضح حدود الاستخدام لرسائل OTP والرسائل العادية.</li>
                <li><strong>Sender ID:</strong> يجب إرسال كافة الرسائل من اسم مرسل (Sender ID) معتمد مسبقاً.</li>
            </ul>
        '
    ],
    [
        'id' => 'send-sms',
        'title' => 'إرسال رسالة SMS',
        'content' => '
            <p>يمكنك إرسال رسائل يدوية من صفحة <a href="' . esc_url(admin_url('admin.php?page=ersaal-send-message')) . '">Send SMS</a>.</p>
            <ul>
                <li><strong>رقم الهاتف:</strong> يتم تنسيقه تلقائياً حسب متطلبات البوابة.</li>
                <li><strong>حروف الرسالة:</strong> تدعم الإضافة الترميزين GSM-7 و Unicode. الرسائل العربية تستخدم Unicode (تُحسب 70 حرفاً لكل جزء).</li>
            </ul>
            <div class="notice notice-warning inline"><p><strong>مهم جداً:</strong> قبول الرسالة (Accepted) من Ersaal لا يعني بالضرورة وصولها إلى هاتف المستلم، بل يعني أن الطلب قيد المعالجة.</p></div>
        '
    ],
    [
        'id' => 'woocommerce',
        'title' => 'ربط WooCommerce',
        'content' => '
            <p>لأتمتة الرسائل مع الطلبات، اذهب إلى <a href="' . esc_url(admin_url('admin.php?page=ersaal-settings&tab=woocommerce')) . '">Settings > WooCommerce</a>.</p>
            <h3>الأحداث المدعومة</h3>
            <ul>
                <li>طلب جديد (New Order)</li>
                <li>قيد التنفيذ (Processing)</li>
                <li>مكتمل (Completed)</li>
                <li>ملغي (Cancelled)</li>
            </ul>
            <h3>المتغيرات المتاحة</h3>
            <p>يمكنك استخدام هذه المتغيرات داخل نصوص الرسائل:</p>
            <code>{customer_name}</code>, <code>{order_number}</code>, <code>{order_total}</code>, <code>{order_status}</code>, <code>{site_name}</code>, <code>{billing_first_name}</code>, <code>{billing_last_name}</code>
            <p><strong>مثال:</strong> مرحباً {billing_first_name}، طلبك رقم {order_number} أصبح الآن {order_status}.</p>
            <h3>إرسال يدوي من الطلب</h3>
            <p>داخل صفحة أي طلب في WooCommerce، ستجد صندوق <strong>Ersaal SMS</strong> الجانبي والذي يمكنك من إرسال رسالة يدوية ومباشرة للعميل.</p>
        '
    ],
    [
        'id' => 'logs',
        'title' => 'سجلات النظام (Logs)',
        'content' => '
            <p>يمكنك تتبع كافة الرسائل عبر صفحة <a href="' . esc_url(admin_url('admin.php?page=ersaal-logs')) . '">Logs</a>.</p>
            <ul>
                <li><strong>البحث والفلترة:</strong> حسب الحالة، المصدر، الحدث، أو بين تاريخين.</li>
                <li><strong>تفاصيل السجل:</strong> انقر على "Details" لرؤية النص الكامل للرسالة والرد التقني (API Response).</li>
                <li><strong>الحذف:</strong> خيارات لحذف سجل واحد أو الحذف الجماعي (يحذف السجلات محلياً فقط).</li>
                <li><strong>تصدير CSV:</strong> تصدير النتائج الحالية بناءً على الفلاتر النشطة.</li>
            </ul>
            <h3>معاني حالات السجل (Statuses)</h3>
            <table class="widefat striped">
                <tr><th>Processing</th><td>قيد الإرسال إلى API.</td></tr>
                <tr><th>Accepted</th><td>تم الاستلام بنجاح من منصة Ersaal.</td></tr>
                <tr><th>Retry Scheduled</th><td>خطأ مؤقت، وستتم إعادة المحاولة تلقائياً.</td></tr>
                <tr><th>Error / Failed</th><td>فشل دائم (رقم خاطئ، لا يوجد رصيد، إلخ).</td></tr>
            </table>
        '
    ],
    [
        'id' => 'troubleshooting',
        'title' => 'استكشاف الأخطاء وإصلاحها',
        'content' => '
            <h3>Connection failed</h3>
            <p>تحقق من رابط الـ API، والـ API Key، وحالة المشروع (Project Status)، واتصال الخادم بالإنترنت.</p>
            <h3>Wallet = Unknown</h3>
            <p>لا يعني أن الرصيد صفر، بل قد يكون الحساب يعتمد على نظام الباقات (Subscriptions) بدلاً من المحفظة المالية.</p>
            <h3>Subscription Error</h3>
            <p>تأكد أن لديك باقة رسائل عامة فعالة، باقات الـ OTP لا تسمح بإرسال رسائل ترويجية أو عامة.</p>
            <h3>Sender ID Error</h3>
            <p>تأكد من كتابة اسم المرسل (Sender ID) تماماً كما هو معتمد في منصة إرسال.</p>
            <h3>Message Accepted But Not Received</h3>
            <p>الحالة Accepted لا تعني التوصيل النهائي. قد تتأخر الرسالة من قبل مزود الاتصالات أو قد يكون هاتف المستلم مغلقاً.</p>
            <h3>WooCommerce message not sent</h3>
            <p>تحقق من تفعيل الربط مع WooCommerce، وتأكد من تفعيل الحدث المطلوب (Event)، وأن الطلب يحتوي على رقم هاتف صحيح (Billing Phone). راجع الـ Logs لمزيد من التفاصيل.</p>
        '
    ],
    [
        'id' => 'faq',
        'title' => 'الأسئلة الشائعة (FAQ)',
        'content' => '
            <div class="ersaal-help-faq-list">
                <details><summary>هل أحتاج WooCommerce؟</summary><div class="ersaal-help-faq-answer"><p>لا، تعمل الإضافة بشكل مستقل للإرسال اليدوي، والربط مع WooCommerce اختياري.</p></div></details>
                <details><summary>هل يمكن إرسال رسائل يدويًا؟</summary><div class="ersaal-help-faq-answer"><p>نعم، من صفحة Send SMS أو من داخل صفحة أي طلب في WooCommerce.</p></div></details>
                <details><summary>ما معنى Wallet Unknown؟</summary><div class="ersaal-help-faq-answer"><p>يعني غالبًا أن حسابك يستخدم باقة اشتراك بدلًا من رصيد محفظة رقمي.</p></div></details>
                <details><summary>ما الفرق بين OTP وSMS؟</summary><div class="ersaal-help-faq-answer"><p>OTP مخصصة لرموز التحقق، بينما SMS مخصصة للرسائل النصية العامة والتنبيهات.</p></div></details>
                <details><summary>لماذا تظهر أرقام الهواتف مخفية؟</summary><div class="ersaal-help-faq-answer"><p>لحماية خصوصية العملاء، لا يتم تخزين الرقم كاملًا في السجلات المحلية.</p></div></details>
                <details><summary>هل يمكن إرسال أكثر من رسالة يدوية للطلب نفسه؟</summary><div class="ersaal-help-faq-answer"><p>نعم، يمكن إرسال أكثر من رسالة يدوية من صفحة تفاصيل الطلب.</p></div></details>
            </div>
        '
    ]
];
