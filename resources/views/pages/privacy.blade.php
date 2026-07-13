@extends('layouts.public')

@section('title', 'سياسة الخصوصية | اتزان')

@push('styles')
<link rel="stylesheet" href="{{ asset('front/css/legal-pages.css') }}">
@endpush

@section('content')

<main class="legal-main">
  <section class="legal-hero">
    <div class="container">
      <div class="legal-hero-card">
        <div class="legal-hero-text">
          <span class="legal-eyebrow">سياسة الخصوصية</span>
          <h1>خصوصيتك تُدار بوضوح ومسؤولية</h1>
          <p>
            نوضح هنا كيف يتم التعامل مع البيانات داخل المنصة، ولماذا تُستخدم،
            وكيف نعمل على حمايتها بما ينسجم مع تجربة آمنة وواضحة.
          </p>
        </div>

        <div class="legal-switch">
          <a href="{{ route('terms') }}" class="legal-switch-link">الشروط والأحكام</a>
          <a href="{{ route('privacy') }}" class="legal-switch-link active">سياسة الخصوصية</a>
        </div>
      </div>
    </div>
  </section>

  <section class="legal-page-section">
    <div class="container">
      <div class="legal-grid">

        <aside class="legal-sidebar">
          <div class="legal-sidebar-card">
            <h3>محتوى الصفحة</h3>
            <nav class="legal-nav">
              <a href="#collection" class="legal-nav-link active">البيانات التي نجمعها</a>
              <a href="#usage" class="legal-nav-link">كيف نستخدم البيانات</a>
              <a href="#protection" class="legal-nav-link">حماية المعلومات</a>
              <a href="#sharing" class="legal-nav-link">مشاركة البيانات</a>
              <a href="#cookies" class="legal-nav-link">ملفات الارتباط</a>
              <a href="#rights" class="legal-nav-link">حقوقك كمستخدم</a>
              <a href="#retention" class="legal-nav-link">الاحتفاظ بالبيانات</a>
              <a href="#updates" class="legal-nav-link">التحديثات</a>
            </nav>
          </div>

          <div class="legal-sidebar-note">
            <span>آخر تحديث</span>
            <strong>12 مارس 2026</strong>
          </div>
        </aside>

        <div class="legal-content">

          <section class="legal-block" id="collection">
            <div class="legal-block-head">
              <span class="legal-block-number">01</span>
              <div>
                <h2>البيانات التي نجمعها</h2>
                <p>يتم جمع البيانات الضرورية فقط لتقديم الخدمة وتشغيل المنصة بشكل صحيح.</p>
              </div>
            </div>
            <div class="legal-card">
              <p>
                قد تشمل البيانات التي يتم جمعها المعلومات التي يضيفها المستخدم
                عند التسجيل أو أثناء استخدام خصائص المنصة.
              </p>
              <ul>
                <li>بيانات الحساب الأساسية مثل الاسم والبريد الإلكتروني.</li>
                <li>بيانات الاستخدام المرتبطة بالتفاعل مع المنصة.</li>
                <li>أي معلومات يختار المستخدم مشاركتها داخل الخدمة.</li>
              </ul>
            </div>
          </section>

          <section class="legal-block" id="usage">
            <div class="legal-block-head">
              <span class="legal-block-number">02</span>
              <div>
                <h2>كيف نستخدم البيانات</h2>
                <p>يتم استخدام البيانات لتقديم الخدمة وتحسين التجربة وتسهيل التواصل.</p>
              </div>
            </div>
            <div class="legal-card">
              <p>
                نستخدم المعلومات بطريقة تساعد على تشغيل المنصة بشكل أوضح وأكثر
                تنظيمًا، وتقديم تجربة أكثر سلاسة للمستخدم.
              </p>
              <ul>
                <li>إدارة الحسابات والخدمات الأساسية.</li>
                <li>تحسين الأداء وتجربة الاستخدام.</li>
                <li>الرد على الاستفسارات وتقديم الدعم عند الحاجة.</li>
              </ul>
            </div>
          </section>

          <section class="legal-block" id="protection">
            <div class="legal-block-head">
              <span class="legal-block-number">03</span>
              <div>
                <h2>حماية المعلومات</h2>
                <p>نلتزم باتخاذ إجراءات مناسبة لحماية البيانات من الوصول غير المصرح به.</p>
              </div>
            </div>
            <div class="legal-card">
              <p>
                نعمل على تطبيق وسائل حماية تقنية وتنظيمية مناسبة للحفاظ على
                خصوصية المعلومات وتقليل أي مخاطر مرتبطة بالاستخدام غير المشروع.
              </p>
              <ul>
                <li>تقييد الوصول إلى البيانات بحسب الحاجة.</li>
                <li>استخدام ممارسات تقنية مناسبة لحماية المعلومات.</li>
                <li>مراجعة دورية لتحسين مستوى الأمان.</li>
              </ul>
            </div>
          </section>

          <section class="legal-block" id="sharing">
            <div class="legal-block-head">
              <span class="legal-block-number">04</span>
              <div>
                <h2>مشاركة البيانات</h2>
                <p>لا يتم مشاركة البيانات إلا في حدود تشغيل الخدمة أو وفق التزامات قانونية.</p>
              </div>
            </div>
            <div class="legal-card">
              <p>
                لا يتم بيع بيانات المستخدمين، ويتم أي استخدام أو مشاركة محتملة
                في نطاق محدود وواضح يخدم تشغيل المنصة أو يلتزم بالأنظمة المعمول بها.
              </p>
              <ul>
                <li>لا يتم بيع المعلومات لأطراف خارجية.</li>
                <li>قد تتم مشاركة محدودة عند الضرورة التشغيلية أو القانونية.</li>
                <li>تتم إدارة أي مشاركة ضمن إطار مسؤول وواضح.</li>
              </ul>
            </div>
          </section>

          <section class="legal-block" id="cookies">
            <div class="legal-block-head">
              <span class="legal-block-number">05</span>
              <div>
                <h2>ملفات تعريف الارتباط</h2>
                <p>قد تُستخدم ملفات الارتباط لتحسين الأداء وتخصيص بعض عناصر التجربة.</p>
              </div>
            </div>
            <div class="legal-card">
              <p>
                تساعد ملفات الارتباط وتقنيات مشابهة على فهم التفاعل مع المنصة،
                وتسهيل الدخول، وتحسين بعض الجوانب التقنية.
              </p>
              <ul>
                <li>تستخدم لتحسين الأداء وتجربة التصفح.</li>
                <li>لا تهدف إلى الإضرار بخصوصية المستخدم.</li>
                <li>يمكن التحكم بها من إعدادات المتصفح عند الحاجة.</li>
              </ul>
            </div>
          </section>

          <section class="legal-block" id="rights">
            <div class="legal-block-head">
              <span class="legal-block-number">06</span>
              <div>
                <h2>حقوقك كمستخدم</h2>
                <p>نحرص على أن تكون لك القدرة على الاستفسار حول بياناتك وإدارتها.</p>
              </div>
            </div>
            <div class="legal-card">
              <div class="rights-grid">
                <div class="right-item">
                  <strong>الوصول</strong>
                  <span>الاستفسار عن البيانات المرتبطة بحسابك عند الحاجة.</span>
                </div>
                <div class="right-item">
                  <strong>التعديل</strong>
                  <span>طلب تحديث أو تصحيح البيانات غير الدقيقة.</span>
                </div>
                <div class="right-item">
                  <strong>الحذف</strong>
                  <span>طلب حذف البيانات وفق ما تسمح به طبيعة الخدمة والأنظمة.</span>
                </div>
                <div class="right-item">
                  <strong>الاستفسار</strong>
                  <span>التواصل معنا لأي سؤال يتعلق بالخصوصية أو الاستخدام.</span>
                </div>
              </div>
            </div>
          </section>

          <section class="legal-block" id="retention">
            <div class="legal-block-head">
              <span class="legal-block-number">07</span>
              <div>
                <h2>الاحتفاظ بالبيانات</h2>
                <p>نحتفظ بالبيانات للمدة التي تساعد على تشغيل الخدمة وتحقيق المتطلبات الضرورية.</p>
              </div>
            </div>
            <div class="legal-card">
              <p>
                يتم الاحتفاظ بالبيانات طالما كانت هناك حاجة تشغيلية أو تنظيمية
                لذلك، أو وفق ما تتطلبه الالتزامات القانونية ذات العلاقة.
              </p>
              <ul>
                <li>الاحتفاظ يكون ضمن حدود الحاجة الفعلية.</li>
                <li>يتم تقليل البيانات غير الضرورية قدر الإمكان.</li>
                <li>تخضع مدد الاحتفاظ للمراجعة والتحديث.</li>
              </ul>
            </div>
          </section>

          <section class="legal-block" id="updates">
            <div class="legal-block-head">
              <span class="legal-block-number">08</span>
              <div>
                <h2>التحديثات على السياسة</h2>
                <p>قد يتم تعديل هذه السياسة بما يتناسب مع تطوير الخدمة وتحسينها.</p>
              </div>
            </div>
            <div class="legal-card">
              <p>
                عند إجراء أي تعديل على هذه السياسة، سيتم نشر النسخة المحدثة داخل
                هذه الصفحة مع توضيح تاريخ آخر تحديث بشكل ظاهر.
              </p>

              <div class="legal-actions">
                <a href="{{ route('terms') }}" class="legal-btn legal-btn-light">الشروط والأحكام</a>
                <a href="{{ route('contact') }}" class="legal-btn legal-btn-solid">تواصل معنا</a>
              </div>
            </div>
          </section>

          <div class="legal-closing-box">
            نحن نتعامل مع بياناتك بمسؤولية وشفافية، ونسعى دائمًا لتوفير بيئة
            رقمية آمنة تحترم خصوصيتك.
          </div>

        </div>
      </div>
    </div>
  </section>
</main>

@endsection

@push('scripts')
<script src="{{ asset('front/js/legal-pages.js') }}"></script>
@endpush
