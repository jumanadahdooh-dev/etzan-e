document.addEventListener("DOMContentLoaded", function () {
  /* =========================================================
     Helpers
  ========================================================= */

  const qs = (selector, parent = document) => parent.querySelector(selector);
  const qsa = (selector, parent = document) => Array.from(parent.querySelectorAll(selector));

  const setText = (element, value = "") => {
    if (element) {
      element.textContent = value || "";
    }
  };

  const setHtml = (element, value = "") => {
    if (element) {
      element.innerHTML = value || "";
    }
  };

  const safeDataset = (element, key, fallback = "") => {
    return element?.dataset?.[key] || fallback;
  };

  const prefersReducedMotion = () =>
    window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  /**
   * يفعّل السحب باللمس (يمين/يسار) على عنصر سلايدر، ويستدعي onNext/onPrev
   * حسب اتجاه السحب. بما إن الصفحة RTL، سحب لليمين = "السابق" بصريًا
   * وسحب لليسار = "التالي"، فبنعكس onNext/onPrev داخليًا مرة وحدة هون
   * بدل ما نكررها بكل سلايدر.
   */
  function enableSwipe(element, { onNext, onPrev, threshold = 40 } = {}) {
    if (!element) {
      return;
    }

    let startX = 0;
    let startY = 0;
    let tracking = false;

    element.addEventListener(
      "touchstart",
      (event) => {
        const touch = event.touches[0];
        startX = touch.clientX;
        startY = touch.clientY;
        tracking = true;
      },
      { passive: true }
    );

    element.addEventListener(
      "touchend",
      (event) => {
        if (!tracking) {
          return;
        }

        tracking = false;

        const touch = event.changedTouches[0];
        const deltaX = touch.clientX - startX;
        const deltaY = touch.clientY - startY;

        if (Math.abs(deltaX) < threshold || Math.abs(deltaX) < Math.abs(deltaY)) {
          return;
        }

        if (deltaX < 0) {
          onNext?.();
        } else {
          onPrev?.();
        }
      },
      { passive: true }
    );
  }

  /* =========================================================
     قسم الهيدر
  ========================================================= */

  const headerLinks = qsa('#mainNavbar .nav-link[href^="#"]');

  if (headerLinks.length) {
    const sectionsMap = headerLinks
      .map((link) => {
        const href = link.getAttribute("href");

        if (!href || href === "#") {
          return null;
        }

        const target = qs(href);

        if (!target) {
          return null;
        }

        return {
          id: target.id,
          link,
          element: target,
        };
      })
      .filter(Boolean)
      .sort((a, b) => a.element.offsetTop - b.element.offsetTop);

    function setActiveHeaderLink(activeId) {
      headerLinks.forEach((link) => {
        link.classList.toggle("active", link.getAttribute("href") === `#${activeId}`);
      });
    }

    function updateHeaderActiveState() {
      if (!sectionsMap.length) {
        return;
      }

      const scrollY = window.scrollY;
      const triggerPoint = scrollY + 140;

      if (scrollY < 120) {
        setActiveHeaderLink("hero");
        return;
      }

      const footer = qs("#footer");

      if (footer) {
        const footerTrigger = footer.offsetTop - 120;

        if (scrollY >= footerTrigger) {
          setActiveHeaderLink("footer");
          return;
        }
      }

      let currentSectionId = sectionsMap[0]?.id || "hero";

      sectionsMap.forEach((section) => {
        if (triggerPoint >= section.element.offsetTop && section.id !== "footer") {
          currentSectionId = section.id;
        }
      });

      setActiveHeaderLink(currentSectionId);
    }

    headerLinks.forEach((link) => {
      link.addEventListener("click", function () {
        const href = this.getAttribute("href");

        if (!href || !href.startsWith("#")) {
          return;
        }

        const targetId = href.replace("#", "");

        if (targetId) {
          setActiveHeaderLink(targetId);
        }
      });
    });

    window.addEventListener("scroll", updateHeaderActiveState);
    window.addEventListener("load", updateHeaderActiveState);
    updateHeaderActiveState();
  }

  /* =========================================================
     قسم الهيرو
  ========================================================= */

  const slides = qsa(".hero-slide");
  const dots = qsa(".slider-dot");
  const nextBtn = qs(".next");
  const prevBtn = qs(".prev");
  const heroSliderEl = qs(".hero-slider");

  if (slides.length) {
    let current = 0;
    let autoSlide = null;

    function showSlide(index) {
      if (!slides.length) {
        return;
      }

      const safeIndex = ((index % slides.length) + slides.length) % slides.length;

      slides.forEach((slide, i) => {
        slide.classList.toggle("active", i === safeIndex);
      });

      dots.forEach((dot, i) => {
        dot.classList.toggle("active", i === safeIndex);
      });

      current = safeIndex;
    }

    function nextSlide() {
      showSlide(current + 1);
    }

    function prevSlide() {
      showSlide(current - 1);
    }

    function startAutoSlide() {
      stopAutoSlide();

      if (slides.length > 1 && !prefersReducedMotion()) {
        autoSlide = setInterval(nextSlide, 4000);
      }
    }

    function stopAutoSlide() {
      if (autoSlide) {
        clearInterval(autoSlide);
        autoSlide = null;
      }
    }

    function resetAutoSlide() {
      startAutoSlide();
    }

    nextBtn?.addEventListener("click", () => {
      nextSlide();
      resetAutoSlide();
    });

    prevBtn?.addEventListener("click", () => {
      prevSlide();
      resetAutoSlide();
    });

    dots.forEach((dot, index) => {
      dot.addEventListener("click", () => {
        showSlide(Number(dot.dataset.index ?? index));
        resetAutoSlide();
      });
    });

    heroSliderEl?.addEventListener("mouseenter", stopAutoSlide);
    heroSliderEl?.addEventListener("mouseleave", startAutoSlide);

    enableSwipe(heroSliderEl, {
      onNext: () => {
        nextSlide();
        resetAutoSlide();
      },
      onPrev: () => {
        prevSlide();
        resetAutoSlide();
      },
    });

    showSlide(0);
    startAutoSlide();
  }

  /* =========================================================
     كيف يعمل النظام
  ========================================================= */

  const stepCards = qsa(".step-card");

  if (stepCards.length) {
    let currentStep = 0;

    stepCards.forEach((card, index) => {
      card.classList.toggle("active", index === 0);
    });

    if (!prefersReducedMotion()) {
      setInterval(() => {
        stepCards.forEach((card) => card.classList.remove("active"));

        stepCards[currentStep]?.classList.add("active");

        currentStep = (currentStep + 1) % stepCards.length;
      }, 3000);
    }
  }

  /* =========================================================
     قسم ما يميزنا
  ========================================================= */

  const featureItems = [
    {
      label: "ميزة ذكية",
      title: "نظام توصيات غذائية ذكي",
      description:
        "يعتمد النظام على بياناتك الصحية مثل الوزن، الحالة المرضية، ومستوى النشاط لتقديم توصيات غذائية مناسبة تساعدك على اتخاذ قرارات أفضل في حياتك اليومية.",
      note: "مناسب للمستخدمين الذين يحتاجون إلى توجيه غذائي أوضح وأكثر تخصيصًا.",
      bubble: "نوضح لك كيف تساعدك المنصة على الحصول على توصيات غذائية تناسب حالتك.",
      chip1: "توصيات مخصصة",
      chip2: "واجهة مبسطة",
      chip3: "نتائج أسرع",
    },
    {
      label: "ميزة عملية",
      title: "حساب ذكي للسعرات اليومية",
      description:
        "يساعدك النظام على تقدير احتياجك اليومي من السعرات بطريقة مبسطة وواضحة بناءً على بياناتك الأساسية ونمط نشاطك اليومي.",
      note: "يسهّل عليك فهم احتياجك الغذائي دون تعقيد أو حسابات مرهقة.",
      bubble: "هذه الميزة تساعدك على معرفة احتياجك اليومي بطريقة سهلة ومباشرة.",
      chip1: "حساب تلقائي",
      chip2: "بيانات واضحة",
      chip3: "خطوات بسيطة",
    },
    {
      label: "ميزة تفاعلية",
      title: "تقارير ورسوم توضيحية سهلة الفهم",
      description:
        "يعرض لك اتزان تقدمك من خلال تقارير ورسوم مبسطة تساعدك على متابعة تغير وزنك وعاداتك الغذائية بشكل أوضح.",
      note: "مفيد لمراقبة التقدم وتحفيزك على الاستمرار بخطوات صحية أفضل.",
      bubble: "نساعدك على رؤية تقدمك بطريقة بصرية واضحة وسهلة المتابعة.",
      chip1: "رسوم واضحة",
      chip2: "متابعة مستمرة",
      chip3: "تحفيز يومي",
    },
    {
      label: "ميزة آمنة",
      title: "خصوصية تامة لبياناتك الصحية",
      description:
        "نحافظ على معلوماتك الصحية داخل بيئة آمنة، وتبقى بياناتك متاحة لك وللطبيب المختص فقط وفق صلاحيات واضحة.",
      note: "لأن الثقة والخصوصية عنصران أساسيان في أي تجربة صحية ناجحة.",
      bubble: "خصوصيتك مهمة، لذلك نبقي بياناتك الصحية محفوظة وآمنة.",
      chip1: "حماية البيانات",
      chip2: "ثقة أكبر",
      chip3: "وصول آمن",
    },
    {
      label: "ميزة موثوقة",
      title: "محتوى توعوي موثوق ومبسط",
      description:
        "يوفر لك اتزان مقالات ونصائح غذائية مكتوبة بلغة واضحة ومناسبة تساعدك على فهم العلاقة بين الغذاء وصحتك.",
      note: "محتوى يهدف إلى رفع وعيك الصحي بطريقة سهلة ومستمرة.",
      bubble: "نقدم لك محتوى مبسطًا يساعدك على فهم الغذاء والصحة بشكل أفضل.",
      chip1: "محتوى موثوق",
      chip2: "أسلوب مبسط",
      chip3: "فهم أسرع",
    },
  ];

  const featureContentBox = qs("#featureContent");
  const featureMiniLabel = qs("#featureMiniLabel");
  const featureTitle = qs("#featureTitle");
  const featureDescription = qs("#featureDescription");
  const featureNote = qs("#featureNote");
  const featureDoctorBubble = qs("#featureDoctorBubble");
  const featureChip1 = qs("#featureChip1");
  const featureChip2 = qs("#featureChip2");
  const featureChip3 = qs("#featureChip3");
  const featureDots = qsa(".feature-dot");
  const featureNextBtn = qs(".next-feature");
  const featurePrevBtn = qs(".prev-feature");
  const featureSectionEl = qs(".features-showcase-section");

  if (
    featureContentBox &&
    featureMiniLabel &&
    featureTitle &&
    featureDescription &&
    featureNote &&
    featureDoctorBubble &&
    featureChip1 &&
    featureChip2 &&
    featureChip3
  ) {
    let currentFeatureIndex = 0;
    let featureAutoPlay = null;
    let featureAnimating = false;

    function updateFeatureView(item) {
      setText(featureMiniLabel, item.label);
      setText(featureTitle, item.title);
      setText(featureDescription, item.description);
      setText(featureNote, item.note);
      setText(featureDoctorBubble, item.bubble);
      setText(featureChip1, item.chip1);
      setText(featureChip2, item.chip2);
      setText(featureChip3, item.chip3);
    }

    function setFeatureDot(index) {
      featureDots.forEach((dot, i) => {
        dot.classList.toggle("active", i === index);
      });
    }

    function showFeatureItem(index) {
      if (featureAnimating) {
        return;
      }

      const safeIndex = ((index % featureItems.length) + featureItems.length) % featureItems.length;

      featureAnimating = true;

      featureContentBox.classList.remove("fade-in");
      featureDoctorBubble.classList.remove("fade-in");

      featureContentBox.classList.add("fade-out");
      featureDoctorBubble.classList.add("fade-out");

      setTimeout(() => {
        updateFeatureView(featureItems[safeIndex]);
        setFeatureDot(safeIndex);
        currentFeatureIndex = safeIndex;

        featureContentBox.classList.remove("fade-out");
        featureDoctorBubble.classList.remove("fade-out");

        featureContentBox.classList.add("fade-in");
        featureDoctorBubble.classList.add("fade-in");

        setTimeout(() => {
          featureAnimating = false;
        }, 350);
      }, 220);
    }

    function goToNextFeature() {
      showFeatureItem(currentFeatureIndex + 1);
    }

    function goToPrevFeature() {
      showFeatureItem(currentFeatureIndex - 1);
    }

    function stopFeatureAutoPlay() {
      if (featureAutoPlay) {
        clearInterval(featureAutoPlay);
        featureAutoPlay = null;
      }
    }

    function startFeatureAutoPlay() {
      stopFeatureAutoPlay();

      if (featureItems.length > 1 && !prefersReducedMotion()) {
        featureAutoPlay = setInterval(goToNextFeature, 4000);
      }
    }

    function resetFeatureAutoPlay() {
      startFeatureAutoPlay();
    }

    featureNextBtn?.addEventListener("click", () => {
      goToNextFeature();
      resetFeatureAutoPlay();
    });

    featurePrevBtn?.addEventListener("click", () => {
      goToPrevFeature();
      resetFeatureAutoPlay();
    });

    featureDots.forEach((dot, index) => {
      dot.addEventListener("click", () => {
        showFeatureItem(Number(dot.dataset.index ?? index));
        resetFeatureAutoPlay();
      });
    });

    featureSectionEl?.addEventListener("mouseenter", stopFeatureAutoPlay);
    featureSectionEl?.addEventListener("mouseleave", startFeatureAutoPlay);

    enableSwipe(featureContentBox, {
      onNext: () => {
        goToNextFeature();
        resetFeatureAutoPlay();
      },
      onPrev: () => {
        goToPrevFeature();
        resetFeatureAutoPlay();
      },
    });

    updateFeatureView(featureItems[0]);
    setFeatureDot(0);
    startFeatureAutoPlay();
  }

  /* =========================================================
     قسم الأطباء
  ========================================================= */

  const doctorStackCards = qsa(".doctor-stack-card");
  const doctorStackDotsContainer = qs("#doctorStackDots");
  const doctorPrevBtn = qs(".doctor-stack-prev");
  const doctorNextBtn = qs(".doctor-stack-next");

  const doctorInfoName = qs("#doctorInfoName");
  const doctorInfoSpecialty = qs("#doctorInfoSpecialty");
  const doctorInfoDescription = qs("#doctorInfoDescription");
  const doctorInfoExp = qs("#doctorInfoExp");
  const doctorInfoSessions = qs("#doctorInfoSessions");
  const doctorInfoStatus = qs("#doctorInfoStatus");
  const doctorInfoTag1 = qs("#doctorInfoTag1");
  const doctorInfoTag2 = qs("#doctorInfoTag2");
  const doctorInfoTag3 = qs("#doctorInfoTag3");
  const doctorInfoBookingBtn = qs("#doctorInfoBookingBtn");
  const doctorInfoProfileBtn = qs("#doctorInfoProfileBtn");

  if (doctorStackCards.length && doctorStackDotsContainer) {
    let doctorCurrentIndex = 0;

    function doctorUpdateInfo(card) {
      if (!card) {
        return;
      }

      setText(doctorInfoName, safeDataset(card, "name", "طبيب اتزان"));
      setText(doctorInfoSpecialty, safeDataset(card, "specialty", "مختص تغذية"));
      setText(doctorInfoDescription, safeDataset(card, "description", ""));
      setText(doctorInfoExp, safeDataset(card, "exp", ""));
      setText(doctorInfoSessions, safeDataset(card, "sessions", ""));
      setText(doctorInfoStatus, safeDataset(card, "status", ""));
      setText(doctorInfoTag1, safeDataset(card, "tag1", "تغذية"));
      setText(doctorInfoTag2, safeDataset(card, "tag2", "متابعة"));
      setText(doctorInfoTag3, safeDataset(card, "tag3", "استشارة"));

      if (doctorInfoBookingBtn) {
        const bookingUrl = safeDataset(card, "bookingUrl", "");
        if (bookingUrl) {
          doctorInfoBookingBtn.setAttribute("href", bookingUrl);
        }
      }

      if (doctorInfoProfileBtn) {
        const profileUrl = safeDataset(card, "profileUrl", "");
        if (profileUrl) {
          doctorInfoProfileBtn.setAttribute("href", profileUrl);
        }
      }
    }

    function doctorCreateDots() {
      doctorStackDotsContainer.innerHTML = "";

      doctorStackCards.forEach((_, index) => {
        const dot = document.createElement("span");
        dot.className = "doctor-stack-dot";
        dot.dataset.index = index;
        doctorStackDotsContainer.appendChild(dot);
      });
    }

    function doctorUpdateDots() {
      const dots = qsa(".doctor-stack-dot");

      dots.forEach((dot, index) => {
        dot.classList.toggle("active", index === doctorCurrentIndex);
      });
    }

    function doctorRenderStack() {
      const total = doctorStackCards.length;

      if (!total) {
        return;
      }

      doctorStackCards.forEach((card, index) => {
        card.classList.remove("active", "next", "next-2", "prev", "hidden");

        const diff = (index - doctorCurrentIndex + total) % total;

        if (diff === 0) {
          card.classList.add("active");
        } else if (diff === 1) {
          card.classList.add("next");
        } else if (diff === 2) {
          card.classList.add("next-2");
        } else if (diff === total - 1) {
          card.classList.add("prev");
        } else {
          card.classList.add("hidden");
        }
      });

      doctorUpdateInfo(doctorStackCards[doctorCurrentIndex]);
      doctorUpdateDots();
    }

    function doctorGoTo(index) {
      doctorCurrentIndex = ((index % doctorStackCards.length) + doctorStackCards.length) % doctorStackCards.length;
      doctorRenderStack();
    }

    function doctorNext() {
      doctorGoTo(doctorCurrentIndex + 1);
    }

    function doctorPrev() {
      doctorGoTo(doctorCurrentIndex - 1);
    }

    doctorCreateDots();
    doctorRenderStack();

    doctorNextBtn?.addEventListener("click", doctorNext);
    doctorPrevBtn?.addEventListener("click", doctorPrev);

    enableSwipe(qs(".doctors-stack-wrap"), {
      onNext: doctorNext,
      onPrev: doctorPrev,
    });

    doctorStackCards.forEach((card, index) => {
      card.addEventListener("click", () => {
        doctorGoTo(index);
      });
    });

    qsa(".doctor-stack-dot").forEach((dot) => {
      dot.addEventListener("click", () => {
        doctorGoTo(Number(dot.dataset.index || 0));
      });
    });
  }

  /* =========================================================
     قسم المقالات التوعوية
  ========================================================= */

  const articleCards = qsa(".etzan-article-card");
  const filterButtons = qsa(".etzan-filter-btn");

  const featuredImage = qs("#featuredImage");
  const featuredCategory = qs("#featuredCategory");
  const featuredReadingTime = qs("#featuredReadingTime");
  const featuredDate = qs("#featuredDate");
  const featuredTitle = qs("#featuredTitle");
  const featuredDescription = qs("#featuredDescription");

  if (articleCards.length) {
    function updateFeaturedArticle(card) {
      if (!card) {
        return;
      }

      if (featuredImage && card.dataset.image) {
        featuredImage.src = card.dataset.image;
      }

      setText(featuredCategory, safeDataset(card, "label", "عام"));
      setText(featuredReadingTime, safeDataset(card, "reading", ""));
      setText(featuredDate, safeDataset(card, "date", ""));
      setText(featuredTitle, safeDataset(card, "title", ""));
      setText(featuredDescription, safeDataset(card, "description", ""));

      articleCards.forEach((item) => item.classList.remove("active"));
      card.classList.add("active");
    }

    articleCards.forEach((card) => {
      card.addEventListener("click", () => {
        updateFeaturedArticle(card);
      });
    });

    filterButtons.forEach((button) => {
      button.addEventListener("click", () => {
        const filter = button.dataset.filter || "all";

        filterButtons.forEach((btn) => btn.classList.remove("active"));
        button.classList.add("active");

        let firstVisibleCard = null;

        articleCards.forEach((card) => {
          const matches = filter === "all" || card.dataset.category === filter;

          card.classList.toggle("hidden", !matches);

          if (matches && !firstVisibleCard) {
            firstVisibleCard = card;
          }
        });

        if (firstVisibleCard) {
          updateFeaturedArticle(firstVisibleCard);
        }
      });
    });
  }

  /* =========================================================
     كويز الصفحة الرئيسية الحالي
     ملاحظة:
     أزلنا كود الكويز القديم الذي كان يبحث عن عناصر غير موجودة
     مثل etzanQuizQuestion و etzanQuizOptions لأنه كان يوقف باقي الجافاسكربت.
  ========================================================= */

  const modal = qs("#homeQuizModal");

  if (modal) {
    const openBtn = qs("#openHomeQuizBtn");
    const closeBtn = qs("#closeHomeQuizBtn");
    const overlay = qs("#closeHomeQuizOverlay");
    const restartBtn = qs("#restartHomeQuiz");

    const steps = qsa(".etzan-quiz-step", modal);
    const progress = qsa(".etzan-quiz-modal__progress span", modal);
    const result = qs("#homeQuizResult");
    const resultTitle = qs("#homeQuizResultTitle");
    const resultDescription = qs("#homeQuizResultDescription");

    let currentQuizStep = 0;
    let answers = [];

    function openModal() {
      modal.classList.add("is-open");
      modal.setAttribute("aria-hidden", "false");
      resetQuiz();
    }

    function closeModal() {
      modal.classList.remove("is-open");
      modal.setAttribute("aria-hidden", "true");
    }

    function showStep(index) {
      currentQuizStep = index;

      steps.forEach((step, i) => {
        step.classList.toggle("is-active", i === index);
      });

      progress.forEach((item, i) => {
        item.classList.toggle("is-active", i <= index);
      });

      result?.classList.remove("is-active");
    }

    function showResult() {
      steps.forEach((step) => step.classList.remove("is-active"));
      progress.forEach((item) => item.classList.add("is-active"));

      const hasHealthCondition =
        answers.includes("diabetes") ||
        answers.includes("pressure") ||
        answers.includes("allergy");

      const wantsLoss = answers.includes("loss");
      const wantsGain = answers.includes("gain");

      if (hasHealthCondition) {
        setText(resultTitle, "الأنسب لك: متابعة مع مختص تغذية");
        setText(resultDescription, "إجاباتك تشير إلى أنك قد تستفيد من توجيه أدق ومتابعة صحية منظمة مع مختص.");
      } else if (wantsLoss) {
        setText(resultTitle, "الأنسب لك: خطة بداية لخسارة الوزن");
        setText(resultDescription, "يمكنك البدء بتوصيات غذائية واضحة ومقالات تساعدك على تحسين عاداتك اليومية.");
      } else if (wantsGain) {
        setText(resultTitle, "الأنسب لك: خطة غذائية لزيادة صحية");
        setText(resultDescription, "إجاباتك تشير إلى حاجتك لبداية منظمة تساعدك على زيادة صحية ومتوازنة.");
      } else {
        setText(resultTitle, "الأنسب لك: توازن غذائي ونمط حياة صحي");
        setText(resultDescription, "إجاباتك مناسبة لبداية مرنة تركز على تحسين العادات والمتابعة البسيطة.");
      }

      result?.classList.add("is-active");
    }

    function resetQuiz() {
      currentQuizStep = 0;
      answers = [];
      showStep(0);
    }

    qsa(".etzan-quiz-options button", modal).forEach((button) => {
      button.addEventListener("click", function () {
        answers.push(this.dataset.answer || "");

        if (currentQuizStep < steps.length - 1) {
          showStep(currentQuizStep + 1);
        } else {
          showResult();
        }
      });
    });

    openBtn?.addEventListener("click", openModal);
    closeBtn?.addEventListener("click", closeModal);
    overlay?.addEventListener("click", closeModal);
    restartBtn?.addEventListener("click", resetQuiz);

    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape" && modal.classList.contains("is-open")) {
        closeModal();
      }
    });
  }

  /* =========================================================
     قسم الأسئلة الشائعة
  ========================================================= */

  const etzanFaqQuestions = qsa(".etzan-faq-question");

  const etzanFaqAnswerBadge = qs("#etzanFaqAnswerBadge");
  const etzanFaqAnswerTitle = qs("#etzanFaqAnswerTitle");
  const etzanFaqAnswerText = qs("#etzanFaqAnswerText");
  const etzanFaqAnswerPoints = qs("#etzanFaqAnswerPoints");
  const etzanFaqAnswerCard = qs("#etzanFaqAnswerCard");

  if (
    etzanFaqQuestions.length &&
    etzanFaqAnswerBadge &&
    etzanFaqAnswerTitle &&
    etzanFaqAnswerText &&
    etzanFaqAnswerPoints
  ) {
    function updateEtzanFaq(button) {
      setText(etzanFaqAnswerBadge, safeDataset(button, "badge", ""));
      setText(etzanFaqAnswerTitle, safeDataset(button, "title", ""));
      setText(etzanFaqAnswerText, safeDataset(button, "text", ""));

      setHtml(
        etzanFaqAnswerPoints,
        `
          <div class="etzan-faq-point">${safeDataset(button, "point1", "")}</div>
          <div class="etzan-faq-point">${safeDataset(button, "point2", "")}</div>
          <div class="etzan-faq-point">${safeDataset(button, "point3", "")}</div>
        `
      );

      etzanFaqQuestions.forEach((item) => item.classList.remove("active"));
      button.classList.add("active");

      if (etzanFaqAnswerCard) {
        etzanFaqAnswerCard.classList.remove("is-changing");

        void etzanFaqAnswerCard.offsetWidth;

        etzanFaqAnswerCard.classList.add("is-changing");
      }
    }

    etzanFaqQuestions.forEach((button) => {
      button.addEventListener("click", () => {
        updateEtzanFaq(button);
      });
    });
  }
});
