@if($blogPage && $latestArticles->isNotEmpty())
    <section class="section" id="news" aria-labelledby="newsTitle">
        <div class="container">
            <div class="section-head">
                <h2 id="newsTitle">{{ __('Последние новости') }}</h2>
                <div class="news-controls">
                    <a class="section-more" href="{{ route('page', ['slag' => $blogPage->slag]) }}">{{ __('Все новости') }} <svg class="icon"><use href="#i-arrow"/></svg></a>
                    <button class="round-arrow" id="newsPrev" type="button" aria-label="{{ __('Предыдущие новости') }}">‹</button>
                    <button class="round-arrow" id="newsNext" type="button" aria-label="{{ __('Следующие новости') }}">›</button>
                </div>
            </div>
            <div class="news-track" id="newsTrack" tabindex="0" aria-label="{{ __('Карусель последних новостей') }}">
                @foreach($latestArticles as $article)
                    @php($articleUrl = route('article', ['blogSlag' => $blogPage->slag, 'articleSlag' => $article->slag]))
                    <article @class(['news-card', 'news-card--text' => !$article->preview])>
                        @if($article->preview)
                            <img src="{{ $article->previewUrl('five') }}" loading="lazy" alt="" width="180" height="180">
                        @endif
                        <div>
                            @if($article->created_at)
                                <time datetime="{{ $article->created_at->toDateString() }}"><svg class="icon"><use href="#i-calendar"/></svg>{{ $article->created_at->locale(app()->getLocale())->translatedFormat('j F Y') }}</time>
                            @endif
                            <h3><a href="{{ $articleUrl }}">{{ $article->title ?: $article->slag }}</a></h3>
                            <p>{{ Illuminate\Support\Str::limit(strip_tags($article->short_content ?: ($article->content ?? '')), 180) }}</p>
                            <a class="news-read-more" href="{{ $articleUrl }}" aria-label="{{ __('Читать статью: :title', ['title' => $article->title ?: $article->slag]) }}"><svg class="icon"><use href="#i-arrow"/></svg></a>
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="carousel-dots" id="newsDots" aria-label="{{ __('Положение в карусели') }}" data-group-label="{{ __('Показать группу новостей :number') }}"></div>
        </div>
    </section>
@endif
