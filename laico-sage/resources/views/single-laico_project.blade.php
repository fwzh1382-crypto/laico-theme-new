@extends('layouts.app')

@section('content')
  @php
    $prevPost = get_previous_post();
    $nextPost = get_next_post();
  @endphp

  <section class="pd-top">
    @if(!empty($project['image']['url']))
      <div class="pd-top-bg" aria-hidden="true">
        <img src="{{ esc_url($project['image']['url']) }}"
             alt=""
             width="{{ (int) $project['image']['width'] }}"
             height="{{ (int) $project['image']['height'] }}"
             decoding="async">
      </div>
    @endif
    <div class="pd-wrap">
      <nav class="crumbs" aria-label="breadcrumb">
        <a href="{{ esc_url($navUrls['projects'] ?? home_url('/projects/')) }}">{!! laico_bi('پروژه‌ها', 'Projects') !!}</a>
        <span aria-hidden="true">/</span>
        <span>{!! laico_bi($project['title_fa'], $project['title_en']) !!}</span>
      </nav>
      <p class="pd-kicker">
        {!! laico_bi($project['category_fa'], $project['category_en']) !!}
        ·
        {!! laico_bi($project['space_fa'], $project['space_en']) !!}
      </p>
      <h1>{!! laico_bi($project['title_fa'], $project['title_en']) !!}</h1>
      @if(!empty($project['lede_fa']))
        <p class="pd-lede">{!! laico_bi($project['lede_fa'], $project['lede_en']) !!}</p>
      @endif
    </div>
  </section>

  <section class="pd-wrap pd-main">
    <figure class="pd-cover">
      <img src="{{ esc_url($project['image']['url']) }}"
           alt="{{ esc_attr($project['title_fa'] . ' — تصویر اصلی') }}"
           data-alt-fa="{{ esc_attr($project['title_fa'] . ' — تصویر اصلی') }}"
           data-alt-en="{{ esc_attr($project['title_en'] . ' — main image') }}"
           width="{{ (int) $project['image']['width'] }}"
           height="{{ (int) $project['image']['height'] }}"
           fetchpriority="high">
    </figure>

    <aside class="pd-card" aria-label="{{ esc_attr($project['title_fa']) }}">
      <h2>{!! laico_bi('مشخصات پروژه', 'Project details') !!}</h2>
      <dl class="pd-specs">
        <div class="pd-spec">
          <dt>{!! laico_bi('کارفرما', 'Client') !!}</dt>
          <dd>{!! laico_bi($project['client_fa'], $project['client_en']) !!}</dd>
        </div>
        <div class="pd-spec">
          <dt>{!! laico_bi('سال انجام پروژه', 'Year') !!}</dt>
          <dd>{!! laico_bi($project['year_fa'], $project['year_en']) !!}</dd>
        </div>
        <div class="pd-spec">
          <dt>{!! laico_bi('مدت زمان پروژه', 'Duration') !!}</dt>
          <dd>{!! laico_bi($project['duration_fa'], $project['duration_en']) !!}</dd>
        </div>
        <div class="pd-spec">
          <dt>{!! laico_bi('مکان پروژه', 'Location') !!}</dt>
          <dd>{!! laico_bi($project['place_fa'], $project['place_en']) !!}</dd>
        </div>
        @if(!empty($project['area_fa']))
          <div class="pd-spec">
            <dt>{!! laico_bi('متراژ پروژه', 'Area') !!}</dt>
            <dd>{!! laico_bi($project['area_fa'], $project['area_en']) !!}</dd>
          </div>
        @endif
      </dl>
      <a class="btn-solid" href="{{ esc_url($navUrls['contact'] ?? home_url('/contact/')) }}">
        {!! laico_bi('درخواست مشاوره برای پروژه مشابه', 'Ask about a similar project') !!}
      </a>
    </aside>
  </section>

  @if(!empty($project['content_fa']))
    <section class="pd-wrap pd-about">
      <h2>{!! laico_bi('دربارهٔ پروژه', 'About the project') !!}</h2>
      <div class="pd-text">
        <div class="lang-fa">{!! $project['content_fa'] !!}</div>
        <div class="lang-en" hidden>{!! $project['content_en'] !!}</div>
      </div>
    </section>
  @endif

  {{-- Sketches: only projects that have them get this section (see Projects::formatSingleProject) --}}
  @if(!empty($project['sketches']))
    @php
      $sketchCount = count($project['sketches']);
      $sketchPortrait = ($project['sketch_layout'] ?? 'landscape') === 'portrait';
      $sketchCollapsible = !empty($project['sketch_collapsible']);
    @endphp
    <section class="pd-wrap pd-sketch-sec" aria-labelledby="sk-title">
      <h2 id="sk-title">{!! laico_bi('اسکیس‌های پروژه', 'Project sketches') !!}</h2>
      <div class="pd-sketches{{ $sketchPortrait ? ' pd-sketches--portrait' : '' }}" id="sk-grid" data-gallery data-sketch-count="{{ $sketchCount }}"{!! $sketchCollapsible ? ' data-sketch-collapsible' : '' !!}>
        @foreach ($project['sketches'] as $sIdx => $sketch)
          @php
            $sketchAltFa = $sketch['alt_fa'] . ' — اسکیس ' . \App\Support\Jalali::toPersianDigits($sIdx + 1);
          @endphp
          <a class="pd-sketch" href="{!! esc_url($sketch['full_url']) !!}" data-gallery-item>
            <span class="pd-sketch-frame">
              <img src="{!! esc_url($sketch['url']) !!}"
                   alt="{{ $sketchAltFa }}"
                   data-alt-fa="{{ $sketchAltFa }}"
                   data-alt-en="{{ $sketch['alt_en'] . ' — sketch ' . ($sIdx + 1) }}"
                   width="{{ (int) $sketch['width'] }}"
                   height="{{ (int) $sketch['height'] }}"
                   loading="lazy"
                   decoding="async">
            </span>
            @if(!empty($sketch['caption_fa']))
              <span class="pd-sketch-cap">{!! laico_bi((string) $sketch['caption_fa'], (string) ($sketch['caption_en'] ?? '')) !!}</span>
            @endif
          </a>
        @endforeach
      </div>
      @if($sketchCollapsible)
        {{-- Hidden until project.js collapses the grid, so without JS every sketch simply stays visible --}}
        <p class="pd-sketch-actions" hidden>
          <button type="button" class="btn-pill-dark pd-sketch-more" data-sketch-toggle aria-controls="sk-grid" aria-expanded="true" hidden>
            <span class="pd-sketch-more-open">{!! laico_bi('نمایش همهٔ ' . \App\Support\Jalali::toPersianDigits($sketchCount) . ' اسکیس', 'Show all ' . $sketchCount . ' sketches') !!}</span>
            <span class="pd-sketch-more-close">{!! laico_bi('نمایش کمتر', 'Show fewer') !!}</span>
          </button>
        </p>
      @endif
    </section>
  @endif

  @if(!empty($project['gallery']))
    <section class="pd-wrap pd-gallery-sec" aria-labelledby="gal-title">
      <h2 id="gal-title">{!! laico_bi('گالری', 'Gallery') !!}</h2>
      <div class="pd-gallery" data-gallery>
        @foreach ($project['gallery'] as $gIdx => $shot)
          <a class="pd-shot" href="{{ esc_url($shot['full_url']) }}" data-gallery-item aria-label="{{ esc_attr($project['title_fa']) }}">
            <img src="{{ esc_url($shot['url']) }}"
                 alt="{{ esc_attr($shot['alt_fa'] . ' — تصویر ' . \App\Support\Jalali::toPersianDigits($gIdx + 1)) }}"
                 data-alt-fa="{{ esc_attr($shot['alt_fa'] . ' — تصویر ' . \App\Support\Jalali::toPersianDigits($gIdx + 1)) }}"
                 data-alt-en="{{ esc_attr($shot['alt_en'] . ' — image ' . ($gIdx + 1)) }}"
                 width="{{ (int) $shot['width'] }}"
                 height="{{ (int) $shot['height'] }}"
                 loading="lazy"
                 decoding="async">
          </a>
        @endforeach
      </div>
    </section>
  @endif

  @if(!empty($project['video_url']))
    <section class="pd-wrap pd-video-sec" aria-labelledby="vid-title">
      <h2 id="vid-title">{!! laico_bi('ویدئو پروژه', 'Project video') !!}</h2>
      <div class="pd-video">
        <video controls preload="none" playsinline poster="{{ esc_url($project['video_poster']) }}">
          <source src="{{ esc_url($project['video_url']) }}" type="video/mp4">
        </video>
      </div>
    </section>
  @endif

  <nav class="pd-pager" aria-label="{{ esc_attr($project['title_fa']) }}">
    @if ($prevPost instanceof \WP_Post)
      @php($prevTitleEn = trim((string) get_post_meta($prevPost->ID, '_laico_title_en', true)) ?: get_the_title($prevPost))
      <a class="pd-pager-link" href="{{ esc_url(get_permalink($prevPost)) }}" rel="prev">
        <span class="pd-pager-label">{!! laico_bi('پروژهٔ قبلی', 'Previous project') !!}</span>
        <strong>{!! laico_bi(get_the_title($prevPost), $prevTitleEn) !!}</strong>
      </a>
    @else
      <span></span>
    @endif

    <a class="pd-pager-all btn-pill-dark" href="{{ esc_url($navUrls['projects'] ?? home_url('/projects/')) }}">
      {!! laico_bi('همهٔ پروژه‌ها', 'All projects') !!}
    </a>

    @if ($nextPost instanceof \WP_Post)
      @php($nextTitleEn = trim((string) get_post_meta($nextPost->ID, '_laico_title_en', true)) ?: get_the_title($nextPost))
      <a class="pd-pager-link pd-pager-next" href="{{ esc_url(get_permalink($nextPost)) }}" rel="next">
        <span class="pd-pager-label">{!! laico_bi('پروژهٔ بعدی', 'Next project') !!}</span>
        <strong>{!! laico_bi(get_the_title($nextPost), $nextTitleEn) !!}</strong>
      </a>
    @else
      <span></span>
    @endif
  </nav>
@endsection
