/* Filterable article listing, search and pagination — no libraries required. */
(() => {
  'use strict';
  const {posts,postCard} = window.KINGAVTO_BLOG;
  const categories = ['Все статьи','Обслуживание','Тормозная система','Шины и диски','Подвеска','Электрика','Подбор запчастей'];
  const perPage = 6;
  const q = id => document.getElementById(id);
  const query = new URLSearchParams(window.location.search);
  let category = categories.includes(query.get('category')) ? query.get('category') : 'Все статьи';
  let term = query.get('q') || '';
  let page = Math.max(1,parseInt(query.get('page')||'1',10)||1);
  q('blogSearch').value = term;
  const esc = window.KINGAVTO_BLOG.escape;
  function writeUrl() {
    const p = new URLSearchParams();
    if (category !== 'Все статьи') p.set('category',category);
    if (term.trim()) p.set('q',term.trim());
    if (page>1) p.set('page',String(page));
    const suffix = p.toString() ? '?'+p.toString() : '';
    try { history.replaceState({},'',window.location.pathname+suffix); } catch { /* file: preview may block history */ }
  }
  function render() {
    q('blogCategories').innerHTML=categories.map(c => `<button type="button" class="blog-tab" data-category="${esc(c)}" aria-pressed="${c===category}">${esc(c)}</button>`).join('');
    const needle=term.trim().toLocaleLowerCase('ru');
    const filtered=posts.filter(p => (category==='Все статьи'||p.category===category) && (!needle || `${p.title} ${p.excerpt} ${p.category} ${p.body.join(' ')}`.toLocaleLowerCase('ru').includes(needle)));
    const pages=Math.ceil(filtered.length/perPage);
    page=Math.max(1,Math.min(page,Math.max(1,pages)));
    const shown=filtered.slice((page-1)*perPage,page*perPage);
    q('blogGrid').innerHTML=shown.map(postCard).join('');
    q('blogGrid').hidden=filtered.length===0;
    q('blogEmpty').hidden=filtered.length!==0;
    q('resultCount').textContent=`Найдено статей: ${filtered.length}`;
    const pager=q('blogPagination');
    pager.hidden=pages<=1;
    pager.innerHTML=pages<=1?'':`<button class="blog-page-button" data-page="${page-1}" type="button" aria-label="Предыдущая страница" ${page===1?'disabled':''}>‹</button>` + Array.from({length:pages},(_,i)=>`<button class="blog-page-button" data-page="${i+1}" type="button" ${page===i+1?'aria-current="page"':''}>${i+1}</button>`).join('')+`<button class="blog-page-button" data-page="${page+1}" type="button" aria-label="Следующая страница" ${page===pages?'disabled':''}>›</button>`;
    writeUrl();
  }
  q('blogCategories').addEventListener('click',event=>{
    const target=event.target.closest('button[data-category]');
    if(!target) return;
    category=target.dataset.category;page=1;render();
  });
  q('blogSearch').addEventListener('input',event=>{
    term=event.target.value;page=1;render();
  });
  q('blogPagination').addEventListener('click',event=>{
    const target=event.target.closest('button[data-page]');
    if (!target || target.disabled) return;
    page=Number(target.dataset.page);render();
    q('articles').scrollIntoView({behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'auto':'smooth'});
  });
  q('blogReset').addEventListener('click',()=>{category='Все статьи';term='';page=1;q('blogSearch').value='';render();});
  render();
})();
