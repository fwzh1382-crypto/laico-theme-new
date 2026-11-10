@php
  $queried = get_queried_object();
  $slug = $queried instanceof \WP_Post ? (string) $queried->post_name : '';

  $autoSlugMap = [
    'services' => 'template-services',
    'projects' => 'archive-laico_project',
    'about' => 'template-about',
    'contact' => 'template-contact',
    'showroom' => 'template-showroom',
    'kitchen' => 'template-collection',
    'living' => 'template-collection',
    'bath' => 'template-collection',
    'cafe' => 'template-collection',
    'studio' => 'template-collection',
    'systems' => 'template-collection',
    'process' => 'template-collection',
    'materials' => 'template-collection',
    'privacy' => 'template-legal',
    'cookies' => 'template-legal',
  ];

  // برگه‌ای که نامک آن در نگاشت بالا باشد با همان قالب اختصاصی رندر می‌شود؛ سایر برگه‌ها با چیدمان عمومی.
  $autoView = $autoSlugMap[$slug] ?? '';
@endphp

{{-- `@extends` همیشه در انتهای فایل اجرا می‌شود (حتی درون @if)، پس والد به‌صورت پویا انتخاب می‌شود تا صفحه دو بار رندر نشود. --}}
@extends($autoView !== '' ? $autoView : 'layouts.app')

@if ($autoView === '')
  @section('content')
    @while(have_posts())
      @php
        the_post();
        $pid = get_the_ID();
        $hasThumb = has_post_thumbnail($pid);
      @endphp

      @if ($hasThumb)
        @php($pageHero = \App\Support\Hero::resolve($slug ?: 'page', (int) $pid))
        @include('partials.hero-page', ['hero' => $pageHero])
      @else
        <section class="pd-top ar-top">
          <div class="ar-wrap">
            <h1>{!! laico_bi((string) get_the_title(), (string) get_post_meta($pid, '_laico_hero_title_en', true)) !!}</h1>
          </div>
        </section>
      @endif

      <section class="wrap prose">
        @php(the_content())
      </section>
    @endwhile
  @endsection
@endif
