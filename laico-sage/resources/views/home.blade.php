@extends('layouts.app')

@section('content')
  {{-- Dynamic Hero Image from the `journal` Page's Featured Image --}}
  @include('partials.hero-page', ['hero' => $hero])

  <section class="jr" id="journal">
    <div class="jr-wrap">
      @if (!empty($featuredArticle))
        <div class="jr-head">
          <p class="eyebrow">{!! laico_bi('آخرین یادداشت', 'Latest') !!}</p>
        </div>
        @include('partials.journal-card', ['item' => $featuredArticle, 'featured' => true])
      @endif

      @if (!empty($journalArticles))
        <div class="jr-head jr-head--grid">
          <h2>{!! laico_bi('همهٔ یادداشت‌ها', 'All notes') !!}</h2>
        </div>
        <div class="jr-grid">
          @foreach ($journalArticles as $art)
            @include('partials.journal-card', ['item' => $art, 'featured' => false])
          @endforeach
        </div>
      @elseif (empty($featuredArticle))
        <div class="prj-empty">
          {!! laico_bi('هنوز یادداشتی در مجله منتشر نشده است.', 'No journal articles published yet.') !!}
        </div>
      @endif

      @php
        $pagination = paginate_links([
          'type' => 'array',
          'prev_text' => laico_bi('قبلی', 'Previous'),
          'next_text' => laico_bi('بعدی', 'Next'),
        ]);
      @endphp
      @if (!empty($pagination))
        <nav class="pager" aria-label="صفحه‌بندی مجله" style="margin-top:40px;">
          @foreach ($pagination as $pageLink)
            {!! $pageLink !!}
          @endforeach
        </nav>
      @endif
    </div>
  </section>
@endsection
