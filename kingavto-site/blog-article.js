/* Static blog article view; replace sample content with Blade/CMS fields in production. */
(() => {
  'use strict';
  const {posts,escape,dateLabel,postCard}=window.KINGAVTO_BLOG;
  const slug=new URLSearchParams(location.search).get('slug');
  const post=posts.find(p=>p.slug===slug);
  const holder=document.getElementById('articleContent');
  if (!post) {
    document.title='Статья не найдена — KINGAVTO';
    holder.innerHTML='<div class="article-not-found"><h1>Статья не найдена</h1><p>Возможно, ссылка устарела. Вернитесь к списку публикаций.</p><a class="btn btn-red" href="blog.html">В блог</a></div>';
    document.getElementById('relatedSection').hidden=true;
    return;
  }
  document.title=`${post.title} — KINGAVTO`;
  document.querySelector('meta[name="description"]').setAttribute('content',post.excerpt);
  document.getElementById('articleCrumb').textContent=post.title;
  holder.innerHTML=`<article class="article-wrap"><header class="article-head"><span class="blog-pill">${escape(post.category)}</span><h1>${escape(post.title)}</h1><p class="article-subtitle">${escape(post.excerpt)}</p><div class="article-meta"><time datetime="${escape(post.date)}">${dateLabel(post.date)}</time> · ${post.reading} мин чтения</div></header>
    <div class="article-hero-picture"><img src="assets/${escape(post.image)}" width="1000" height="600" alt="${escape(post.title)}"></div>
    <div class="article-body"><p>${post.body.map(escape).join('</p><p>')}</p><h2>Нужна помощь с выбором?</h2><p>Если для покупки необходима проверка совместимости, подготовьте VIN автомобиля и свяжитесь с нами. Поможем уточнить характеристики нужной детали.</p></div>
    <a class="article-back" href="blog.html"><svg class="icon"><use href="#i-arrow"/></svg> Вернуться к статьям</a></article>`;
  const related=posts.filter(p=>p.slug!==post.slug).sort((a,b)=>Number(b.category===post.category)-Number(a.category===post.category)).slice(0,3);
  document.getElementById('relatedGrid').innerHTML=related.map(postCard).join('');
})();
