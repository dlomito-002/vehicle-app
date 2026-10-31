/* Ecosistema Carrousel · comportamiento visual compartido
 * @version 1.5.0 (misma versión que ecosystem.css; historial en docs/ECOSISTEMA.md)
 * Archivo IDÉNTICO en todas las apps. Solo presentación: no envía datos ni cambia lógica.
 */
(()=>{'use strict';
const desktop=window.matchMedia('(min-width:761px)');

// Tablas más anchas que su contenedor: activa la columna de acciones fija (ecosystem.css §3).
function markOverflowingTables(){
  document.querySelectorAll('.data-table-wrap').forEach(w=>w.classList.toggle('is-overflowing',w.scrollWidth-w.clientWidth>8));
}

// "Más acciones" dentro de una tabla desplazable: el panel se abre fijo sobre la pantalla
// para que no lo recorte el contenedor.
function placePanel(details){
  const panel=details.querySelector('.row-action-disclosure-panel');
  const summary=details.querySelector('summary');
  if(!panel||!summary)return;
  if(!details.open||!desktop.matches){panel.classList.remove('is-floating');panel.style.top=panel.style.left='';return;}
  panel.classList.add('is-floating');
  const r=summary.getBoundingClientRect();
  const w=panel.offsetWidth,h=panel.offsetHeight;
  const below=r.bottom+6+h<=window.innerHeight;
  panel.style.top=Math.max(8,below?r.bottom+6:r.top-6-h)+'px';
  panel.style.left=Math.max(8,Math.min(window.innerWidth-w-8,r.right-w))+'px';
}
function closeFloatingPanels(){
  document.querySelectorAll('.data-table .row-action-disclosure[open]').forEach(d=>{d.open=false;placePanel(d);});
}

document.addEventListener('toggle',e=>{
  const d=e.target;
  if(d instanceof HTMLDetailsElement&&d.matches('.data-table .row-action-disclosure'))placePanel(d);
},true);
document.querySelectorAll('.data-table-wrap').forEach(w=>w.addEventListener('scroll',closeFloatingPanels,{passive:true}));
window.addEventListener('scroll',closeFloatingPanels,{passive:true});
window.addEventListener('resize',()=>{markOverflowingTables();closeFloatingPanels();});
markOverflowingTables();
// Algunas apps normalizan tablas después de cargar; se recalcula cuando todo terminó.
window.addEventListener('load',markOverflowingTables);

// Botón de tema (ÚNICO manejador del ecosistema; las apps no deben tener otro): alterna claro ↔ oscuro.
// Ícono fijo ◐; el título dice a qué modo cambia. Misma clave en todas las apps, así el modo se respeta
// al pasar de una a otra. Sin preferencia guardada se usa el modo del sistema.
const THEME_KEY='carrousel-theme';
function syncThemeButtons(){
  const dark=document.documentElement.dataset.theme==='dark';
  document.querySelectorAll('[data-theme-toggle],[data-eco-theme-toggle]').forEach(b=>{
    const label=dark?'Cambiar a modo claro':'Cambiar a modo oscuro';
    b.title=label;b.setAttribute('aria-label',label);
    b.querySelectorAll('[data-theme-icon]').forEach(i=>{i.textContent='◐';});
  });
}
function applyTheme(mode,persist){
  document.documentElement.dataset.theme=mode;
  document.documentElement.dataset.themePreference=mode;
  document.documentElement.style.colorScheme=mode;
  if(persist){try{localStorage.setItem(THEME_KEY,mode);}catch(err){}}
  syncThemeButtons();
  window.dispatchEvent(new CustomEvent('carrousel:themechange',{detail:{preference:mode,resolved:mode}}));
}
document.addEventListener('click',e=>{
  const btn=e.target instanceof Element?e.target.closest('[data-theme-toggle],[data-eco-theme-toggle]'):null;
  if(!btn)return;
  e.preventDefault();
  applyTheme(document.documentElement.dataset.theme==='dark'?'light':'dark',true);
});
syncThemeButtons();

// "Procesando" y avisos: misma animación en todas las apps (ecosystem.css §10).
// EcoUI.busy('Guardando…'), EcoUI.idle() y EcoUI.toast('Listo', 'success'|'warning'|'danger'|'info', ms).
// Si la pantalla no trae el marcado, se crea al primer uso.
function ensureStatus(){
  let el=document.getElementById('global-action-status');
  if(!el){
    el=document.createElement('div');
    el.id='global-action-status';el.className='global-action-status';
    el.setAttribute('role','status');el.setAttribute('aria-live','polite');el.setAttribute('aria-hidden','true');
    el.innerHTML='<div class="global-action-card"><span class="global-action-spinner" aria-hidden="true"></span><strong>Procesando información</strong><span data-action-message>Espera un momento…</span></div>';
    document.body.appendChild(el);
  }
  return el;
}
function ensureToasts(){
  let el=document.getElementById('app-toast-container');
  if(!el){
    el=document.createElement('div');
    el.id='app-toast-container';el.className='app-toast-container';
    el.setAttribute('aria-live','polite');el.setAttribute('aria-atomic','false');
    document.body.appendChild(el);
  }
  return el;
}
function busy(message){
  const el=ensureStatus();
  const msg=el.querySelector('[data-action-message]');
  if(msg)msg.textContent=message||'Espera un momento…';
  el.classList.add('show');el.setAttribute('aria-hidden','false');
}
function idle(){
  const el=document.getElementById('global-action-status');
  if(!el)return;
  el.classList.remove('show');el.setAttribute('aria-hidden','true');
}
function toast(message,type,duration){
  if(!message)return;
  const kind=['success','warning','danger','info'].includes(type)?type:(type==='error'?'danger':'info');
  const item=document.createElement('div');
  item.className='app-toast '+kind;
  item.setAttribute('role',kind==='danger'?'alert':'status');
  const text=document.createElement('span');
  text.className='app-toast-message';text.textContent=String(message);
  const close=document.createElement('button');
  close.className='app-toast-close';close.type='button';close.setAttribute('aria-label','Cerrar');close.textContent='×';
  item.append(text,close);ensureToasts().appendChild(item);
  const remove=()=>item.remove();
  close.addEventListener('click',remove);
  const ms=duration===undefined?3600:duration;
  if(ms>0)window.setTimeout(remove,ms);
}
// Confirmaciones: mismo diálogo en todas las apps (ecosystem.css §11); nunca el confirm() del navegador.
// Marcado: data-confirm="¿Pregunta?" en un formulario, su botón de envío, un enlace o un botón
// (opcionales data-confirm-title, data-confirm-ok y data-confirm-danger="1|0").
// Desde código: EcoUI.confirm('¿Pregunta?', {title, ok, danger}).then(ok => …).
const DANGER_WORDS=/\b(eliminar|elimina|borrar|quitar|retirar|desactivar|anular|rechazar|descartar|revocar)\b/i;
function confirmDialog(message,options){
  const opts=options||{};
  const text=String(message||'¿Confirmar esta acción?');
  if(typeof HTMLDialogElement!=='function')return Promise.resolve(window.confirm(text));
  return new Promise(resolve=>{
    const danger=opts.danger===undefined?DANGER_WORDS.test(text):!!opts.danger;
    const dlg=document.createElement('dialog');
    dlg.className='eco-dialog eco-confirm'+(danger?' is-danger':'');
    dlg.setAttribute('aria-labelledby','eco-confirm-title');dlg.setAttribute('aria-describedby','eco-confirm-text');
    const card=document.createElement('div');
    card.className='eco-confirm-card';
    const icon=document.createElement('span');
    icon.className='eco-confirm-icon';icon.setAttribute('aria-hidden','true');icon.textContent=danger?'!':'?';
    const title=document.createElement('h2');
    title.id='eco-confirm-title';title.textContent=opts.title||(danger?'Confirmar acción definitiva':'Confirmar acción');
    const body=document.createElement('p');
    body.id='eco-confirm-text';body.textContent=text;
    const actions=document.createElement('div');
    actions.className='eco-confirm-actions';
    const cancel=document.createElement('button');
    cancel.type='button';cancel.value='cancel';cancel.className='eco-confirm-btn';cancel.textContent=opts.cancel||'Cancelar';
    const ok=document.createElement('button');
    ok.type='button';ok.value='ok';ok.className='eco-confirm-btn '+(danger?'is-danger':'is-primary');ok.textContent=opts.ok||(danger?'Sí, continuar':'Confirmar');
    actions.append(cancel,ok);card.append(icon,title,body,actions);dlg.appendChild(card);
    // Botones sin formulario: ningún script de la app que escuche "submit" puede impedir que se cierre.
    cancel.addEventListener('click',()=>dlg.close('cancel'));
    ok.addEventListener('click',()=>dlg.close('ok'));
    dlg.addEventListener('click',e=>{if(e.target===dlg)dlg.close('cancel');});
    dlg.addEventListener('close',()=>{resolve(dlg.returnValue==='ok');dlg.remove();},{once:true});
    document.body.appendChild(dlg);
    dlg.showModal();
    (danger?cancel:ok).focus();
  });
}
function confirmOptions(el){
  const d=el.dataset;
  return {title:d.confirmTitle||undefined,ok:d.confirmOk||undefined,danger:d.confirmDanger===undefined?undefined:d.confirmDanger!=='0'};
}
// Repite la acción ya aceptada sin el atributo, para que ningún otro script vuelva a preguntar.
function replayWithout(elements,action){
  const saved=elements.filter(Boolean).map(el=>[el,el.getAttribute('data-confirm')]);
  saved.forEach(([el])=>el.removeAttribute('data-confirm'));
  try{action();}finally{saved.forEach(([el,value])=>{if(value!==null)el.setAttribute('data-confirm',value);});}
}
window.addEventListener('submit',e=>{
  const form=e.target instanceof HTMLFormElement?e.target:null;
  if(!form)return;
  const submitter=e.submitter instanceof HTMLElement&&e.submitter.hasAttribute('data-confirm')?e.submitter:null;
  const source=submitter||(form.hasAttribute('data-confirm')?form:null);
  if(!source)return;
  e.preventDefault();e.stopImmediatePropagation();
  confirmDialog(source.getAttribute('data-confirm'),confirmOptions(source)).then(accepted=>{
    if(!accepted)return;
    replayWithout([form,e.submitter instanceof HTMLElement?e.submitter:null],()=>{
      if(typeof form.requestSubmit!=='function'){form.submit();return;}
      try{e.submitter?form.requestSubmit(e.submitter):form.requestSubmit();}catch(err){form.requestSubmit();}
    });
  });
},true);
window.addEventListener('click',e=>{
  const el=e.target instanceof Element?e.target.closest('a[data-confirm],button[type="button"][data-confirm]'):null;
  if(!el)return;
  e.preventDefault();e.stopImmediatePropagation();
  confirmDialog(el.getAttribute('data-confirm'),confirmOptions(el)).then(accepted=>{
    if(accepted)replayWithout([el],()=>el.click());
  });
},true);
window.EcoUI=Object.freeze({busy,idle,toast,confirm:confirmDialog});
// Al volver con "Atrás" el navegador restaura la página: nunca debe quedar el "Procesando" abierto.
window.addEventListener('pageshow',idle);
})();
