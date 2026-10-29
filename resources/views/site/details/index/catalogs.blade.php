    <section class="section section-categories" id="categories" aria-labelledby="categoriesTitle">
      <div class="container">
        <div class="section-head"><h2 id="categoriesTitle">{{ __('Популярные категории') }}</h2><a class="section-more" href="#categoryGrid">{{ __('Все категории') }} <svg class="icon"><use href="#i-arrow"/></svg></a></div>
        <!-- 12 карточек в обычной CSS Grid, без карусели -->
        <div class="categories-grid" id="categoryGrid">
          @include('site.details.cards.catalogs', ['catalogs' => $menuCatalogs->collapse()->where('popular', 1)])
      
         
        </div>
      </div>
    </section>