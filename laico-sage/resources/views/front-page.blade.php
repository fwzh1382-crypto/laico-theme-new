@extends('layouts.app')

@section('content')
  @php
    $mosaicClasses = ['narrow-left', 'wide-right', 'wide-left', 'narrow-right'];

    // Graceful fallback if no `laico_project` CPT posts have been created yet
    $mosaicItems = !empty($featuredProjects) ? $featuredProjects : [
      [
        'url' => $navUrls['projects'] ?? home_url('/projects/'),
        'title_fa' => 'پروژه کوهستانی فیروزکوه',
        'title_en' => 'Firouzkuh Mountain Residence',
        'space_fa' => 'معماری',
        'space_en' => 'Architecture',
        'image' => ['url' => laico_asset('images/firouzkuh.webp')],
      ],
      [
        'url' => $navUrls['projects'] ?? home_url('/projects/'),
        'title_fa' => 'طراحی مهد کودک و آموزشگاه خلاقیت کودک نقش دوزک',
        'title_en' => 'Naghsh-e Doozak Kindergarten',
        'space_fa' => 'آموزشی',
        'space_en' => 'Educational',
        'image' => ['url' => laico_asset('images/kindergarten.webp')],
      ],
      [
        'url' => $navUrls['projects'] ?? home_url('/projects/'),
        'title_fa' => 'طراحی باشگاه',
        'title_en' => 'Boutique Athletic Club',
        'space_fa' => 'ورزشی',
        'space_en' => 'Sports',
        'image' => ['url' => laico_asset('images/gym.webp')],
      ],
      [
        'url' => $navUrls['projects'] ?? home_url('/projects/'),
        'title_fa' => 'طراحی کافه در آمل',
        'title_en' => 'Amol Specialty Café',
        'space_fa' => 'کافه و رستوران',
        'space_en' => 'Café & restaurant',
        'image' => ['url' => laico_asset('images/cafe-amol.webp')],
      ],
    ];

    $whyBgUrl = laico_asset('images/hero-services.webp');
    $phoneDisplay = (string) ($themeOpt['phone_primary'] ?? '۰۹۱۲۰۸۹۸۹۳۸');
    $phoneTel = preg_replace('/[^\d+]/', '', (string) ($themeOpt['phone_primary_tel'] ?? '+989120898938')) ?: '+989120898938';
  @endphp

  {{-- 00 — Dynamic Full-Screen Hero (loaded from Front Page's Featured Image) --}}
  @include('partials.hero-page', ['hero' => $hero])

  {{-- 01 — Collections --}}
  @if(!empty($themeOpt['sec_home_collections']))
    <section id="collections">
      <div class="section-head">
        <div class="copy">
          <p class="eyebrow" data-i18n="col-eye">۰۱ — مجموعه‌ها</p>
          <h2 data-i18n="col-title">چهار فضا، یک استاندارد ساخت</h2>
        </div>
        <a class="btn-line" href="{{ esc_url($navUrls['services'] ?? '#collections') }}" data-i18n="col-all">همه مجموعه‌ها</a>
      </div>
      <div class="collection-grid">
        <a class="collection-card" href="{{ esc_url($navUrls['kitchen'] ?? home_url('/kitchen/')) }}">
          <img src="{{ esc_url(laico_asset('images/col-kitchen.webp')) }}" alt="آشپزخانه" width="570" height="776" loading="lazy">
          <span class="shade"></span>
          <span class="collection-label">
            <span class="name" data-i18n="col-kitchen">آشپزخانه</span>
            <span class="code">KITCHEN</span>
          </span>
        </a>
        <a class="collection-card" href="{{ esc_url($navUrls['living'] ?? home_url('/living/')) }}">
          <img src="{{ esc_url(laico_asset('images/col-living.webp')) }}" alt="پذیرایی و اتاق" width="568" height="776" loading="lazy">
          <span class="shade"></span>
          <span class="collection-label">
            <span class="name" data-i18n="col-living">پذیرایی و اتاق</span>
            <span class="code">LIVING</span>
          </span>
        </a>
        <a class="collection-card" href="{{ esc_url($navUrls['bath'] ?? home_url('/bath/')) }}">
          <img src="{{ esc_url(laico_asset('images/col-bath.webp')) }}" alt="حمام" width="568" height="776" loading="lazy">
          <span class="shade"></span>
          <span class="collection-label">
            <span class="name" data-i18n="col-bath">حمام</span>
            <span class="code">BATH</span>
          </span>
        </a>
        <a class="collection-card" href="{{ esc_url($navUrls['cafe'] ?? home_url('/cafe/')) }}">
          <img src="{{ esc_url(laico_asset('images/col-cafe.webp')) }}" alt="کافه و رستوران" width="570" height="776" loading="lazy">
          <span class="shade"></span>
          <span class="collection-label">
            <span class="name" data-i18n="col-cafe">کافه و رستوران</span>
            <span class="code">CAFE</span>
          </span>
        </a>
      </div>
    </section>
  @endif

  {{-- Marquee Ticker --}}
  <div class="marquee" dir="ltr" aria-hidden="true">
    <div class="marquee-track">
      @for ($loopIdx = 0; $loopIdx < 4; $loopIdx++)
        @foreach ($mosaicItems as $mq)
          <span class="mq-item">{!! laico_bi((string) $mq['title_fa'], (string) ($mq['title_en'] ?? '')) !!}</span><span class="mq-dot"></span>
        @endforeach
      @endfor
    </div>
  </div>

  {{-- 02 — Featured Projects Mosaic (from `laico_project` CPT) --}}
  @if(!empty($themeOpt['sec_home_projects']))
    <section class="projects" id="projects">
      <div class="section-head">
        <div class="copy">
          <p class="eyebrow" data-i18n="prj-eye">۰۲ — پروژه‌های منتخب</p>
          <h2 data-i18n="prj-title">نمونه کارها، امضای کیفیت</h2>
        </div>
        <a class="btn-line" href="{{ esc_url($navUrls['projects'] ?? home_url('/projects/')) }}" data-i18n="prj-all">آرشیو کامل</a>
      </div>
      <div class="project-grid">
        @foreach ($mosaicItems as $idx => $item)
          <a class="project-card {{ $mosaicClasses[$idx % 4] }}" href="{{ esc_url($item['url']) }}">
            <img src="{{ esc_url($item['image']['url']) }}"
                 alt="{{ esc_attr($item['title_fa']) }}"
                 data-alt-fa="{{ esc_attr($item['title_fa']) }}"
                 data-alt-en="{{ esc_attr($item['title_en'] ?? $item['title_fa']) }}"
                 loading="lazy">
            <span class="shade"></span>
            <span class="tag">{!! laico_bi($item['space_fa'], $item['space_en']) !!}</span>
            <span class="card-foot">
              <strong>{!! laico_bi($item['title_fa'], $item['title_en']) !!}</strong>
            </span>
          </a>
        @endforeach
      </div>
    </section>
  @endif

  {{-- 03 — Studio & Dynamic Stats --}}
  @if(!empty($themeOpt['sec_home_about']))
    <section class="why" id="studio" style="background: linear-gradient(rgba(11, 29, 51, 0.56), rgba(11, 29, 51, 0.96)), url('{{ esc_url($whyBgUrl) }}') center center / cover fixed;">
      <p class="why-eyebrow" data-i18n="why-eye">۰۳ — استودیو</p>
      <p class="why-quote" data-i18n="why-quote">چرا لایکو دیزاین؟ چون از اولین اسکچ تا آخرین پیچ نصب، یک تیم و یک استاندارد پشت کار شماست؛ متریال صادق، اجرای دقیق و تعهدی که سال‌ها بعد از تحویل هم همراه شما می‌ماند.</p>
      <div class="stats">
        @foreach ($studioStats as $st)
          @php
            $rawNum = trim((string) $st['num']);
            $hasPlus = str_starts_with($rawNum, '+') || str_ends_with($rawNum, '+');
            $digitsOnly = preg_replace('/\D+/', '', \App\Support\Jalali::toEnglishDigits($rawNum)) ?: '0';
          @endphp
          <div class="stat">
            <strong data-count="{{ esc_attr($digitsOnly) }}" @if($hasPlus) data-prefix="+" @endif>{{ $hasPlus ? '+' . $digitsOnly : $digitsOnly }}</strong>
            <em>{!! laico_bi($st['fa'], $st['en']) !!}</em>
          </div>
        @endforeach
      </div>
    </section>
  @endif

  {{-- 04 — Systems & Craft --}}
  @if(!empty($themeOpt['sec_home_craft']))
    <section class="systems" id="systems">
      <div class="systems-visual">
        <img src="{{ esc_url(laico_asset('images/detail.webp')) }}" alt="سیستم‌ها و متریال لایکو" width="1256" height="938" loading="lazy">
      </div>
      <div class="systems-copy">
        <p class="eyebrow" data-i18n="sys-eye">۰۴ — سیستم‌ها و متریال</p>
        <h2 data-i18n="sys-title">بدون دستگیره، بدون درز اضافی</h2>
        <p class="body" data-i18n="sys-body">سیستم‌های آشپزخانه، کمد و پذیرایی لایکو بر پایه پروفیل‌های آلومینیومی، روکش‌های لاک مات و صفحات سنگ طبیعی طراحی می‌شوند؛ با تلورانس اجرایی زیر یک میلی‌متر.</p>
        <dl class="specs">
          <div><dt data-i18n="spec-finish">روکش</dt><dd data-i18n="spec-finish-v">لاک مات، چوب راش، گرس porcelain</dd></div>
          <div><dt data-i18n="spec-hw">سخت‌افزار</dt><dd data-i18n="spec-hw-v">لولای آرام‌بند، ریل فول بلوم</dd></div>
          <div><dt data-i18n="spec-w">ضمانت</dt><dd data-i18n="spec-w-v">۱۰ سال ساخت، ۲ سال خدمات</dd></div>
        </dl>
      </div>
    </section>
  @endif

  {{-- 05 — Showroom Info --}}
  <section class="showroom" id="showroom">
    <div class="showroom-inner">
      <p class="eyebrow" data-i18n="sh-eye">۰۵ — شو روم</p>
      <h2 data-i18n="sh-title">متریال و کیفیت را لمس کنید</h2>
      <p class="body" data-i18n="sh-body">بازدید از شو روم با هماهنگی قبلی؛ همراه با مشاوره فنی و انتخاب پالت متریال.</p>
      <dl class="info-list">
        <div class="info-row">
          <dt data-i18n="addr">آدرس</dt>
          <dd>{!! laico_bi((string) ($themeOpt['address_fa'] ?? 'آمل، دریای ۲۰'), (string) ($themeOpt['address_en'] ?? 'Amol, Darya 20')) !!}</dd>
        </div>
        <div class="info-row">
          <dt data-i18n="hours">ساعات</dt>
          <dd>{!! laico_bi((string) ($themeOpt['hours_fa'] ?? 'شنبه تا پنجشنبه، ۱۰ تا ۱۹'), (string) ($themeOpt['hours_en'] ?? 'Sat – Thu, 10:00 – 19:00')) !!}</dd>
        </div>
        <div class="info-row">
          <dt data-i18n="phone">تماس</dt>
          <dd class="num" style="text-align: right;">
            <a href="tel:{{ esc_attr($phoneTel) }}" style="direction: rtl; display: inline-block;">{{ $phoneDisplay }}</a>
          </dd>
        </div>
      </dl>
    </div>
  </section>

  {{-- 06 — Consultation Form --}}
  @if(!empty($themeOpt['sec_home_cta']))
    @include('partials.home-collaborate', ['sourcePage' => 'صفحه اصلی', 'defaultType' => 'kitchen'])
  @endif
@endsection
