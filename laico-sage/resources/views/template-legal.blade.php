{{--
  Template Name: لایکو — برگه متنی / حقوقی (حریم خصوصی، کوکی‌ها)
--}}
@extends('layouts.app')

@section('content')
  @php
    $postObj = get_queried_object();
    $pageId = $postObj instanceof \WP_Post ? (int) $postObj->ID : 0;
    $slug = $postObj instanceof \WP_Post ? (string) $postObj->post_name : 'privacy';
    $editorContent = $pageId > 0 ? trim((string) get_post_field('post_content', $pageId)) : '';
    $enContent = $pageId > 0 ? trim((string) get_post_meta($pageId, '_laico_content_en', true)) : '';
    $isCookies = ($slug === 'cookies');
  @endphp

  <section class="pd-top ar-top">
    <div class="ar-wrap">
      <nav class="crumbs" aria-label="breadcrumb">
        <a href="{{ esc_url($navUrls['home'] ?? home_url('/')) }}">{!! laico_bi('صفحه اصلی', 'Home') !!}</a>
        <span aria-hidden="true">/</span>
        <span>
          @if ($isCookies)
            {!! laico_bi('سیاست کوکی‌ها', 'Cookie policy') !!}
          @else
            {!! laico_bi('حریم خصوصی', 'Privacy policy') !!}
          @endif
        </span>
      </nav>
      <h1>
        @if ($isCookies)
          {!! laico_bi('سیاست استفاده از کوکی‌ها', 'Cookie policy') !!}
        @elseif ($pageId > 0 && $slug !== 'privacy')
          {!! laico_bi((string) get_the_title($pageId), (string) get_post_meta($pageId, '_laico_hero_title_en', true)) !!}
        @else
          {!! laico_bi('حریم خصوصی کاربران', 'Privacy policy') !!}
        @endif
      </h1>
    </div>
  </section>

  <article class="ar">
    <div class="ar-body">
      @if ($editorContent !== '')
        <div class="lang-fa">{!! apply_filters('the_content', $editorContent) !!}</div>
        @if ($enContent !== '')
          <div class="lang-en" hidden>{!! wpautop($enContent) !!}</div>
        @endif
      @elseif ($isCookies)
        <p>{!! laico_bi('این وب‌سایت تنها از ذخیره‌سازی محلی مرورگر (LocalStorage) برای به خاطر سپردن زبان انتخابی شما (فارسی یا انگلیسی) و کوکی‌های ضروری امنیتی وردپرس استفاده می‌کند.', 'This website uses browser LocalStorage solely to remember your preferred display language (Persian or English) alongside essential WordPress security cookies.') !!}</p>
        <h2>{!! laico_bi('عدم ردیابی تبلیغاتی', 'No advertising trackers') !!}</h2>
        <p>{!! laico_bi('ما هیچ‌گونه کوکی تبلیغاتی شخص ثالث برای فروش یا اشتراک‌گذاری رفتار کاربران به کار نمی‌گیریم.', 'We do not set third-party advertising cookies or sell visitor browsing data.') !!}</p>
      @else
        <p>{!! laico_bi('اطلاعاتی که در فرم‌های درخواست مشاوره لایکو دیزاین وارد می‌کنید (نام، شماره تماس و شرح پروژه) صرفاً جهت هماهنگی جلسه مشاوره و پیگیری پروژهٔ شما در سرور اختصاصی مجموعه نگهداری می‌شود.', 'Information submitted through Laico Design consultation forms (name, phone number and project brief) is stored securely on our server strictly to schedule and follow up on your consultation.') !!}</p>
        <h2>{!! laico_bi('محرمانگی اطلاعات کارفرمایان', 'Client confidentiality') !!}</h2>
        <p>{!! laico_bi('اطلاعات تماس، نقشه‌ها و جزئیات ملک شما نزد استودیوی لایکو محرمانه است و هرگز در اختیار اشخاص یا مجموعه‌های ثالث قرار نمی‌گیرد. برای درخواست حذف اطلاعات ثبت‌شده می‌توانید با دفتر آمل تماس بگیرید.', 'Your contact details, floor plans and property data remain strictly confidential within Laico Design and are never shared with third parties. Contact our Amol studio anytime to request deletion of your records.') !!}</p>
      @endif
    </div>
  </article>
@endsection
