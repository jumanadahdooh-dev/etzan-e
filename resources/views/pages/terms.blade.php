@extends('layouts.public')

@section('title', 'الشروط والأحكام | اتزان')

@push('styles')
<link rel="stylesheet" href="{{ asset('front/css/legal-pages.css') }}">
@endpush

@section('content')

<main class="legal-main">
  <section class="legal-hero">
    <div class="container">
      <div class="legal-hero-card">
        <div class="legal-hero-text">
          <span class="legal-eyebrow">الشروط والأحكام</span>
          <h1>إطار واضح لاستخدام آمن ومنظم</h1>
          <p>
            نوضح هنا القواعد الأساسية التي تنظّم استخدام المنصة، بما يساعد على
            تقديم تجربة موثوقة ومريحة لجميع المستخدمين.
          </p>
        </div>

        <div class="legal-switch">
          <a href="{{ route('terms') }}" class="legal-switch-link active">الشروط والأحكام</a>
          <a href="{{ route('privacy') }}" class="legal-switch-link">سياسة الخصوصية</a>
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
              <a href="#acceptance" class="legal-nav-link active">قبول الشروط</a>
              <a href="#eligibility" class="legal-nav-link">أهلية الاستخدام</a>
              <a href="#account" class="legal-nav-link">الحساب والمعلومات</a>
              <a href="#usage" class="legal-nav-link">استخدام المنصة</a>
              <a href="#rights" class="legal-nav-link">الحقوق والمحتوى</a>
              <a href="#service" class="legal-nav-link">توفر الخدمة</a>
              <a href="#updates" class="legal-nav-link">التحديثات</a>
              <a href="#contact" class="legal-nav-link">التواصل معنا</a>
            </nav>
          </div>

          <div class="legal-sidebar-note">
            <span>آخر تحديث</span>
            <strong>12 مارس 2026</strong>
          </div>
        </aside>

        <div class="legal-content">

          <section class="legal-block" id="acceptance">
            <div class="legal-block-head">
              <span class="legal-block-number">01</span>
              <div>
                <h2>قبول الشروط</h2>
                <p>باستخدامك للمنصة فإنك توافق على هذه الشروط والأحكام.</p>
              </div>
            </div>
            <div class="legal-card">
              <p>
                يعتبر استمرارك في استخدام المنصة موافقة على الشروط المعلنة
                فيها، وعلى أي تحديثات لاحقة يتم نشرها داخل هذه الصفحة.
              </p>
              <ul>
                <li>ينصح بمراجعة الشروط قبل استخدام الخدمة.</li>
                <li>الاستمرار في الاستخدام يعني القبول بالأحكام المنظمة.</li>
                <li>في حال عدم الموافقة، يفضّل التوقف عن استخدام المنصة.</li>
              </ul>
            </div>
          </section>

          <section class="legal-block" id="eligibility">
            <div class="legal-block-head">
              <span class="legal-block-number">02</span>
              <div>
                <h2>أهلية الاستخدام</h2>
                <p>يجب أن يتم استخدام المنصة بطريقة قانونية ومسؤولة.</p>
              </div>
            </div>
            <div class="legal-card">
              <p>
                يلتزم المستخدم باستخدام المنصة للأغراض المخصصة لها فقط، وبما لا
                يسبب ضررًا للخدمة أو للمستخدمين الآخرين.
              </p>
              <ul>
                <li>يُمنع إساءة استخدام الخدمة أو تعطيلها.</li>
                <li>يجب الالتزام بالسلوك المحترم والآمن داخل المنصة.</li>
                <li>قد يتم تعليق الوصول عند وجود استخدام مخالف.</li>
              </ul>
            </div>
          </section>

          <section class="legal-block" id="account">
            <div class="legal-block-head">
              <span class="legal-block-number">03</span>
              <div>
                <h2>الحساب والمعلومات</h2>
                <p>المعلومات المدخلة يجب أن تكون دقيقة وحديثة قدر الإمكان.</p>
              </div>
            </div>
            <div class="legal-card">
              <p>
                عند إنشاء حساب أو إدخال أي معلومات، يتحمل المستخدم مسؤولية صحة
                البيانات والمحافظة على سرية معلومات الدخول الخاصة به.
              </p>
              <ul>
                <li>يجب تقديم بيانات صحيحة وغير مضللة.</li>
                <li>الحفاظ على سرية بيانات الدخول مسؤولية المستخدم.</li>
                <li>ينصح بتحديث البيانات عند تغيرها.</li>
              </ul>
            </div>
          </section>

          <section class="legal-block" id="usage">
            <div class="legal-block-head">
              <span class="legal-block-number">04</span>
              <div>
                <h2>استخدام المنصة</h2>
                <p>تم تصميم المنصة لتقديم تجربة منظمة وواضحة وآمنة.</p>
              </div>
            </div>
            <div class="legal-card">
              <p>
                يلتزم المستخدم باستخدام المنصة بطريقة لا تخلّ بأمان الموقع، ولا
                تؤثر سلبًا على راحة بقية المستخدمين أو على جودة الخدمة.
              </p>
              <ul>
                <li>عدم إرسال محتوى مسيء أو مخالف.</li>
                <li>عدم محاولة الوصول غير المصرح به إلى الأنظمة.</li>
                <li>عدم استغلال المنصة في نشاط غير مشروع.</li>
              </ul>
            </div>
          </section>

          <section class="legal-block" id="rights">
            <div class="legal-block-head">
              <span class="legal-block-number">05</span>
              <div>
                <h2>الحقوق والمحتوى</h2>
                <p>جميع العناصر المعروضة داخل المنصة تخضع للتنظيم وحقوق الملكية.</p>
              </div>
            </div>
            <div class="legal-card">
              <p>
                يشمل ذلك النصوص، التصاميم، الهوية البصرية، والعناصر البرمجية،
                ولا يجوز استخدامها أو إعادة نشرها بشكل غير مصرح به.
              </p>
              <ul>
                <li>المحتوى مخصص للاستخدام داخل إطار المنصة.</li>
                <li>يُمنع النسخ أو إعادة النشر دون إذن.</li>
                <li>أي استخدام غير مصرح به قد يؤدي إلى إيقاف الحساب.</li>
              </ul>
            </div>
          </section>

          <section class="legal-block" id="service">
            <div class="legal-block-head">
              <span class="legal-block-number">06</span>
              <div>
                <h2>توفر الخدمة</h2>
                <p>نسعى إلى تقديم خدمة مستقرة مع إمكانية التحديث أو الصيانة.</p>
              </div>
            </div>
            <div class="legal-card">
              <p>
                قد يتم تحديث بعض الخصائص أو إيقافها مؤقتًا لأغراض الصيانة أو
                التحسين أو رفع مستوى الأمان داخل المنصة.
              </p>
              <ul>
                <li>قد تجرى صيانة دورية أو تحديثات تقنية.</li>
                <li>قد تتغير بعض الواجهات أو الخصائص مع الوقت.</li>
                <li>الهدف من ذلك تحسين الأداء وجودة التجربة.</li>
              </ul>
            </div>
          </section>

          <section class="legal-block" id="updates">
            <div class="legal-block-head">
              <span class="legal-block-number">07</span>
              <div>
                <h2>التحديثات على الشروط</h2>
                <p>قد يتم تعديل هذه الشروط بما يتناسب مع تطوير المنصة.</p>
              </div>
            </div>
            <div class="legal-card">
              <p>
                عند إجراء أي تعديل جوهري، سيتم نشر النسخة المحدثة في هذه الصفحة،
                ويعد استمرارك في استخدام المنصة بعد التحديث موافقة على النسخة الجديدة.
              </p>
              <ul>
                <li>سيتم توضيح تاريخ آخر تحديث بشكل ظاهر.</li>
                <li>التعديلات تهدف إلى تحسين الوضوح والتنظيم.</li>
                <li>ينصح بمراجعة الصفحة من وقت لآخر.</li>
              </ul>
            </div>
          </section>

          <section class="legal-block" id="contact">
            <div class="legal-block-head">
              <span class="legal-block-number">08</span>
              <div>
                <h2>التواصل معنا</h2>
                <p>في حال وجود أي استفسار، يمكنك التواصل معنا مباشرة.</p>
              </div>
            </div>
            <div class="legal-card">
              <p>
                يسعدنا الإجابة عن أي استفسار يتعلق باستخدام المنصة أو بما ورد في
                هذه الصفحة من شروط وأحكام.
              </p>

              <div class="legal-actions">
                <a href="{{ route('privacy') }}" class="legal-btn legal-btn-light">سياسة الخصوصية</a>
                <a href="{{ route('contact') }}" class="legal-btn legal-btn-solid">تواصل معنا</a>
              </div>
            </div>
          </section>

          <div class="legal-closing-box">
            باستخدامك للمنصة، فأنت توافق على هذه الشروط وتتعهد باستخدامها بشكل
            مسؤول وآمن.
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
