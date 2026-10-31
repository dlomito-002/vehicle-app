/* Control de Vehículos · comportamiento del shell del Ecosistema Carrousel
 * (menú con riel de íconos, tema, campana, ayuda "?" y recorrido guiado).
 * Archivo propio de esta app; la capa común es ecosystem.js. No requiere compilar con Vite.
 */
(function(){
  'use strict';

  const root=document.documentElement;
  const body=document.body;
  const THEME_KEY='carrousel-theme';
  const SIDEBAR_KEY='vehiculos:sidebar-collapsed';
  const desktopSidebar=window.matchMedia('(min-width:901px)');
  const sidebar=document.getElementById('app-sidebar');
  const sidebarBackdrop=document.querySelector('[data-sidebar-backdrop]');

  function store(key,value){try{if(value===undefined)return localStorage.getItem(key);localStorage.setItem(key,value);}catch(e){}return null;}
  function escapeHtml(v){return String(v).replace(/[&<>'"]/g,ch=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[ch]));}

  /* ===== Tema ===== */
  function systemTheme(){return window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light';}
  function readTheme(){return store(THEME_KEY)||'system';}
  function applyTheme(pref){
    root.dataset.theme=pref==='system'?systemTheme():pref;
    root.dataset.themePreference=pref;
    document.querySelectorAll('[data-theme-icon]').forEach(el=>{el.textContent=pref==='dark'?'☾':pref==='light'?'☀':'◐';});
  }

  /* ===== Avisos y estado de envío ===== */
  // "Procesando" y avisos comunes del ecosistema (ecosystem.js → EcoUI; ecosystem.css §10).
  function notify(message,type='info',duration=3600){window.EcoUI?.toast(message,type,duration);}
  function startAction(message){window.EcoUI?.busy(message||'Procesando…');}
  function stopAction(){window.EcoUI?.idle();}

  /* ===== Menú lateral ===== */
  function updateSidebarToggle(){
    const button=document.querySelector('[data-sidebar-toggle]');
    if(!(button instanceof HTMLElement))return;
    const expanded=desktopSidebar.matches?!body.classList.contains('sidebar-collapsed'):Boolean(sidebar&&sidebar.classList.contains('is-open'));
    const label=expanded?'Ocultar menú':'Mostrar menú';
    button.setAttribute('aria-expanded',expanded?'true':'false');
    button.setAttribute('aria-label',label);button.setAttribute('title',label+' lateral');
  }
  function restoreSidebar(){
    if(desktopSidebar.matches){
      body.classList.toggle('sidebar-collapsed',store(SIDEBAR_KEY)==='1');
      if(sidebar)sidebar.classList.remove('is-open');
      if(sidebarBackdrop instanceof HTMLElement)sidebarBackdrop.hidden=true;
    }else{
      body.classList.remove('sidebar-collapsed');
    }
    updateSidebarToggle();
  }
  function toggleSidebar(){
    if(desktopSidebar.matches){
      const collapsed=body.classList.toggle('sidebar-collapsed');
      store(SIDEBAR_KEY,collapsed?'1':'0');
    }else if(sidebar){
      const open=sidebar.classList.toggle('is-open');
      if(sidebarBackdrop instanceof HTMLElement)sidebarBackdrop.hidden=!open;
    }
    updateSidebarToggle();
  }
  function closeMobileSidebar(){
    if(desktopSidebar.matches||!sidebar)return;
    sidebar.classList.remove('is-open');
    if(sidebarBackdrop instanceof HTMLElement)sidebarBackdrop.hidden=true;
    updateSidebarToggle();
  }
  if(sidebar){
    sidebar.querySelectorAll('.side-link').forEach(link=>{
      const label=link.querySelector('.side-label');
      const text=label?label.textContent.trim():'';
      if(text&&!link.hasAttribute('title'))link.setAttribute('title',text);
    });
  }

  /* ===== Campana ===== */
  function closeNotifications(){
    const menu=document.querySelector('[data-notifications-menu]');
    const toggle=document.querySelector('[data-notifications-toggle]');
    if(menu instanceof HTMLElement)menu.hidden=true;
    if(toggle instanceof HTMLElement)toggle.setAttribute('aria-expanded','false');
  }
  function toggleNotifications(){
    const menu=document.querySelector('[data-notifications-menu]');
    const toggle=document.querySelector('[data-notifications-toggle]');
    if(!(menu instanceof HTMLElement))return;
    const open=menu.hidden;menu.hidden=!open;
    if(toggle instanceof HTMLElement)toggle.setAttribute('aria-expanded',open?'true':'false');
  }

  /* ===== Ayuda ===== */
  function setHelp(open){
    const panel=document.querySelector('[data-help-panel]');
    const backdrop=document.querySelector('[data-help-backdrop]');
    const trigger=document.querySelector('[data-help-open]');
    if(!(panel instanceof HTMLElement))return;
    panel.classList.toggle('open',open);
    panel.setAttribute('aria-hidden',open?'false':'true');
    if(trigger instanceof HTMLElement)trigger.setAttribute('aria-expanded',open?'true':'false');
    if(backdrop instanceof HTMLElement)backdrop.hidden=!open;
    body.classList.toggle('help-open',open);
    if(open){const close=panel.querySelector('[data-help-close]');if(close instanceof HTMLElement)window.requestAnimationFrame(()=>close.focus({preventScroll:true}));}
    else if(trigger instanceof HTMLElement)trigger.focus({preventScroll:true});
  }

  /* ===== Recorrido guiado ===== */
  // Pasos comunes del ecosistema Carrousel: mismos textos que Helpdesk y Caja Chica.
  const SHELL_STEPS=[
    ['[data-sidebar-toggle]','Menú','Muestra u oculta el menú lateral. En computadora, oculto queda una barra de íconos (pasa el cursor sobre uno para ver su nombre); en celular, el menú se desliza desde la izquierda.','Tu preferencia se recuerda en este navegador.'],
    ['.shell-notifications','Notificaciones','La campana reúne lo que tienes pendiente. El número rojo indica cuántos avisos esperan tu atención.','Abre cada aviso para ir directo al caso.'],
    ['.portal-btn','Volver al Portal','Regresa al Portal de Sistemas para abrir otra aplicación de Carrousel.','Se abre en la misma pestaña.'],
    ['.help-fab','Ayuda','Este botón está en todas las pantallas: explica la pantalla actual, inicia su recorrido y abre el Manual.','Úsalo cuando tengas dudas sobre lo que estás viendo.']
  ];
  let tourSteps=[],tourIndex=0,tourActive=false,tourEl=null,overlay=null,popover=null;
  function isVisible(el){
    if(!(el instanceof HTMLElement))return false;
    const style=window.getComputedStyle(el);
    if(style.display==='none'||style.visibility==='hidden')return false;
    const r=el.getBoundingClientRect();return r.width>0&&r.height>0;
  }
  function resolveTour(){
    const own=Array.isArray(window.ECO_TOUR)?window.ECO_TOUR:[];
    const source=own.concat(window.ECO_TOUR_COMMON===false?[]:SHELL_STEPS);
    const found=[];
    source.forEach(([selector,title,text,tip])=>{const el=document.querySelector(selector);if(isVisible(el))found.push({el,title,text,tip:tip||''});});
    return found;
  }
  function ensureTourUi(){
    if(overlay&&popover)return;
    overlay=document.createElement('div');overlay.className='tour-overlay';overlay.addEventListener('click',stopTour);
    popover=document.createElement('aside');popover.className='tour-popover';popover.setAttribute('role','dialog');popover.setAttribute('aria-modal','true');popover.setAttribute('aria-labelledby','eco-tour-title');popover.tabIndex=-1;
    document.body.append(overlay,popover);
  }
  function positionPopover(el){
    if(!popover||!isVisible(el))return;
    const r=el.getBoundingClientRect();
    const vw=window.innerWidth,vh=window.innerHeight;
    const width=Math.min(420,vw-24);popover.style.width=width+'px';
    const h=popover.offsetHeight||220;
    let top=r.bottom+14;if(top+h>vh-12)top=Math.max(12,r.top-h-14);
    let left=Math.max(12,Math.min(r.left,vw-width-12));
    popover.style.top=Math.round(top)+'px';popover.style.left=Math.round(left)+'px';
  }
  function renderTour(){
    if(!tourActive||!tourSteps.length||!popover)return;
    const step=tourSteps[tourIndex];
    if(tourEl)tourEl.classList.remove('tour-highlight');
    tourEl=step.el;tourEl.classList.add('tour-highlight');
    tourEl.scrollIntoView({behavior:'auto',block:'center',inline:'nearest'});
    popover.innerHTML='<div class="tour-progress">Paso '+(tourIndex+1)+' de '+tourSteps.length+'</div>'
      +'<h3 id="eco-tour-title">'+escapeHtml(step.title)+'</h3><p>'+escapeHtml(step.text)+'</p>'
      +(step.tip?'<div class="tour-tip"><strong>Consejo</strong><span>'+escapeHtml(step.tip)+'</span></div>':'')
      +'<div class="tour-actions"><button type="button" class="btn btn-outline-secondary" data-tour-stop>Salir</button><span></span>'
      +(tourIndex>0?'<button type="button" class="btn btn-outline-secondary" data-tour-prev>Anterior</button>':'')
      +'<button type="button" class="btn btn-primary" data-tour-next>'+(tourIndex===tourSteps.length-1?'Finalizar':'Siguiente')+'</button></div>';
    requestAnimationFrame(()=>{positionPopover(tourEl);popover.focus({preventScroll:true});});
  }
  function startTour(){
    tourSteps=resolveTour();
    if(!tourSteps.length){notify('Esta pantalla no tiene pasos visibles para el recorrido guiado.','info');return;}
    setHelp(false);ensureTourUi();
    tourActive=true;tourIndex=0;
    overlay.classList.add('show');popover.classList.add('show');body.classList.add('tour-open');
        renderTour();
  }
  function stopTour(){
    tourActive=false;
    if(tourEl)tourEl.classList.remove('tour-highlight');tourEl=null;
    if(overlay)overlay.classList.remove('show');if(popover)popover.classList.remove('show');
    body.classList.remove('tour-open');
  }

  /* ===== Eventos ===== */
  // Clave anterior de esta app: se respeta la preferencia ya guardada.
  if(store(SIDEBAR_KEY)===null&&store('carrousel-sidebar-collapsed')!==null)store(SIDEBAR_KEY,store('carrousel-sidebar-collapsed'));
  applyTheme(readTheme());
  restoreSidebar();
  if(desktopSidebar.addEventListener)desktopSidebar.addEventListener('change',restoreSidebar);

  document.addEventListener('click',function(e){
    const target=e.target instanceof Element?e.target:null;
    if(!target)return;
    if(target.closest('[data-theme-toggle]')){
      const current=readTheme();
      const next=current==='system'?'light':current==='light'?'dark':'system';
      store(THEME_KEY,next);applyTheme(next);return;
    }
    if(target.closest('[data-sidebar-toggle]')){toggleSidebar();return;}
    if(target.matches('[data-sidebar-backdrop]')){closeMobileSidebar();return;}
    if(target.closest('[data-help-open]')){setHelp(true);closeNotifications();return;}
    if(target.closest('[data-help-close]')||target.matches('[data-help-backdrop]')){setHelp(false);return;}
    if(target.closest('[data-tour-start]')){e.preventDefault();startTour();return;}
    if(target.closest('[data-tour-stop]')){stopTour();return;}
    if(target.closest('[data-tour-prev]')){tourIndex=Math.max(0,tourIndex-1);renderTour();return;}
    if(target.closest('[data-tour-next]')){if(tourIndex>=tourSteps.length-1){stopTour();return;}tourIndex+=1;renderTour();return;}
    if(target.closest('[data-notifications-toggle]')){toggleNotifications();return;}
    if(!target.closest('[data-notifications]'))closeNotifications();

    const row=target.closest('tr[data-row-href]');
    if(row instanceof HTMLElement&&!target.closest('a,button,input,select,textarea,label,form,summary')&&e.button===0){
      if(String(window.getSelection?window.getSelection():'').trim()!=='')return;
      const href=row.dataset.rowHref||'';
      if(href){startAction('Abriendo…');window.location.href=href;}
      return;
    }

    const link=target.closest('a[href]');
    if(link instanceof HTMLAnchorElement){
      if(!desktopSidebar.matches)closeMobileSidebar();
    }
  });

  document.addEventListener('keydown',function(e){
    if(tourActive&&e.key==='Escape'){e.preventDefault();stopTour();return;}
    if(e.key!=='Escape')return;
    setHelp(false);closeNotifications();closeMobileSidebar();
  });

  // Envío único y "Procesando". Las confirmaciones (data-confirm) las muestra ecosystem.js con el diálogo común.
  document.addEventListener('submit',function(e){
    const form=e.target instanceof HTMLFormElement?e.target:null;
    if(!form)return;
    const submitter=e.submitter instanceof HTMLElement?e.submitter:null;
    if(!form.matches('[data-single-submit]'))return;
    if(form.dataset.submitting==='1'){e.preventDefault();return;}
    if(!form.checkValidity())return;
    form.dataset.submitting='1';
    startAction(form.dataset.actionMessage||'Guardando…');
    form.querySelectorAll('button[type="submit"],input[type="submit"]').forEach(btn=>{
      if(btn.disabled)return;
      btn.disabled=true;btn.setAttribute('aria-disabled','true');
      if(btn instanceof HTMLButtonElement&&!btn.dataset.keepLabel){btn.dataset.originalText=btn.textContent||'';btn.textContent='Procesando…';}
    });
    // El botón deshabilitado no viaja en el POST: se conserva su valor (after_save, etc.).
    if(submitter instanceof HTMLButtonElement&&submitter.name){
      const hidden=document.createElement('input');hidden.type='hidden';hidden.name=submitter.name;hidden.value=submitter.value;form.appendChild(hidden);
    }
  });

  window.addEventListener('pageshow',stopAction);
  window.addEventListener('load',stopAction);
  window.addEventListener('resize',()=>{if(tourActive&&tourEl)positionPopover(tourEl);});
  if(window.matchMedia){const mq=window.matchMedia('(prefers-color-scheme: dark)');if(mq.addEventListener)mq.addEventListener('change',()=>{if(readTheme()==='system')applyTheme('system');});}

})();
